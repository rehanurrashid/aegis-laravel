<?php

declare(strict_types=1);

namespace App\Enums;

enum ContractStatus: string
{
    case Draft            = 'draft';
    case PendingSignature = 'pending_signature';
    case PendingFunding   = 'pending_funding';
    case Active           = 'active';
    case Completed        = 'completed';
    case Cancelled        = 'cancelled';
    case Disputed         = 'disputed';

    public function label(): string
    {
        return match ($this) {
            self::Draft            => 'Draft',
            self::PendingSignature => 'Awaiting Signature',
            self::PendingFunding   => 'Awaiting Funding',
            self::Active           => 'Active',
            self::Completed        => 'Completed',
            self::Cancelled        => 'Cancelled',
            self::Disputed         => 'Disputed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft            => 'gray',
            self::PendingSignature => 'amber',
            self::PendingFunding   => 'blue',
            self::Active           => 'green',
            self::Completed        => 'blue',
            self::Cancelled        => 'red',
            self::Disputed         => 'red',
        };
    }

    /** Returns true if the contract is in a state that allows work to proceed. */
    public function isWorkable(): bool
    {
        return $this === self::Active;
    }

    /** Returns true if the contract can still be cancelled with a full refund. */
    public function isCancellable(): bool
    {
        return in_array($this, [
            self::PendingSignature,
            self::PendingFunding,
            self::Active,
        ], true);
    }
}
