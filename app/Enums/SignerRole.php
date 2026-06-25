<?php

declare(strict_types=1);

namespace App\Enums;

enum SignerRole: string
{
    case Practitioner      = 'practitioner';
    case ContinuitySteward = 'continuity_steward';

    public function label(): string
    {
        return match ($this) {
            self::Practitioner      => 'Practitioner',
            self::ContinuitySteward => 'Continuity Steward',
        };
    }
}
