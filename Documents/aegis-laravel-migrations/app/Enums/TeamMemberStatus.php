<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamMemberStatus: string
{
    case Active   = 'active';
    case Idle     = 'idle';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Active',
            self::Idle     => 'Idle',
            self::Inactive => 'Inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active   => 'green',
            self::Idle     => 'amber',
            self::Inactive => 'gray',
        };
    }
}
