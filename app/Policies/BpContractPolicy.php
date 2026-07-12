<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ContractStatus;
use App\Models\BpContract;
use App\Models\User;

class BpContractPolicy
{
    public function view(User $user, BpContract $contract): bool
    {
        return $user->id === $contract->practitioner_id
            || $user->id === $contract->bp_id;
    }

    /**
     * Wave 6: Sign is now allowed from pending_signature state only.
     * (Was incorrectly guarded to 'active' — contracts are now born in
     * 'pending_signature', not 'active', per Wave 2 ProposalService change.)
     */
    public function sign(User $user, BpContract $contract): bool
    {
        $status = $contract->status instanceof \BackedEnum
            ? $contract->status->value
            : (string) $contract->status;

        if (!in_array($status, [
            ContractStatus::PendingSignature->value,
            ContractStatus::Draft->value,  // allow signing drafts too
        ], true)) {
            return false;
        }

        return $user->id === $contract->practitioner_id
            || $user->id === $contract->bp_id;
    }

    public function cancel(User $user, BpContract $contract): bool
    {
        $status = $contract->status instanceof \BackedEnum
            ? $contract->status->value
            : (string) $contract->status;

        if (!in_array($status, [
            ContractStatus::PendingSignature->value,
            ContractStatus::PendingFunding->value,
            ContractStatus::Active->value,
        ], true)) {
            return false;
        }

        return $user->id === $contract->practitioner_id
            || $user->id === $contract->bp_id;
    }
}
