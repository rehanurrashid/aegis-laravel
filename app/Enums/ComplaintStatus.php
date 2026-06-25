<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintStatus: string
{
    case Open       = 'open';
    case InProgress = 'in_progress';
    case Resolved   = 'resolved';
    case Closed     = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open       => 'Open',
            self::InProgress => 'In Progress',
            self::Resolved   => 'Resolved',
            self::Closed     => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open       => 'red',
            self::InProgress => 'amber',
            self::Resolved   => 'green',
            self::Closed     => 'gray',
        };
    }
}
