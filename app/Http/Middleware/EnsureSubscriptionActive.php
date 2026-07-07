<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate paid-role portals behind an active Stripe subscription.
 *
 * Roles that require an active subscription:
 *   - Practitioner (access or practice tier)
 *   - Business Partner
 *   - Continuity Steward (cs_account_type = business only)
 *
 * Free roles (Invited CS, Support Steward) skip this check.
 * Admin skips this check.
 *
 * "Active" means: subscription exists with stripe_status IN
 *   (active, trialing, past_due)  — past_due gets a grace warning
 *   but is not locked out immediately.
 */
class EnsureSubscriptionActive
{
    private const ACTIVE_STATUSES = ['active', 'trialing', 'past_due'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Resolve role
        $role = $user->role instanceof UserRole
            ? $user->role->value
            : (string) $user->role;

        // Admin — always allow
        if ($role === 'admin') {
            return $next($request);
        }

        // Invited CS and SS — free accounts, no subscription required
        $csType = $user->cs_account_type instanceof \App\Enums\CsAccountType
            ? $user->cs_account_type->value
            : (string) ($user->cs_account_type ?? '');

        $needsSubscription =
            $role === 'practitioner' ||
            $role === 'business_partner' ||
            ($role === 'continuity_steward' && $csType === 'business');

        if (!$needsSubscription) {
            return $next($request);
        }

        // Check Cashier subscription
        $hasActive = false;
        try {
            $sub = $user->subscriptions()->where('type', 'default')->latest()->first();
            $hasActive = $sub && in_array($sub->stripe_status, self::ACTIVE_STATUSES, true);
        } catch (\Throwable $e) {
            // Cashier not installed or DB issue — fail open with log
            \Illuminate\Support\Facades\Log::warning('EnsureSubscriptionActive: could not check subscription', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return $next($request);
        }

        if (!$hasActive) {
            // Send them to plan selection to complete onboarding
            return redirect()->route('onboarding.plan')
                ->with('error', 'Please complete your subscription to access your portal.');
        }

        return $next($request);
    }
}
