<?php

declare(strict_types=1);

namespace App\Enums;

// Wave 1 — Clinical Services Overhaul
// Tracks money movement through a clinical session independently of the
// session lifecycle status (scheduled/completed/cancelled/no_show).
//
// State machine:
//   unpaid → deposit_paid → paid
//                        ↘ partially_refunded
//          ↘ refunded (if deposit refunded before session)
//            (paid or partially_refunded) → refunded | partially_refunded

enum ServiceSessionPaymentStatus: string
{
    case Unpaid             = 'unpaid';
    case DepositPaid        = 'deposit_paid';
    case Paid               = 'paid';
    case Refunded           = 'refunded';
    case PartiallyRefunded  = 'partially_refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid            => 'Deposit Due',
            self::DepositPaid       => 'Balance Due',
            self::Paid              => 'Paid',
            self::Refunded          => 'Refunded',
            self::PartiallyRefunded => 'Partially Refunded',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Unpaid            => 'gold',
            self::DepositPaid       => 'blue',
            self::Paid              => 'green',
            self::Refunded          => 'neutral',
            self::PartiallyRefunded => 'neutral',
        };
    }

    /** True when client still owes money. */
    public function hasPendingBalance(): bool
    {
        return in_array($this, [self::Unpaid, self::DepositPaid], true);
    }

    /** True when at least the deposit has been charged. */
    public function depositCharged(): bool
    {
        return in_array($this, [
            self::DepositPaid,
            self::Paid,
            self::Refunded,
            self::PartiallyRefunded,
        ], true);
    }

    /** True when the full session amount has been charged. */
    public function fullyCharged(): bool
    {
        return in_array($this, [self::Paid, self::Refunded, self::PartiallyRefunded], true);
    }

    /** True when any refund has been issued. */
    public function hasRefund(): bool
    {
        return in_array($this, [self::Refunded, self::PartiallyRefunded], true);
    }
}
