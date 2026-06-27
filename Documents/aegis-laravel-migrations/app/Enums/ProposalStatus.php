<?php

declare(strict_types=1);

namespace App\Enums;

enum ProposalStatus: string
{
    case Pending     = 'pending';
    case UnderReview = 'under_review';
    case Accepted    = 'accepted';
    case Declined    = 'declined';
    case Withdrawn   = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Pending     => 'Pending',
            self::UnderReview => 'Under Review',
            self::Accepted    => 'Accepted',
            self::Declined    => 'Declined',
            self::Withdrawn   => 'Withdrawn',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending     => 'amber',
            self::UnderReview => 'blue',
            self::Accepted    => 'green',
            self::Declined    => 'red',
            self::Withdrawn   => 'gray',
        };
    }
}
