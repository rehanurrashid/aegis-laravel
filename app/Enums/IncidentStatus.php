<?php

declare(strict_types=1);

namespace App\Enums;

enum IncidentStatus: string
{
    case Reported = 'reported';
    case Verified = 'verified';
    case Active   = 'active';
    case Closed   = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Reported',
            self::Verified => 'Verified',
            self::Active   => 'Active',
            self::Closed   => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Reported => 'amber',
            self::Verified => 'orange',
            self::Active   => 'red',
            self::Closed   => 'gray',
        };
    }

    public function unsealsVault(): bool
    {
        return $this === self::Active;
    }

    public function isOpen(): bool
    {
        return $this !== self::Closed;
    }
}
