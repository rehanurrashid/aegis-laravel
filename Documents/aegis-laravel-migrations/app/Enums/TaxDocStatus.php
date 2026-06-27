<?php

declare(strict_types=1);

namespace App\Enums;

enum TaxDocStatus: string
{
    case Available = 'available';
    case Pending   = 'pending';
    case Verified  = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Pending   => 'Pending',
            self::Verified  => 'Verified',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'blue',
            self::Pending   => 'amber',
            self::Verified  => 'green',
        };
    }
}
