<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\IncidentStatus;
use App\Enums\PlanStatus;
use App\Enums\StewardStatus;
use App\Models\ContinuityPlan;
use App\Models\CriticalIncident;
use App\Models\PlanSteward;
use App\Models\User;
use App\Models\VaultItem;

class VaultPolicy
{
    /** Provider can see their own vault listing */
    public function view(User $user, VaultItem $item): bool
    {
        return $user->id === $item->practitioner_id;
    }

    /** CS may view vault contents only during an active incident */
    public function viewContents(User $user, ContinuityPlan $plan): bool
    {
        $isSteward = PlanSteward::where('plan_id', $plan->id)
            ->where('steward_id', $user->id)
            ->where('status', StewardStatus::Active->value)
            ->exists();

        if (!$isSteward) {
            return false;
        }

        return CriticalIncident::where('plan_id', $plan->id)
            ->where('status', IncidentStatus::Active->value)
            ->exists();
    }

    /** Provider can upload once their plan is signed */
    public function upload(User $user, ContinuityPlan $plan): bool
    {
        return $user->id === $plan->practitioner_id
            && $plan->signed_at !== null;
    }

    /** Only CS-category active stewards can download during an active incident */
    public function download(User $user, VaultItem $item): bool
    {
        $isCsSteward = PlanSteward::whereHas('plan', fn($q) =>
                $q->where('practitioner_id', $item->practitioner_id)
            )
            ->where('steward_id', $user->id)
            ->where('steward_category', 'continuity_steward')
            ->where('status', StewardStatus::Active->value)
            ->exists();

        if (!$isCsSteward) {
            return false;
        }

        return CriticalIncident::where('practitioner_id', $item->practitioner_id)
            ->where('status', IncidentStatus::Active->value)
            ->exists();
    }

    /** Provider attests their vault on an active plan */
    public function attest(User $user, ContinuityPlan $plan): bool
    {
        $status = $plan->status instanceof PlanStatus
            ? $plan->status
            : PlanStatus::tryFrom((string) $plan->status);

        return $user->id === $plan->practitioner_id
            && $status === PlanStatus::Active;
    }

    /** Provider can share vault items once a signed plan exists */
    public function share(User $user, VaultItem $item): bool
    {
        if ($user->id !== $item->practitioner_id) {
            return false;
        }

        return ContinuityPlan::where('practitioner_id', $user->id)
            ->whereNotNull('signed_at')
            ->exists();
    }

    public function delete(User $user, VaultItem $item): bool
    {
        return $user->id === $item->practitioner_id;
    }
}
