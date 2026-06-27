<?php

declare(strict_types=1);

namespace App\Enums;

enum PayoutStatus: string
{
    case Pending   = 'pending';
    case InTransit = 'in_transit';
    case Paid      = 'paid';
    case Failed    = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending',
            self::InTransit => 'In Transit',
            self::Paid      => 'Paid',
            self::Failed    => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending   => 'gray',
            self::InTransit => 'blue',
            self::Paid      => 'green',
            self::Failed    => 'red',
            self::Cancelled => 'gray',
        };
    }
}
