<?php

declare(strict_types=1);

namespace App\Enums;

enum ConnectionType: string
{
    case Practitioner    = 'practitioner';
    case BusinessPartner = 'business_partner';

    public function label(): string
    {
        return match ($this) {
            self::Practitioner    => 'Practitioner',
            self::BusinessPartner => 'Business Partner',
        };
    }
}
