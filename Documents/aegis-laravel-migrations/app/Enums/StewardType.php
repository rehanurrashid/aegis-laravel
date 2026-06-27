<?php

declare(strict_types=1);

namespace App\Enums;

/** Short-form steward type used by provider_checkins.steward_type (added in migration 072). */
enum StewardType: string
{
    case Cs = 'cs';
    case Ss = 'ss';

    public function label(): string
    {
        return match ($this) {
            self::Cs => 'Continuity Steward',
            self::Ss => 'Support Steward',
        };
    }
}
