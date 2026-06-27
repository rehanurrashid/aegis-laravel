<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\NetworkConnection;
use App\Models\NetworkRequest;
use App\Models\User;

class NetworkConnectionPolicy
{
    /** Either party to the connection can view it. */
    public function view(User $user, NetworkConnection $conn): bool
    {
        return $user->id === $conn->user_a_id || $user->id === $conn->user_b_id;
    }

    /** Practitioners and stewards can send connection requests. */
    public function request(User $user): bool
    {
        $role = $this->roleEnum($user);
        return in_array($role, [
            UserRole::Practitioner,
            UserRole::ContinuitySteward,
            UserRole::SupportSteward,
        ], true);
    }

    /** Only the receiver of a pending request can accept/decline it. */
    public function respond(User $user, NetworkRequest $req): bool
    {
        return $user->id === $req->to_user_id && $req->status === 'pending';
    }

    /** Either party may sever a connection. */
    public function disconnect(User $user, NetworkConnection $conn): bool
    {
        return $this->view($user, $conn);
    }

    private function roleEnum(User $user): ?UserRole
    {
        return $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);
    }
}
