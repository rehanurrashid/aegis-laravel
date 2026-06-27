<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\IncidentStatus;
use App\Enums\StewardStatus;
use App\Enums\UserRole;
use App\Models\ContinuityPlan;
use App\Models\CriticalIncident;
use App\Models\PlanSteward;
use App\Models\User;

class IncidentPolicy
{
    /** Provider on the plan OR an active SS-category steward can report */
    public function report(User $user, ContinuityPlan $plan): bool
    {
        if ($user->id === $plan->practitioner_id) {
            return true;
        }

        return PlanSteward::where('plan_id', $plan->id)
            ->where('steward_id', $user->id)
            ->where('steward_category', 'support_steward')
            ->where('status', StewardStatus::Active->value)
            ->exists();
    }

    /** Only an active CS-category steward verifies */
    public function verify(User $user, CriticalIncident $incident): bool
    {
        return PlanSteward::where('plan_id', $incident->plan_id)
            ->where('steward_id', $user->id)
            ->where('steward_category', 'continuity_steward')
            ->where('status', StewardStatus::Active->value)
            ->exists();
    }

    /** CS activates once status is verified */
    public function activate(User $user, CriticalIncident $incident): bool
    {
        $status = $incident->status instanceof IncidentStatus
            ? $incident->status
            : IncidentStatus::tryFrom((string) $incident->status);

        if ($status !== IncidentStatus::Verified) {
            return false;
        }

        return $this->verify($user, $incident);
    }

    /** CS closes once status is active */
    public function close(User $user, CriticalIncident $incident): bool
    {
        $status = $incident->status instanceof IncidentStatus
            ? $incident->status
            : IncidentStatus::tryFrom((string) $incident->status);

        if ($status !== IncidentStatus::Active) {
            return false;
        }

        return $this->verify($user, $incident);
    }

    /** Admins can see every incident */
    public function viewAll(User $user): bool
    {
        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);

        return $role === UserRole::Admin;
    }
}
