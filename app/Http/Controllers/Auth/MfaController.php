<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\MfaToken;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MfaController extends Controller
{
    public function __construct(private ActivityService $activity) {}

    /** POST /settings/mfa/enable — generate secret + provisioning URI (unconfirmed) */
    public function enable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return response()->json(['message' => 'Two-factor authentication is already enabled.'], 422);
        }

        $secret = $this->generateTotpSecret();

        $provisioningUri = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode('Aegis'),
            rawurlencode($user->email),
            $secret,
            rawurlencode('Aegis')
        );

        // Generate 8 six-digit numeric backup/recovery codes
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        }

        MfaToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'id'             => 'mfa_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)),
                'secret'         => $secret,
                'recovery_codes' => $recoveryCodes,
                'confirmed_at'   => null,
                'disabled_at'    => null,
            ]
        );

        return response()->json([
            'secret'           => $secret,
            'provisioning_uri' => $provisioningUri,
            'recovery_codes'   => $recoveryCodes,
        ]);
    }

    /** GET /settings/mfa/backup-codes — return existing recovery codes */
    public function backupCodes(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $mfa  = $user->mfaToken;

        if (!$mfa || !$user->two_factor_enabled) {
            return response()->json(['recovery_codes' => []]);
        }

        // Generate codes on-demand if none exist (e.g. seeded/legacy accounts)
        if (empty($mfa->recovery_codes)) {
            $codes = [];
            for ($i = 0; $i < 8; $i++) {
                $codes[] = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            }
            $mfa->forceFill(['recovery_codes' => $codes])->save();
        }

        return response()->json([
            'recovery_codes' => $mfa->recovery_codes,
        ]);
    }

    /** POST /settings/mfa/enable-email — enable Email OTP 2FA */
    public function enableEmail(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return back()->withErrors(['email_mfa' => '2FA is already active. Disable it first before switching methods.']);
        }

        // Generate and store a 6-digit OTP to verify user owns the email
        $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(10);

        $mfa = MfaToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'id'                  => 'mfa_' . Str::lower(Str::random(12)),
                'secret'              => Str::random(32), // placeholder — not used for email method
                'method'              => 'email',
                'email_otp_hash'      => Hash::make($otp),
                'email_otp_expires_at'=> $expires,
                'confirmed_at'        => null,
                'disabled_at'         => null,
            ]
        );

        // Send OTP email
        \App\Jobs\SendEmailJob::dispatch(
            'emails.auth.11-email-otp',
            [
                'recipient_name' => $user->display_name,
                'otp_code'       => $otp,
                'expires_at'     => $expires->format('g:i A'),
                'settings_url'   => rtrim(config('app.url'), '/') . '/' . ($user->role?->portal() ?? 'provider') . '/settings',
            ],
            $user->id,
        )->onQueue('email');

        return back()->with('success', 'Verification code sent to ' . $user->email . '. Enter it below to activate Email 2FA.');
    }

    /** POST /settings/mfa/verify-email — confirm email OTP, activate email 2FA */
    public function verifyEmail(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        /** @var User $user */
        $user = $request->user();
        $mfa  = $user->mfaToken;

        if (!$mfa || $mfa->method !== 'email') {
            return back()->withErrors(['code' => 'Email 2FA setup has not been initiated.']);
        }

        if (!$mfa->email_otp_expires_at || now()->gt($mfa->email_otp_expires_at)) {
            return back()->withErrors(['code' => 'Verification code has expired. Please request a new one.']);
        }

        if (!Hash::check($request->code, (string) $mfa->email_otp_hash)) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        $mfa->forceFill([
            'confirmed_at'         => now(),
            'disabled_at'          => null,
            'email_otp_hash'       => null,
            'email_otp_expires_at' => null,
        ])->save();

        $user->forceFill(['two_factor_enabled' => 1])->save();

        $this->activity->log(
            $user->id,
            $user->role?->portal() ?? 'provider',
            'account',
            \App\Enums\ActivitySeverity::Warning,
            'mfa_enabled',
            'Email 2FA enabled',
            'You enabled email-based two-factor authentication on your account.',
            null, null, null, 'log', $user->id,
        );

        return back()->with('success', 'Email two-factor authentication has been enabled.');
    }

    /** POST /settings/mfa/verify — confirm TOTP code, activate */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        /** @var User $user */
        $user = $request->user();
        $mfa  = $user->mfaToken;

        if (!$mfa || $mfa->secret === null) {
            return back()->withErrors(['code' => 'Two-factor setup has not been initiated.']);
        }

        if (!$this->verifyTotp($mfa->secret, $request->code)) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        $mfa->forceFill([
            'confirmed_at' => now(),
            'disabled_at'  => null,
        ])->save();

        $user->forceFill(['two_factor_enabled' => 1])->save();

        $this->activity->log(
            $user->id,
            $user->role?->portal() ?? 'provider',
            'account',
            \App\Enums\ActivitySeverity::Warning,
            'mfa_enabled',
            'Two-factor authentication enabled',
            'You enabled two-factor authentication (authenticator app) on your account.',
            null, null, null, 'log', $user->id,
        );

        return back()->with('success', 'Two-factor authentication has been enabled.');
    }

    /** POST /settings/mfa/disable */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        /** @var User $user */
        $user = $request->user();

        if (!Hash::check($request->password, (string) $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user->forceFill(['two_factor_enabled' => 0])->save();

        if ($user->mfaToken) {
            $user->mfaToken->forceFill(['disabled_at' => now()])->save();
        }

        $this->activity->log(
            $user->id,
            $user->role?->portal() ?? 'provider',
            'account',
            \App\Enums\ActivitySeverity::Warning,
            'mfa_disabled',
            'Two-factor authentication disabled',
            'Two-factor authentication was disabled on your account.',
            null, null, null, 'log', $user->id,
        );

        return back()->with('success', 'Two-factor authentication has been disabled.');
    }

    /** GET /mfa/challenge — session-gated challenge view */
    public function showChallenge(Request $request): Response|RedirectResponse
    {
        if (!$request->session()->has('mfa_pending_user_id')) {
            return redirect()->route('login');
        }

        $userId = $request->session()->get('mfa_pending_user_id');
        $user   = \App\Models\User::find($userId);
        $mfa    = $user?->mfaToken;

        // Determine method: if MFA token exists and is confirmed → totp, else email
        $mfaMethod = ($mfa && $mfa->confirmed_at && !$mfa->disabled_at)
            ? 'totp'
            : 'email';

        return Inertia::render('Auth/MfaChallenge', [
            'mfaMethod' => $mfaMethod,
        ]);
    }

    /** POST /mfa/challenge — verify code on login */
    public function challenge(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $userId = $request->session()->get('mfa_pending_user_id');

        if (!$userId) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        /** @var User|null $user */
        $user = User::find($userId);
        $mfa  = $user?->mfaToken;

        if (!$user || !$mfa || $mfa->secret === null || $mfa->disabled_at !== null) {
            $request->session()->forget(['mfa_pending_user_id', 'mfa_remember']);
            return redirect()->route('login')
                ->withErrors(['email' => 'Two-factor session is invalid.']);
        }

        if ($mfa->method === 'email') {
            // Email OTP verification
            if (!$mfa->email_otp_expires_at || now()->gt($mfa->email_otp_expires_at)) {
                return back()->withErrors(['code' => 'Your verification code has expired. Please sign in again to receive a new code.']);
            }
            if (!Hash::check($request->code, (string) $mfa->email_otp_hash)) {
                return back()->withErrors(['code' => 'Invalid code. Please check your email and try again.']);
            }
            // Consume — clear the OTP after use
            $mfa->forceFill(['email_otp_hash' => null, 'email_otp_expires_at' => null])->save();
        } else {
            // TOTP verification (authenticator app)
            if (!$this->verifyTotp($mfa->secret, $request->code)) {
                // TOTP failed — try backup/recovery codes
                if (!$this->consumeRecoveryCode($mfa, $request->code)) {
                    return back()->withErrors(['code' => 'Invalid authentication code. Try a backup code if you have lost access to your authenticator app.']);
                }
            }
        }

        $remember = (bool) $request->session()->pull('mfa_remember', false);
        $request->session()->forget('mfa_pending_user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->forceFill([
            'failed_login_count' => 0,
            'last_login_at'      => now(),
        ])->save();

        return redirect($this->portalHomeFor($user))
            ->with('success', 'Signed in successfully.');
    }

    // ── TOTP helpers (RFC 6238 / RFC 4226) ───────────────────────────────────

    /**
     * Check if the given code matches a recovery code and consume it (one-time use).
     */
    private function consumeRecoveryCode(MfaToken $mfa, string $code): bool
    {
        $codes = $mfa->recovery_codes ?? [];
        if (empty($codes)) {
            return false;
        }

        $code = trim($code);
        $remaining = [];
        $matched   = false;

        foreach ($codes as $stored) {
            if (!$matched && hash_equals((string) $stored, $code)) {
                $matched = true; // consume — do not add back
            } else {
                $remaining[] = $stored;
            }
        }

        if ($matched) {
            $mfa->forceFill(['recovery_codes' => $remaining])->save();
        }

        return $matched;
    }

    /**
     * Verify a 6-digit TOTP code against a base32 secret.
     * Allows ±1 time-step (30 s) drift to handle clock skew.
     */
    private function verifyTotp(string $secret, string $code): bool
    {
        $timestamp = (int) floor(time() / 30);

        foreach ([-1, 0, 1] as $offset) {
            if (hash_equals($this->generateTotp($secret, $timestamp + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a 6-digit TOTP code for a given counter value (RFC 4226 HOTP).
     */
    private function generateTotp(string $secret, int $counter): string
    {
        $key  = $this->base32Decode($secret);
        $msg  = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hash[19]) & 0xF;
        $code = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
             (ord($hash[$offset + 3]) & 0xFF)
        ) % 1_000_000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a base32 string to binary (RFC 4648, case-insensitive, no padding required).
     */
    private function base32Decode(string $input): string
    {
        $map   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));
        $output = '';
        $buffer = 0;
        $bits   = 0;

        foreach (str_split($input) as $char) {
            $pos = strpos($map, $char);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bits  += 5;
            if ($bits >= 8) {
                $bits   -= 8;
                $output .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $output;
    }

    /**
     * Generate a cryptographically random base32 TOTP secret (160-bit).
     */
    private function generateTotpSecret(): string
    {
        $map   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bytes = random_bytes(20);
        $bits  = '';

        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $secret = '';
        foreach (str_split($bits, 5) as $chunk) {
            $secret .= $map[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $secret;
    }

    private function portalHomeFor(User $user): string
    {
        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);

        return match ($role) {
            UserRole::Practitioner      => route('provider.dashboard'),
            UserRole::ContinuitySteward => route('cs.dashboard'),
            UserRole::SupportSteward    => route('ss.dashboard'),
            UserRole::BusinessPartner   => route('bp.dashboard'),
            UserRole::Admin             => route('admin.dashboard'),
            default                     => '/',
        };
    }
}
