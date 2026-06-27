<?php

declare(strict_types=1);

namespace App\Enums;

enum IncidentUpdateType: string
{
    case Reported       = 'reported';
    case Verified       = 'verified';
    case Activated      = 'activated';
    case VaultUnsealed  = 'vault_unsealed';
    case SsNotified     = 'ss_notified';
    case TaskAdded      = 'task_added';
    case Escalated      = 'escalated';
    case Closed         = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Reported      => 'Reported',
            self::Verified      => 'Verified',
            self::Activated     => 'Activated',
            self::VaultUnsealed => 'Vault Unsealed',
            self::SsNotified    => 'Support Steward Notified',
            self::TaskAdded     => 'Task Added',
            self::Escalated     => 'Escalated',
            self::Closed        => 'Closed',
        };
    }
}
