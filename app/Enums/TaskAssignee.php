<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Long-form steward type used by plan_tasks.assigned_to and incident_tasks.assigned_role.
 * Distinct from StewardType (short form cs|ss) because migration ENUM values differ.
 */
enum TaskAssignee: string
{
    case ContinuitySteward = 'continuity_steward';
    case SupportSteward    = 'support_steward';

    public function label(): string
    {
        return match ($this) {
            self::ContinuitySteward => 'Continuity Steward',
            self::SupportSteward    => 'Support Steward',
        };
    }

    public function toShort(): StewardType
    {
        return match ($this) {
            self::ContinuitySteward => StewardType::Cs,
            self::SupportSteward    => StewardType::Ss,
        };
    }
}
