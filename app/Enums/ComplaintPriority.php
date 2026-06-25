<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintPriority: string
{
    case Low    = 'low';
    case Normal = 'normal';
    case High   = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Low',
            self::Normal => 'Normal',
            self::High   => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low    => 'gray',
            self::Normal => 'blue',
            self::High   => 'amber',
            self::Urgent => 'red',
        };
    }

    public function slaHours(): int
    {
        return match ($this) {
            self::Urgent => 4,
            self::High   => 24,
            self::Normal => 72,
            self::Low    => 168,
        };
    }
}
