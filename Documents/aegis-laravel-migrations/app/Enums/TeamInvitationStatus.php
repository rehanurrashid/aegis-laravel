<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamInvitationStatus: string
{
    case Pending  = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired  = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Pending',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Expired  => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending  => 'amber',
            self::Accepted => 'green',
            self::Declined => 'red',
            self::Expired  => 'gray',
        };
    }
}
