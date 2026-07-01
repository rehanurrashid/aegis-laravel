<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\UserTier;
use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /** Practice tier + services_mode flag + practitioner role */
    public function create(User $user): bool
    {
        if ($this->roleEnum($user) !== UserRole::Practitioner) {
            return false;
        }

        $tier = $user->tier instanceof UserTier
            ? $user->tier->value
            : (string) $user->tier;

        return $tier === 'practice' && (bool) $user->services_mode;
    }

    /** Any practitioner (not the owner) can request an active service */
    public function request(User $user, Service $service): bool
    {
        if ($this->roleEnum($user) !== UserRole::Practitioner) {
            return false;
        }

        if ($user->id === $service->practitioner_id) {
            return false;
        }

        return ($service->status instanceof \BackedEnum ? $service->status->value : (string) $service->status) === 'active';
    }

    /** Only the owner manages a service */
    public function manage(User $user, Service $service): bool
    {
        return $user->id === $service->practitioner_id;
    }

    private function roleEnum(User $user): ?UserRole
    {
        return $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);
    }
}
