<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Draft    = 'draft';
    case Paused   = 'paused';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Active',
            self::Inactive => 'Inactive',
            self::Draft    => 'Draft',
            self::Paused   => 'Paused',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active             => 'green',
            self::Paused             => 'gold',
            self::Draft              => 'neutral',
            self::Inactive,
            self::Archived           => 'neutral',
        };
    }
}
