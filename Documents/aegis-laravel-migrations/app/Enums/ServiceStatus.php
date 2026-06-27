<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    public function color(): string
    {
        return $this === self::Active ? 'green' : 'gray';
    }
}
