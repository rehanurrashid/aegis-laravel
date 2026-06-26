<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Events\Auth\UserLoggedIn;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class LoginController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): SymfonyResponse
    {
        $trace = '[LOGIN:' . substr(md5((string) microtime(true)), 0, 6) . ']';

        $key = 'login.' . $request->ip() . '.' . strtolower((string) $request->email);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in '
                    . RateLimiter::availableIn($key) . ' seconds.',
            ]);
        }

        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();

        if ($user && $user->deactivated_at !== null) {
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Please contact support.',
            ]);
        }

        if ($user && $user->locked_at !== null) {
            throw ValidationException::withMessages([
                'email' => 'Your account has been locked. ' . ($user->locked_reason ?? 'Please contact support.'),
            ]);
        }

        if (!$user || !Hash::check($request->password, (string) $user->password)) {
            RateLimiter::hit($key, 60);

            if ($user) {
                $count = ($user->failed_login_count ?? 0) + 1;
                $user->forceFill(['failed_login_count' => $count])->save();

                if ($count >= 5) {
                    $user->forceFill([
                        'locked_at'     => now(),
                        'locked_reason' => 'Too many failed login attempts',
                    ])->save();
                }
            }

            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        RateLimiter::clear($key);

        $mfa       = $user->mfaToken;
        $mfaActive = $user->two_factor_enabled
            && $mfa !== null
            && $mfa->confirmed_at !== null
            && $mfa->disabled_at === null;

        if ($mfaActive) {
            $request->session()->put('mfa_pending_user_id', $user->id);
            $request->session()->put('mfa_remember', $request->boolean('remember'));
            return Inertia::location(route('mfa.challenge'));
        }

        // ── KEY CHANGE ────────────────────────────────────────────────────────
        // Auth::login() internally calls session()->migrate(true) which already
        // regenerates the session ID and preserves in-memory attributes.
        // Do NOT call regenerate() or save() — that triggers a double-save race
        // where the middleware terminate phase rewrites the session row with
        // stale attributes, wiping the login_web_* key.
        Auth::login($user, $request->boolean('remember'));

        Log::info("{$trace} after Auth::login", [
            'auth_check' => Auth::check(),
            'auth_id'    => Auth::id(),
            'session_id' => $request->session()->getId(),
            'session_keys' => array_keys($request->session()->all()),
        ]);

        $user->forceFill([
            'failed_login_count' => 0,
            'last_login_at'      => now(),
        ])->save();

        event(new UserLoggedIn($user, 'web'));

        return Inertia::location($this->portalHomeFor($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user && method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
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
            default                     => url('/'),
        };
    }
}
