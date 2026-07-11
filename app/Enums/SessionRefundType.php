<?php

declare(strict_types=1);

namespace App\Enums;

// Wave 1 — Clinical Services Overhaul
// Which portion of a session payment the client is requesting back.
// Availability of each type depends on session payment_status:
//   - deposit_only  → available when payment_status IN (deposit_paid, paid, partially_refunded)
//   - balance_only  → available when payment_status IN (paid, partially_refunded)
//   - full          → available when payment_status IN (deposit_paid, paid)

enum SessionRefundType: string
{
    case DepositOnly  = 'deposit_only';
    case BalanceOnly  = 'balance_only';
    case Full         = 'full';

    public function label(): string
    {
        return match ($this) {
            self::DepositOnly  => 'Deposit only (30%)',
            self::BalanceOnly  => 'Balance only (70%)',
            self::Full         => 'Full refund (100%)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DepositOnly  => 'Refund only the booking deposit already paid.',
            self::BalanceOnly  => 'Refund only the session balance paid on completion.',
            self::Full         => 'Refund the full session amount including deposit and balance.',
        };
    }

    /**
     * Returns which refund types are available given the current payment status.
     *
     * @return self[]
     */
    public static function availableFor(ServiceSessionPaymentStatus $status): array
    {
        return match ($status) {
            ServiceSessionPaymentStatus::DepositPaid       => [self::DepositOnly],
            ServiceSessionPaymentStatus::Paid              => [self::DepositOnly, self::BalanceOnly, self::Full],
            ServiceSessionPaymentStatus::PartiallyRefunded => [self::DepositOnly, self::BalanceOnly],
            default                                         => [],
        };
    }
}
