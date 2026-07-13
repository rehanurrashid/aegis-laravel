<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft               = 'draft';
    case PendingSign         = 'pending_sign';
    case Countersign         = 'countersign';
    case CountersignPending  = 'countersign_pending';
    case Active              = 'active';
    case FullyExecuted       = 'fully_executed';
    case Expiring            = 'expiring';
    case Expired             = 'expired';
    case ReleasePending      = 'release_pending';
    case Archived            = 'archived';
    case Terminated          = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Draft              => 'Draft',
            self::PendingSign        => 'Awaiting Signature',
            self::Countersign,
            self::CountersignPending => 'Awaiting Countersignature',
            self::Active,
            self::FullyExecuted      => 'Active',
            self::Expiring           => 'Expiring Soon',
            self::Expired            => 'Expired',
            self::ReleasePending     => 'Release Pending',
            self::Archived           => 'Archived',
            self::Terminated         => 'Terminated',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Active,
            self::FullyExecuted      => 'green',
            self::PendingSign        => 'gold',
            self::Countersign,
            self::CountersignPending => 'blue',
            self::Draft              => 'gray',
            self::Expiring           => 'orange',
            self::Expired,
            self::Terminated         => 'red',
            self::Archived           => 'gray',
            self::ReleasePending     => 'orange',
        };
    }
}
