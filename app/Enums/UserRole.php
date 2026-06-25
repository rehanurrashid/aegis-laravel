<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Practitioner       = 'practitioner';
    case ContinuitySteward  = 'continuity_steward';
    case SupportSteward     = 'support_steward';
    case BusinessPartner    = 'business_partner';
    case Admin              = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Practitioner      => 'Practitioner',
            self::ContinuitySteward => 'Continuity Steward',
            self::SupportSteward    => 'Support Steward',
            self::BusinessPartner   => 'Business Partner',
            self::Admin             => 'Admin',
        };
    }

    /** Portal slug used in routes/URLs (note: practitioner -> 'provider') */
    public function portal(): string
    {
        return match ($this) {
            self::Practitioner      => 'provider',
            self::ContinuitySteward => 'cs',
            self::SupportSteward    => 'ss',
            self::BusinessPartner   => 'bp',
            self::Admin             => 'admin',
        };
    }

    public function routePrefix(): string
    {
        return '/' . $this->portal();
    }

    public function middleware(): string
    {
        return 'role:' . $this->value;
    }

    public static function fromPortal(string $portal): self
    {
        return match ($portal) {
            'provider' => self::Practitioner,
            'cs'       => self::ContinuitySteward,
            'ss'       => self::SupportSteward,
            'bp'       => self::BusinessPartner,
            'admin'    => self::Admin,
            default    => throw new \ValueError("Unknown portal: $portal"),
        };
    }
}
