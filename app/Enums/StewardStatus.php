<?php

declare(strict_types=1);

namespace App\Enums;

enum StewardStatus: string
{
    case Invited         = 'invited';
    case Active          = 'active';
    case Declined        = 'declined';
    case RequestIncoming = 'request_incoming';
    case Archived        = 'archived';
    case Pending         = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Invited         => 'Invited',
            self::Active          => 'Active',
            self::Declined        => 'Declined',
            self::RequestIncoming => 'Request Incoming',
            self::Archived        => 'Archived',
            self::Pending         => 'Pending',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Invited, self::Pending, self::RequestIncoming => 'amber',
            self::Active   => 'green',
            self::Declined => 'red',
            self::Archived => 'gray',
        };
    }

    public function isLive(): bool
    {
        return $this === self::Active;
    }
}
