<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanStatus: string
{
    case Draft           = 'draft';
    case PendingReview   = 'pending_review';
    case Active          = 'active';
    case AnnualReviewDue = 'annual_review_due';
    case Expired         = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft           => 'Draft',
            self::PendingReview   => 'Pending Review',
            self::Active          => 'Active',
            self::AnnualReviewDue => 'Annual Review Due',
            self::Expired         => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft           => 'gray',
            self::PendingReview   => 'amber',
            self::Active          => 'green',
            self::AnnualReviewDue => 'amber',
            self::Expired         => 'red',
        };
    }

    public function isLive(): bool
    {
        return in_array($this, [self::Active, self::AnnualReviewDue], true);
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
