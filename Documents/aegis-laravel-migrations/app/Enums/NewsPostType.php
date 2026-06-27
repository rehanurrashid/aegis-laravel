<?php

declare(strict_types=1);

namespace App\Enums;

enum NewsPostType: string
{
    case Post         = 'post';
    case Poll         = 'poll';
    case Announcement = 'announcement';

    public function label(): string
    {
        return match ($this) {
            self::Post         => 'Post',
            self::Poll         => 'Poll',
            self::Announcement => 'Announcement',
        };
    }
}
