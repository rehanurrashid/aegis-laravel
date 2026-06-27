<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StewardStatus;
use App\Models\ContinuityDocument;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\User;

class ContinuityDocumentPolicy
{
    /** Plan owner or active assigned steward can view */
    public function view(User $user, ContinuityDocument $doc): bool
    {
        if ($user->id === $doc->practitioner_id) {
            return true;
        }

        return PlanSteward::where('plan_id', $doc->plan_id)
            ->where('steward_id', $user->id)
            ->where('status', StewardStatus::Active->value)
            ->exists();
    }

    /** Plan owner signs their own document when status is pending_sign */
    public function sign(User $user, ContinuityDocument $doc): bool
    {
        return $user->id === $doc->practitioner_id
            && (string) $doc->status === 'pending_sign';
    }

    /** Active CS-category steward countersigns */
    public function countersign(User $user, ContinuityDocument $doc): bool
    {
        if ((string) $doc->status !== 'countersign_pending') {
            return false;
        }

        return PlanSteward::where('plan_id', $doc->plan_id)
            ->where('steward_id', $user->id)
            ->where('steward_category', 'continuity_steward')
            ->where('status', StewardStatus::Active->value)
            ->exists();
    }

    /** Plan owner archives */
    public function archive(User $user, ContinuityDocument $doc): bool
    {
        return $user->id === $doc->practitioner_id;
    }

    /** Plan owner can request a document from a steward */
    public function request(User $user, ContinuityPlan $plan): bool
    {
        return $user->id === $plan->practitioner_id;
    }
}
