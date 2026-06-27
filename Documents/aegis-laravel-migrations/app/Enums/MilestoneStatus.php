<?php

declare(strict_types=1);

namespace App\Enums;

enum MilestoneStatus: string
{
    case Pending   = 'pending';
    case Submitted = 'submitted';
    case Approved  = 'approved';
    case Rejected  = 'rejected';
    case Paid      = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending',
            self::Submitted => 'Submitted',
            self::Approved  => 'Approved',
            self::Rejected  => 'Rejected',
            self::Paid      => 'Paid',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending   => 'gray',
            self::Submitted => 'amber',
            self::Approved  => 'blue',
            self::Rejected  => 'red',
            self::Paid      => 'green',
        };
    }
}
