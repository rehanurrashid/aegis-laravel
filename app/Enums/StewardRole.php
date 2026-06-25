<?php

declare(strict_types=1);

namespace App\Enums;

enum StewardRole: string
{
    case Primary   = 'primary';
    case Alternate = 'alternate';
    case Support   = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Primary   => 'Primary',
            self::Alternate => 'Alternate',
            self::Support   => 'Support',
        };
    }
}
