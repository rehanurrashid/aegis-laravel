<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Stripe = 'stripe';
    case Manual = 'manual';
    case Ach    = 'ach';
    case Card   = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Stripe => 'Stripe',
            self::Manual => 'Manual',
            self::Ach    => 'ACH Transfer',
            self::Card   => 'Card',
        };
    }
}
