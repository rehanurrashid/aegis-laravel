<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft          = 'draft';
    case Countersign    = 'countersign';
    case Active         = 'active';
    case Archived       = 'archived';
    case ReleasePending = 'release_pending';

    public function label(): string
    {
        return match ($this) {
            self::Draft          => 'Draft',
            self::Countersign    => 'Awaiting Countersignature',
            self::Active         => 'Active',
            self::Archived       => 'Archived',
            self::ReleasePending => 'Release Pending',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft          => 'gray',
            self::Countersign    => 'amber',
            self::Active         => 'green',
            self::Archived       => 'gray',
            self::ReleasePending => 'orange',
        };
    }
}
