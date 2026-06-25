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
     * Register a new user. Creates User + default UserRoleAssignment + default notify_* UserMeta rows.
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $userId = 'u_' . Str::lower(Str::random(12));
            $slug   = $this->generateSlug($data['display_name']);

            /** @var User $user */
            $user = User::create([
                'id'            => $userId,
                'role'          => $data['role'],
                'display_name'  => $data['display_name'],
                'email'         => $data['email'],
                'password'      => Hash::make($data['password']),
                'phone'         => $data['phone'] ?? null,
                'slug'          => $slug,
                'tier'          => $data['tier'] ?? 'access',
                'verified'      => 0,
                'created_at'    => now(),
            ]);

            UserRoleAssignment::create([
                'user_id'    => $user->id,
                'role'       => $user->role,
                'is_default' => 1,
                'enabled_at' => now(),
            ]);

            // Default notification preferences — all true on create
            $defaultPrefs = [
                'notify_email'         => '1',
                'notify_sms'           => '0',
                'notify_in_app'        => '1',
                'notify_summary'       => '1',
                'notify_plan'          => '1',
                'notify_vault'         => '1',
                'notify_incident'      => '1',
                'notify_steward'       => '1',
                'notify_payment'       => '1',
                'notify_message'       => '1',
                'notify_referral'      => '1',
                'notify_account'       => '1',
            ];
            foreach ($defaultPrefs as $key => $val) {
                UserMeta::create([
                    'user_id'    => $user->id,
                    'meta_key'   => $key,
                    'meta_value' => $val,
                    'meta_type'  => 'bool',
                ]);
            }

            event(new UserRegistered($user));
            return $user;
        });
    }

    /**
     * Verify credentials, check lock/deactivation state, and return user on success.
     * Throws on failure (validation exception model handles it in controller).
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

        if (!Hash::check($password, $user->password)) {
            $this->incrementFailedLogin($user);
            return null;
        }

        $user->update([
            'failed_login_count' => 0,
            'last_login'         => now(),
        ]);

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
        $user->update(['failed_login_count' => $count]);

        if ($count >= 5) {
            $this->lockAccount($user, 'Too many failed login attempts');
        }
    }

    public function lockAccount(User $user, string $reason): void
    {
        $user->update([
            'locked_at'     => now(),
            'locked_reason' => $reason,
        ]);
        $user->tokens()->delete();

        event(new UserLocked($user, $reason));

        $this->activity->log(
            $user->id,
            $this->portalFor($user->role),
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
        $user->update([
            'locked_at'          => null,
            'locked_reason'      => null,
            'failed_login_count' => 0,
        ]);
    }

    public function forgotPassword(string $email): bool
    {
        $user = User::where('email', $email)->first();
        if (!$user) return true; // Always-true response to prevent enumeration

        PasswordResetToken::where('user_id', $user->id)->delete();

        $token = Str::random(64);
        PasswordResetToken::create([
            'token'      => hash('sha256', $token),
            'user_id'    => $user->id,
            'created_at' => now(),
            'expires_at' => now()->addHours(2),
        ]);

        \App\Jobs\SendEmailJob::dispatch(
            'emails.account.05-password-reset',
            ['token' => $token, 'email' => $email],
            $user->id
        );

        return true;
    }

    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        $user = User::where('email', $email)->first();
        if (!$user) return false;

        $hashed = hash('sha256', $token);
        $record = PasswordResetToken::where('token', $hashed)
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) return false;

        $user->update([
            'password'           => Hash::make($newPassword),
            'failed_login_count' => 0,
            'locked_at'          => null,
            'locked_reason'      => null,
        ]);
        $record->update(['used_at' => now()]);

        event(new PasswordReset($user));
        return true;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }
        $user->update(['password' => Hash::make($newPassword)]);
        event(new PasswordReset($user));
        return true;
    }

    public function enableMfa(User $user): array
    {
        $totp = TOTP::generate();
        $totp->setLabel($user->email);
        $totp->setIssuer('Aegis');
        $user->update(['mfa_secret' => $totp->getSecret()]);

        return [
            'secret' => $totp->getSecret(),
            'provisioning_uri' => $totp->getProvisioningUri(),
        ];
    }

    public function verifyMfa(User $user, string $code): bool
    {
        if (!$user->mfa_secret) return false;

        $totp = TOTP::createFromSecret($user->mfa_secret);
        if (!$totp->verify($code, null, 1)) {
            return false;
        }

        $user->update(['mfa_enabled' => true]);
        event(new MfaEnabled($user));
        return true;
    }

    public function disableMfa(User $user, string $password): bool
    {
        if (!Hash::check($password, $user->password)) {
            return false;
        }
        $user->update(['mfa_enabled' => false, 'mfa_secret' => null]);
        event(new MfaDisabled($user));
        return true;
    }

    private function generateSlug(string $name): string
    {
        $base = Str::slug(preg_replace('/^(dr|mr|mrs|ms|prof)\.?\s+/i', '', $name));
        $slug = $base;
        $i = 1;
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
