<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate every portal route behind email verification.
 * If the user is not verified, redirect to the verification notice page
 * (which also triggers a resend if they haven't received one).
 */
class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->verified) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
