<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PlanStatus;
use App\Enums\StewardStatus;
use App\Enums\UserRole;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\User;

class ContinuityPlanPolicy
{
    public function view(User $user, ContinuityPlan $plan): bool
    {
        if ($user->id === $plan->practitioner_id) {
            return true;
        }

        return PlanSteward::where('plan_id', $plan->id)
            ->where('steward_id', $user->id)
            ->where('status', StewardStatus::Active->value)
            ->exists();
    }

    public function create(User $user): bool
    {
        $role = $this->roleEnum($user);
        if ($role !== UserRole::Practitioner) {
            return false;
        }

        return ContinuityPlan::where('practitioner_id', $user->id)
            ->whereIn('status', [
                PlanStatus::Draft->value,
                PlanStatus::PendingReview->value,
                PlanStatus::Active->value,
                PlanStatus::AnnualReviewDue->value,
            ])
            ->doesntExist();
    }

    public function update(User $user, ContinuityPlan $plan): bool
    {
        return $user->id === $plan->practitioner_id;
    }

    public function annualReview(User $user, ContinuityPlan $plan): bool
    {
        return $user->id === $plan->practitioner_id;
    }

    public function sign(User $user, ContinuityPlan $plan): bool
    {
        $status = $plan->status instanceof PlanStatus
            ? $plan->status
            : PlanStatus::tryFrom((string) $plan->status);

        return $user->id === $plan->practitioner_id
            && in_array($status, [PlanStatus::Draft, PlanStatus::PendingReview], true);
    }

    public function attestVault(User $user, ContinuityPlan $plan): bool
    {
        return $user->id === $plan->practitioner_id;
    }

    public function delete(User $user, ContinuityPlan $plan): bool
    {
        $status = $plan->status instanceof PlanStatus
            ? $plan->status
            : PlanStatus::tryFrom((string) $plan->status);

        return $user->id === $plan->practitioner_id
            && $status === PlanStatus::Draft;
    }

    public function addTask(User $user, ContinuityPlan $plan): bool
    {
        return $user->id === $plan->practitioner_id;
    }

    private function roleEnum(User $user): ?UserRole
    {
        return $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);
    }
}
