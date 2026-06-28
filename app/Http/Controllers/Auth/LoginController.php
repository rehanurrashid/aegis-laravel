<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Events\Auth\UserLoggedIn;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
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
            $request->session()->put('mfa_remember', true);
            return redirect()->route('mfa.challenge');
        }

        $request->session()->regenerate();

        Auth::login($user, true);

        $user->forceFill([
            'failed_login_count' => 0,
            'last_login_at'      => now(),
        ])->save();

        event(new UserLoggedIn($user, 'web'));

        // Track device; fires NewDeviceLogin event if this is a first-time device.
        app(AuthService::class)->trackDeviceLogin($user, $request);

        Log::info("{$trace} login complete", [
            'auth_check'   => Auth::check(),
            'auth_id'      => Auth::id(),
            'session_id'   => $request->session()->getId(),
            'session_keys' => array_keys($request->session()->all()),
        ]);

        return redirect($this->portalHomeFor($user))
            ->with('success', 'Signed in successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
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
