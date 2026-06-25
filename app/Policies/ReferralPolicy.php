<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Referral;
use App\Models\User;

class ReferralPolicy
{
    /** Only practitioners can send referrals */
    public function send(User $user): bool
    {
        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);

        return $role === UserRole::Practitioner;
    }

    /** Only the named recipient can respond */
    public function respond(User $user, Referral $referral): bool
    {
        return $user->id === $referral->recipient_id;
    }

    /** Either party can mark a referral complete */
    public function complete(User $user, Referral $referral): bool
    {
        return $user->id === $referral->sender_id
            || $user->id === $referral->recipient_id;
    }
}
