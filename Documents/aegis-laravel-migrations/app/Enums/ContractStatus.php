<?php

declare(strict_types=1);

namespace App\Enums;

enum ContractStatus: string
{
    case Draft     = 'draft';
    case Active    = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Active    => 'Active',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft     => 'gray',
            self::Active    => 'green',
            self::Completed => 'blue',
            self::Cancelled => 'red',
        };
    }
}
