<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContinuityPlan;
use App\Models\IncidentTask;
use App\Models\PlanTask;
use App\Models\User;

class PlanTaskPolicy
{
    /** Only the assigned steward can complete */
    public function complete(User $user, IncidentTask|PlanTask $task): bool
    {
        return $this->isAssignee($user, $task);
    }

    /** Only the assigned steward can flag */
    public function flag(User $user, IncidentTask|PlanTask $task): bool
    {
        return $this->isAssignee($user, $task);
    }

    /** Assigned steward OR plan owner can add a note */
    public function addNote(User $user, IncidentTask|PlanTask $task): bool
    {
        if ($this->isAssignee($user, $task)) {
            return true;
        }

        $planId = $this->planIdFor($task);
        if ($planId === null) {
            return false;
        }

        return ContinuityPlan::where('id', $planId)
            ->where('practitioner_id', $user->id)
            ->exists();
    }

    private function isAssignee(User $user, IncidentTask|PlanTask $task): bool
    {
        if ($task instanceof IncidentTask) {
            return $user->id === $task->assigned_to_id;
        }
        // PlanTask
        return $user->id === $task->steward_id;
    }

    private function planIdFor(IncidentTask|PlanTask $task): ?string
    {
        if ($task instanceof PlanTask) {
            return $task->plan_id;
        }
        // IncidentTask → incident → plan
        return $task->incident?->plan_id;
    }
}
