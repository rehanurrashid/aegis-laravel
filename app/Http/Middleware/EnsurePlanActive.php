<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\PlanStatus;
use App\Models\ContinuityPlan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanActive
{
    /**
     * Gates CS/SS routes that require a live continuity plan for the provider
     * named by {provider} (or {practitioner_id}) in the route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $practitionerId = $request->route('provider') ?? $request->route('practitioner_id');

        if ($practitionerId === null) {
            // Route doesn't scope a provider — defer to route-level auth
            return $next($request);
        }

        $exists = ContinuityPlan::where('practitioner_id', $practitionerId)
            ->whereIn('status', [
                PlanStatus::Active->value,
                PlanStatus::AnnualReviewDue->value,
            ])
            ->exists();

        if (!$exists) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'No active continuity plan found.'], 403);
            }
            abort(403, 'This provider does not have an active continuity plan.');
        }

        return $next($request);
    }
}
