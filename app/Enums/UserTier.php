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
        if ($this === self::PracticeBusiness || $this === self::PracticeCsAddon) {
            return (int) config('aegis.pricing.practitioner.practice.monthly_cents', 0)
                 + (int) config('aegis.pricing.practitioner.practice_business.monthly_cents', 0);
        }
        $key = match ($this) {
            self::Access           => 'practitioner.access.monthly_cents',
            self::Practice         => 'practitioner.practice.monthly_cents',
            self::CsBusiness       => 'continuity_steward_business.monthly_cents',
            self::BusinessPartner  => 'business_partner.monthly_cents',
            default                => null,
        };
        return $key ? (int) config("aegis.pricing.{$key}", 0) : 0;
    }

    public function annualTotalCents(): int
    {
        if ($this === self::PracticeBusiness || $this === self::PracticeCsAddon) {
            return (int) config('aegis.pricing.practitioner.practice.annual_total_cents', 0)
                 + (int) config('aegis.pricing.practitioner.practice_business.annual_total_cents', 0);
        }
        $key = match ($this) {
            self::Access           => 'practitioner.access.annual_total_cents',
            self::Practice         => 'practitioner.practice.annual_total_cents',
            self::CsBusiness       => 'continuity_steward_business.annual_total_cents',
            self::BusinessPartner  => 'business_partner.annual_total_cents',
            default                => null,
        };
        return $key ? (int) config("aegis.pricing.{$key}", 0) : 0;
    }
}
