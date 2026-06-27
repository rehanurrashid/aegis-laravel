<?php

declare(strict_types=1);

namespace App\Enums;

/** activity_events.portal — note 'provider' rather than 'practitioner' (per migration ENUM). */
enum ActivityPortal: string
{
    case Provider          = 'provider';
    case ContinuitySteward = 'continuity_steward';
    case SupportSteward    = 'support_steward';
    case BusinessPartner   = 'business_partner';
    case Admin             = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Provider          => 'Provider',
            self::ContinuitySteward => 'Continuity Steward',
            self::SupportSteward    => 'Support Steward',
            self::BusinessPartner   => 'Business Partner',
            self::Admin             => 'Admin',
        };
    }

    public static function fromUserRole(UserRole $role): self
    {
        return match ($role) {
            UserRole::Practitioner      => self::Provider,
            UserRole::ContinuitySteward => self::ContinuitySteward,
            UserRole::SupportSteward    => self::SupportSteward,
            UserRole::BusinessPartner   => self::BusinessPartner,
            UserRole::Admin             => self::Admin,
        };
    }
}
