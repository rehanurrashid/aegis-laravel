<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\BpJob;
use App\Models\BpProposal;
use App\Models\User;

class BpJobPolicy
{
    public function create(User $user): bool
    {
        return $this->roleEnum($user) === UserRole::Practitioner;
    }

    public function close(User $user, BpJob $job): bool
    {
        return $user->id === $job->practitioner_id;
    }

    public function apply(User $user, BpJob $job): bool
    {
        if ($this->roleEnum($user) !== UserRole::BusinessPartner) {
            return false;
        }

        if ((string) $job->status !== 'open') {
            return false;
        }

        return BpProposal::where('job_id', $job->id)
            ->where('bp_id', $user->id)
            ->doesntExist();
    }

    private function roleEnum(User $user): ?UserRole
    {
        return $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);
    }
}
