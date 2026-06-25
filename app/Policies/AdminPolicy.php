<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class AdminPolicy
{
    /**
     * Short-circuit: non-admin → false; admin → null (continue to method).
     */
    public function before(User $user, string $ability): ?bool
    {
        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);

        if ($role !== UserRole::Admin) {
            return false;
        }

        return null;
    }

    public function manageUsers(User $user): bool       { return true; }
    public function managePackages(User $user): bool    { return true; }
    public function manageRoles(User $user): bool       { return true; }
    public function viewPayments(User $user): bool      { return true; }
    public function processRefund(User $user): bool     { return true; }
    public function releasePayout(User $user): bool     { return true; }
    public function manageComplaints(User $user): bool  { return true; }
    public function viewAuditLog(User $user): bool      { return true; }
    public function manageHelp(User $user): bool        { return true; }
}
