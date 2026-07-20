<?php
declare(strict_types=1);
namespace App\Enums;

enum UserTier: string
{
    case Access           = 'access';
    case Practice         = 'practice';
    case PracticeCsAddon  = 'practice_cs_addon';
    case PracticeBusiness = 'practice_business';
    case CsBusiness       = 'cs_business';
    case BusinessPartner  = 'business_partner';

    public function label(): string
    {
        return match ($this) {
            self::Access           => 'Continuity Access',
            self::Practice         => 'Continuity Practice',
            self::PracticeCsAddon  => 'Continuity Practice + CS Add-On',
            self::PracticeBusiness => 'Continuity Practice Business',
            self::CsBusiness       => 'Business CS',
            self::BusinessPartner  => 'Business Partner',
        };
    }

    public function monthlyCents(): int
    {
        return match ($this) {
            self::Access           => 2900,
            self::Practice         => 4900,
            self::PracticeCsAddon  => 7400,
            self::PracticeBusiness => 7400,
            self::CsBusiness       => 4900,
            self::BusinessPartner  => 6900,
        };
    }

    public function annualTotalCents(): int
    {
        return match ($this) {
            self::Access           => 27600,
            self::Practice         => 46800,
            self::PracticeCsAddon  => 71800,
            self::PracticeBusiness => 71800,
            self::CsBusiness       => 42900,
            self::BusinessPartner  => 69000,
        };
    }
}
