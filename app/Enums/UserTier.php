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
            self::Access           => 3900,   // $39/mo
            self::Practice         => 7900,   // $79/mo
            self::PracticeCsAddon  => 10400,  // $104/mo ($79+$25)
            self::PracticeBusiness => 10400,  // $104/mo ($79+$25)
            self::CsBusiness       => 4900,   // $49/mo
            self::BusinessPartner  => 6900,   // $69/mo
        };
    }

    public function annualTotalCents(): int
    {
        return match ($this) {
            self::Access           => 42900,  // $429/yr
            self::Practice         => 79000,  // $790/yr
            self::PracticeCsAddon  => 104000, // $1,040/yr
            self::PracticeBusiness => 104000, // $1,040/yr
            self::CsBusiness       => 49000,  // $490/yr
            self::BusinessPartner  => 69000,  // $690/yr
        };
    }
}
