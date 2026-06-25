<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivitySeverity: string
{
    case Info     = 'info';
    case Warning  = 'warning';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Info     => 'Info',
            self::Warning  => 'Warning',
            self::Critical => 'Critical',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Info     => 'gray',
            self::Warning  => 'amber',
            self::Critical => 'red',
        };
    }

    public function cssVar(): string
    {
        return match ($this) {
            self::Info     => 'var(--text-3)',
            self::Warning  => 'var(--gold-dark)',
            self::Critical => 'var(--red)',
        };
    }
}
