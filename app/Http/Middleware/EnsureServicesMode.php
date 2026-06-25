<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserTier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureServicesMode
{
    /**
     * Gates /provider/services routes.
     * Requires: services_mode=1 on users AND tier='practice'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $tier = $user->tier instanceof UserTier
            ? $user->tier->value
            : (string) $user->tier;

        $hasTier = $tier === 'practice';
        $hasMode = (bool) $user->services_mode;

        if (!$hasTier || !$hasMode) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error'   => 'Services Mode requires the Continuity Practice plan.',
                    'upgrade' => true,
                ], 403);
            }

            return back()->with(
                'error',
                'Services Mode requires the Continuity Practice plan. Please upgrade in Settings.'
            );
        }

        return $next($request);
    }
}
