<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case New       = 'new';
    case Countered = 'countered';
    case Accepted  = 'accepted';
    case Declined  = 'declined';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::New       => 'New',
            self::Countered => 'Counter Sent',
            self::Accepted  => 'Accepted',
            self::Declined  => 'Declined',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New       => 'amber',
            self::Countered => 'blue',
            self::Accepted  => 'green',
            self::Declined  => 'red',
            self::Withdrawn => 'neutral',
        };
    }
}
