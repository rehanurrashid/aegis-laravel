<?php

declare(strict_types=1);

namespace App\Enums;

enum PractitionerPaymentStatus: string
{
    case Paid              = 'paid';
    case Failed            = 'failed';
    case Refunded          = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Pending           = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Paid              => 'Paid',
            self::Failed            => 'Failed',
            self::Refunded          => 'Refunded',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Pending           => 'Pending',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Paid              => 'green',
            self::Failed            => 'red',
            self::Refunded          => 'gray',
            self::PartiallyRefunded => 'amber',
            self::Pending           => 'amber',
        };
    }
}
