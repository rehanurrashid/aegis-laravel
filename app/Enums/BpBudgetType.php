<?php

declare(strict_types=1);

namespace App\Enums;

enum BpBudgetType: string
{
    case Fixed    = 'fixed';
    case Hourly   = 'hourly';
    case Retainer = 'retainer';

    public function label(): string
    {
        return match ($this) {
            self::Fixed    => 'Fixed Price',
            self::Hourly   => 'Hourly Rate',
            self::Retainer => 'Monthly Retainer',
        };
    }
}
