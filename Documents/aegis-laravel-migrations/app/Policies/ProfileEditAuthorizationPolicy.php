<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ProfileEditAuthorization;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;

class ProfileEditAuthorizationPolicy
{
    /**
     * Admin OR holder of 'users.propose_profile_edit' permission can propose.
     */
    public function propose(User $user, User $target): bool
    {
        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);

        if ($role === UserRole::Admin) {
            return true;
        }

        // Resolve role slugs assigned to user, then look for the permission
        $userRoleSlugs = $user->roleAssignments()
            ->pluck('role')
            ->map(fn($r) => $r instanceof UserRole ? $r->value : (string) $r)
            ->all();

        if (empty($userRoleSlugs)) {
            return false;
        }

        return RolePermission::whereIn('role', $userRoleSlugs)
            ->where('permission', 'users.propose_profile_edit')
            ->exists();
    }

    /** The target practitioner approves their own edit authorization */
    public function approve(User $user, ProfileEditAuthorization $auth): bool
    {
        return $user->id === ($auth->target_user_id ?? $auth->practitioner_id);
    }

    /** The target practitioner rejects */
    public function reject(User $user, ProfileEditAuthorization $auth): bool
    {
        return $user->id === ($auth->target_user_id ?? $auth->practitioner_id);
    }
}
