<?php

declare(strict_types=1);

namespace App\Enums;

enum BpJobStatus: string
{
    case Draft     = 'draft';
    case Open      = 'open';
    case Paused    = 'paused';
    case Closed    = 'closed';
    case Filled    = 'filled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Open      => 'Open',
            self::Paused    => 'Paused',
            self::Closed    => 'Closed',
            self::Filled    => 'Filled',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft     => 'gray',
            self::Open      => 'green',
            self::Paused    => 'amber',
            self::Closed    => 'gray',
            self::Filled    => 'blue',
            self::Cancelled => 'red',
        };
    }

    public function acceptsProposals(): bool
    {
        return $this === self::Open;
    }
}
