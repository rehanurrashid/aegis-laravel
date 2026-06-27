<?php

declare(strict_types=1);

namespace App\Enums;

enum CheckinStatus: string
{
    case Ok          = 'ok';
    case Concern     = 'concern';
    case Unreachable = 'unreachable';

    public function label(): string
    {
        return match ($this) {
            self::Ok          => 'OK',
            self::Concern     => 'Concern',
            self::Unreachable => 'Unreachable',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ok          => 'green',
            self::Concern     => 'amber',
            self::Unreachable => 'red',
        };
    }
}
