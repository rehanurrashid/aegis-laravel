<?php
declare(strict_types=1);
namespace App\Enums;

enum UserTier: string
{
    case Access          = 'access';
    case Practice        = 'practice';
    case CsBusiness      = 'cs_business';
    case BusinessPartner = 'business_partner';

    public function label(): string
    {
        return match ($this) {
            self::Access          => 'Continuity Access',
            self::Practice        => 'Continuity Practice',
            self::CsBusiness      => 'Business CS',
            self::BusinessPartner => 'Business Partner',
        };
    }

    public function monthlyCents(): int
    {
        $key = match ($this) {
            self::Access          => 'practitioner.access.monthly_cents',
            self::Practice        => 'practitioner.practice.monthly_cents',
            self::CsBusiness      => 'continuity_steward_business.monthly_cents',
            self::BusinessPartner => 'business_partner.monthly_cents',
        };
        return (int) config("aegis.pricing.{$key}", 0);
    }

    public function annualTotalCents(): int
    {
        $key = match ($this) {
            self::Access          => 'practitioner.access.annual_total_cents',
            self::Practice        => 'practitioner.practice.annual_total_cents',
            self::CsBusiness      => 'continuity_steward_business.annual_total_cents',
            self::BusinessPartner => 'business_partner.annual_total_cents',
        };
        return (int) config("aegis.pricing.{$key}", 0);
    }
}
