<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Subscription policy operates on the User model (the subscription owner)
 * — there is no separate Subscription model; tier/status live on users + Cashier.
 */
class SubscriptionPolicy
{
    /** A user can view their own subscription; admins can view any. */
    public function view(User $user, User $target): bool
    {
        return $user->id === $target->id || $this->roleEnum($user) === UserRole::Admin;
    }

    /** Only the account holder can change their own tier. */
    public function changeTier(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

    /** Only the account holder can cancel; admins can cancel on behalf. */
    public function cancel(User $user, User $target): bool
    {
        return $user->id === $target->id || $this->roleEnum($user) === UserRole::Admin;
    }

    /** Only the account holder can toggle add-ons (e.g. services_mode). */
    public function toggleAddOn(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

    private function roleEnum(User $user): ?UserRole
    {
        return $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);
    }
}
