<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\MfaToken;
use App\Models\User;
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
    /** POST /settings/mfa/enable — generate secret + provisioning URI (unconfirmed) */
    public function enable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return response()->json(['message' => 'Two-factor authentication is already enabled.'], 422);
        }

        $secret = $this->generateTotpSecret();

        MfaToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'id'           => 'mfa_' . Str::lower(Str::random(12)),
                'secret'       => $secret,
                'confirmed_at' => null,
                'disabled_at'  => null,
            ]
        );

        $provisioningUri = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode('Aegis'),
            rawurlencode($user->email),
            $secret,
            rawurlencode('Aegis')
        );

        return response()->json([
            'secret'           => $secret,
            'provisioning_uri' => $provisioningUri,
        ]);
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

        return back()->with('success', 'Two-factor authentication has been disabled.');
    }

    /** GET /mfa/challenge — session-gated challenge view */
    public function showChallenge(Request $request): Response|RedirectResponse
    {
        if (!$request->session()->has('mfa_pending_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/MfaChallenge');
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

        if (!$this->verifyTotp($mfa->secret, $request->code)) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
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
