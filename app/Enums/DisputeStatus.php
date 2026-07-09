<?php

declare(strict_types=1);

namespace App\Enums;

enum DisputeStatus: string
{
    case Open              = 'open';
    case AwaitingResponse  = 'awaiting_response';
    case UnderReview       = 'under_review';
    case Resolved          = 'resolved';
    case Closed            = 'closed_no_action';

    public function label(): string
    {
        return match ($this) {
            self::Open             => 'Open',
            self::AwaitingResponse => 'Awaiting response',
            self::UnderReview      => 'Under review',
            self::Resolved         => 'Resolved',
            self::Closed           => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open, self::AwaitingResponse => 'gold',
            self::UnderReview                  => 'blue',
            self::Resolved                     => 'green',
            self::Closed                       => 'gray',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::AwaitingResponse, self::UnderReview], true);
    }
}
