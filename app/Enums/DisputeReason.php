<?php

declare(strict_types=1);

namespace App\Enums;

enum DisputeReason: string
{
    case NonDelivery        = 'non_delivery';
    case QualityIssue       = 'quality_issue';
    case UnauthorizedCharge = 'unauthorized_charge';
    case DuplicateCharge    = 'duplicate_charge';
    case WrongAmount        = 'wrong_amount';
    case Other              = 'other';

    public function label(): string
    {
        return match ($this) {
            self::NonDelivery        => 'Work not delivered',
            self::QualityIssue       => 'Quality issue',
            self::UnauthorizedCharge => 'Unauthorized charge',
            self::DuplicateCharge    => 'Duplicate charge',
            self::WrongAmount        => 'Wrong amount',
            self::Other              => 'Other',
        };
    }
}
