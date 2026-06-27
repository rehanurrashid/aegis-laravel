<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PackageOverride;
use App\Models\User;

class PackagePolicy
{
    /** Any authenticated user can read package definitions (for the pricing page). */
    public function view(?User $user, PackageOverride $package): bool
    {
        return true;
    }

    /** Only admins can mutate pricing/features/limits. */
    public function manage(User $user): bool
    {
        return $this->roleEnum($user) === UserRole::Admin;
    }

    public function update(User $user, PackageOverride $package): bool
    {
        return $this->manage($user);
    }

    public function setPrice(User $user, PackageOverride $package): bool
    {
        return $this->manage($user);
    }

    public function setFeature(User $user, PackageOverride $package): bool
    {
        return $this->manage($user);
    }

    public function setLimits(User $user, PackageOverride $package): bool
    {
        return $this->manage($user);
    }

    private function roleEnum(User $user): ?UserRole
    {
        return $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);
    }
}
