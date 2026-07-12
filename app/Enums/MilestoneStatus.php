<?php

declare(strict_types=1);

namespace App\Enums;

enum MilestoneStatus: string
{
    case Pending            = 'pending';           // legacy / created, not yet funded
    case PendingFunding     = 'pending_funding';   // contract active but milestone not yet funded
    case Funded             = 'funded';            // escrow charge collected, work can begin
    case InProgress         = 'in_progress';       // alias of funded — explicit work-started state
    case Submitted          = 'submitted';         // BP submitted work; awaiting provider review
    case RevisionRequested  = 'revision_requested';// provider sent back for changes
    case Approved           = 'approved';          // provider approved; transfer pending
    case Released           = 'released';          // Stripe transfer sent to BP Connect
    case Disputed           = 'disputed';          // dispute opened; funds frozen
    case Refunded           = 'refunded';          // escrow refunded to provider
    case Paid               = 'paid';              // legacy alias of released

    public function label(): string
    {
        return match ($this) {
            self::Pending           => 'Pending',
            self::PendingFunding    => 'Awaiting Funding',
            self::Funded            => 'Funded',
            self::InProgress        => 'In Progress',
            self::Submitted         => 'Under Review',
            self::RevisionRequested => 'Revision Requested',
            self::Approved          => 'Approved',
            self::Released          => 'Paid',
            self::Disputed          => 'Disputed',
            self::Refunded          => 'Refunded',
            self::Paid              => 'Paid',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending           => 'gray',
            self::PendingFunding    => 'gray',
            self::Funded            => 'blue',
            self::InProgress        => 'blue',
            self::Submitted         => 'amber',
            self::RevisionRequested => 'amber',
            self::Approved          => 'green',
            self::Released          => 'green',
            self::Disputed          => 'red',
            self::Refunded          => 'gray',
            self::Paid              => 'green',
        };
    }

    /** Returns true if the BP can submit/resubmit work. */
    public function isSubmittable(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Funded,
            self::InProgress,
            self::RevisionRequested,
        ], true);
    }

    /** Returns true if funds are currently held in Aegis escrow. */
    public function isEscrowHeld(): bool
    {
        return in_array($this, [
            self::Funded,
            self::InProgress,
            self::Submitted,
            self::RevisionRequested,
            self::Approved,
            self::Disputed,
        ], true);
    }
}
