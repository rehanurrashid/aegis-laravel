<?php

declare(strict_types=1);

namespace App\Enums;

enum BpType: string
{
    case Agency     = 'agency';
    case Freelancer = 'freelancer';

    public function label(): string
    {
        return match ($this) {
            self::Agency     => 'Agency',
            self::Freelancer => 'Freelancer',
        };
    }
}
