<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\IncidentStatus;
use App\Enums\StewardStatus;
use App\Models\ContinuityPlan;
use App\Models\CriticalIncident;
use App\Models\PlanSteward;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIncidentActive
{
    /**
     * Vault is sealed until:
     *   (a) an active critical_incident exists for this plan/practitioner, AND
     *   (b) the authenticated user is an active plan_steward on that plan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Vault is sealed.');
        }

        $planId         = $request->route('plan');
        $practitionerId = $request->route('provider') ?? $request->route('practitioner_id');

        // If we have a plan id, resolve the practitioner from it
        if ($planId !== null && $practitionerId === null) {
            $plan = ContinuityPlan::find($planId);
            $practitionerId = $plan?->practitioner_id;
        }

        if ($practitionerId === null && $planId === null) {
            abort(403, 'Vault is sealed — cannot resolve plan owner.');
        }

        // Active incident must exist
        $incidentQuery = CriticalIncident::where('status', IncidentStatus::Active->value);
        if ($planId !== null) {
            $incidentQuery->where('plan_id', $planId);
        } else {
            $incidentQuery->where('practitioner_id', $practitionerId);
        }

        if (!$incidentQuery->exists()) {
            abort(403, 'Vault is sealed — no active critical incident.');
        }

        // User must be an active steward on the plan
        $stewardQuery = PlanSteward::where('steward_id', $user->id)
            ->where('status', StewardStatus::Active->value);

        if ($planId !== null) {
            $stewardQuery->where('plan_id', $planId);
        } else {
            $stewardQuery->whereHas('plan', fn($q) => $q->where('practitioner_id', $practitionerId));
        }

        if (!$stewardQuery->exists()) {
            abort(403, 'You are not an assigned steward for this provider.');
        }

        return $next($request);
    }
}
