<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BpContract;
use App\Models\User;

class BpContractPolicy
{
    public function view(User $user, BpContract $contract): bool
    {
        return $user->id === $contract->practitioner_id
            || $user->id === $contract->bp_id;
    }

    public function sign(User $user, BpContract $contract): bool
    {
        $status = $contract->status instanceof \BackedEnum
            ? $contract->status->value
            : (string) $contract->status;

        if ($status !== 'active') {
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

        if (!in_array($status, ['active', 'paused'], true)) {
            return false;
        }

        return $user->id === $contract->practitioner_id
            || $user->id === $contract->bp_id;
    }
}
