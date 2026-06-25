<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountLocked
{
    /** Routes that should NOT trigger the lock check (auth-flow + recovery) */
    private array $except = [
        'login', 'login.store', 'logout',
        'password.request', 'password.email',
        'password.reset', 'password.update',
        'mfa.challenge', 'mfa.challenge.store',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if ($routeName !== null && in_array($routeName, $this->except, true)) {
            return $next($request);
        }

        if ($user->locked_at !== null) {
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been locked. '
                    . ($user->locked_reason ?? 'Please contact support.'),
            ]);
        }

        if ($user->deactivated_at !== null) {
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated.',
            ]);
        }

        return $next($request);
    }
}
