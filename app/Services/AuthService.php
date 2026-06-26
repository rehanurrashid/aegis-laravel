<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Events\Auth\MfaDisabled;
use App\Events\Auth\MfaEnabled;
use App\Events\Auth\PasswordReset;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Events\Admin\UserLocked;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\UserRoleAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OTPHP\TOTP;

class AuthService
{
    public function __construct(private ActivityService $activity) {}

    /**
     * Register a new user.
     * Creates User + UserRoleAssignment + default notify_* UserMeta rows.
     * Returns a fresh() User instance so all casts (role enum, tier enum) are
     * properly hydrated before the controller uses the object.
     */
    public function register(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $userId = 'ae_' . Str::lower(Str::random(12));
            $slug   = $this->generateSlug($data['display_name']);

            /** @var User $user */
            $user = new User();
            $user->forceFill([
                'id'           => $userId,
                'role'         => $data['role'],
                'display_name' => $data['display_name'],
                'email'        => $data['email'],
                'password'     => Hash::make($data['password']),
                'phone'        => $data['phone'] ?? null,
                'slug'         => $slug,
                'tier'         => $data['tier'] ?? 'access',
                'verified'     => 0,
                'bp_type'      => ($data['role'] === 'business_partner') ? ($data['bp_type'] ?? null) : null,
            ])->save();

            UserRoleAssignment::create([
                'id'         => 'ur_' . Str::lower(Str::random(12)),
                'user_id'    => $user->id,
                'role'       => $data['role'],
                'is_default' => 1,
                'enabled_at' => now(),
            ]);

            // Default notification preferences
            $now        = now();
            $notifyKeys = [
                'notify_email', 'notify_incident', 'notify_message', 'notify_task',
                'notify_assignment', 'notify_attestation', 'notify_plan_change',
                'notify_plan_review', 'notify_role_change', 'notify_payment',
                'notify_proposal', 'notify_agreement', 'notify_summary',
            ];
            $metaRows = [];
            foreach ($notifyKeys as $key) {
                $metaRows[] = [
                    'id'         => 'um_' . Str::lower(Str::random(12)),
                    'user_id'    => $user->id,
                    'meta_key'   => $key,
                    'meta_value' => '1',
                    'meta_type'  => 'boolean',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            UserMeta::insert($metaRows);

            return $user;
        });

        // Fire event AFTER transaction commits so listeners can safely query
        event(new UserRegistered($user));

        // Return a fresh DB-hydrated instance so all enum casts resolve correctly
        return $user->fresh();
    }

    /**
     * Verify credentials and return the user on success, null on failure.
     */
    public function login(string $email, string $password, string $device = 'web'): ?User
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();
        if (!$user) {
            return null;
        }

        if ($user->locked_at !== null || $user->deactivated_at !== null) {
            return null;
        }

        if (!Hash::check($password, (string) $user->password)) {
            $this->incrementFailedLogin($user);
            return null;
        }

        $user->forceFill([
            'failed_login_count' => 0,
            'last_login_at'      => now(),
        ])->save();

        event(new UserLoggedIn($user, $device));
        return $user;
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    public function incrementFailedLogin(User $user): void
    {
        $count = ($user->failed_login_count ?? 0) + 1;
        $user->forceFill(['failed_login_count' => $count])->save();

        if ($count >= 5) {
            $this->lockAccount($user, 'Too many failed login attempts');
        }
    }

    public function lockAccount(User $user, string $reason): void
    {
        $user->forceFill([
            'locked_at'     => now(),
            'locked_reason' => $reason,
        ])->save();

        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        event(new UserLocked($user, $reason));

        $roleValue = $user->role instanceof \BackedEnum
            ? $user->role->value
            : (string) $user->role;

        $this->activity->log(
            $user->id,
            $this->portalFor($roleValue),
            'account',
            ActivitySeverity::Critical,
            'account_locked',
            'Your account has been locked',
            "Reason: {$reason}. Please contact support to restore access.",
            'user',
            $user->id,
        );
    }

    public function unlockAccount(User $user): void
    {
        $user->forceFill([
            'locked_at'          => null,
            'locked_reason'      => null,
            'failed_login_count' => 0,
        ])->save();
    }

    public function forgotPassword(string $email): bool
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return true; // prevent enumeration
        }

        PasswordResetToken::where('user_id', $user->id)->delete();

        $rawToken = bin2hex(random_bytes(32));

        PasswordResetToken::create([
            'id'         => 'prt_' . Str::lower(Str::random(12)),
            'user_id'    => $user->id,
            'token'      => hash('sha256', $rawToken),
            'expires_at' => now()->addHours(2),
            'created_at' => now(),
        ]);

        \App\Jobs\SendEmailJob::dispatch(
            'emails.account.05-password-reset',
            ['token' => $rawToken, 'email' => $email],
            $user->id
        );

        return true;
    }

    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return false;
        }

        $hashed = hash('sha256', $token);
        $record = PasswordResetToken::where('token', $hashed)
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return false;
        }

        $user->forceFill([
            'password'           => Hash::make($newPassword),
            'failed_login_count' => 0,
            'locked_at'          => null,
            'locked_reason'      => null,
        ])->save();

        $record->update(['used_at' => now()]);

        event(new PasswordReset($user));
        return true;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, (string) $user->password)) {
            return false;
        }
        $user->forceFill(['password' => Hash::make($newPassword)])->save();
        event(new PasswordReset($user));
        return true;
    }

    public function enableMfa(User $user): array
    {
        $totp = TOTP::generate();
        $totp->setLabel($user->email);
        $totp->setIssuer('Aegis');

        return [
            'secret'           => $totp->getSecret(),
            'provisioning_uri' => $totp->getProvisioningUri(),
        ];
    }

    public function verifyMfa(User $user, string $code): bool
    {
        $mfa = $user->mfaToken;
        if (!$mfa || !$mfa->secret) {
            return false;
        }

        $totp = TOTP::createFromSecret($mfa->secret);
        if (!$totp->verify($code, null, 1)) {
            return false;
        }

        $user->forceFill(['two_factor_enabled' => true])->save();
        event(new MfaEnabled($user));
        return true;
    }

    public function disableMfa(User $user, string $password): bool
    {
        if (!Hash::check($password, (string) $user->password)) {
            return false;
        }
        $user->forceFill(['two_factor_enabled' => false])->save();
        event(new MfaDisabled($user));
        return true;
    }

    private function generateSlug(string $name): string
    {
        $base = Str::slug(preg_replace('/^(dr|mr|mrs|ms|prof)\.?\s+/i', '', $name)) ?: 'user';
        $slug = $base;
        $i    = 1;
        while (User::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    private function portalFor(string $role): string
    {
        return match ($role) {
            'practitioner'       => 'provider',
            'continuity_steward' => 'continuity_steward',
            'support_steward'    => 'support_steward',
            'business_partner'   => 'business_partner',
            'admin'              => 'admin',
            default              => 'provider',
        };
    }
}
