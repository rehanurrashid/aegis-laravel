<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case New      = 'new';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New      => 'New',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New      => 'amber',
            self::Accepted => 'green',
            self::Declined => 'red',
        };
    }
}
