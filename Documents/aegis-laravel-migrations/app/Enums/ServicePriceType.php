<?php

declare(strict_types=1);

namespace App\Enums;

enum ServicePriceType: string
{
    case Fixed   = 'fixed';
    case Hourly  = 'hourly';
    case Session = 'session';
    case Inquiry = 'inquiry';

    public function label(): string
    {
        return match ($this) {
            self::Fixed   => 'Fixed Price',
            self::Hourly  => 'Hourly Rate',
            self::Session => 'Per Session',
            self::Inquiry => 'Contact for Pricing',
        };
    }
}
