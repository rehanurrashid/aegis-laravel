<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function submit(User $user): bool
    {
        return true; // Any authenticated user
    }

    public function view(User $user, Complaint $complaint): bool
    {
        return $user->id === $complaint->submitter_id
            || $this->isAdmin($user);
    }

    public function reply(User $user, Complaint $complaint): bool
    {
        return $this->isAdmin($user);
    }

    public function close(User $user, Complaint $complaint): bool
    {
        return $user->id === $complaint->submitter_id
            || $this->isAdmin($user);
    }

    private function isAdmin(User $user): bool
    {
        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);

        return $role === UserRole::Admin;
    }
}
