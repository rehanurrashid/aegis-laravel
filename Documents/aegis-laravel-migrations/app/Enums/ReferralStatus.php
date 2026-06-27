<?php

declare(strict_types=1);

namespace App\Enums;

enum ReferralStatus: string
{
    case Sent      = 'sent';
    case Accepted  = 'accepted';
    case Declined  = 'declined';
    case Closed    = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Sent      => 'Sent',
            self::Accepted  => 'Accepted',
            self::Declined  => 'Declined',
            self::Closed    => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Sent      => 'amber',
            self::Accepted  => 'green',
            self::Declined  => 'red',
            self::Closed    => 'gray',
            self::Cancelled => 'gray',
        };
    }
}
