<?php

declare(strict_types=1);

namespace App\Enums;

enum CsAccountType: string
{
    case Invited    = 'invited';
    case Business   = 'business';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Invited    => 'Invited',
            self::Business   => 'Business',
            self::Enterprise => 'Enterprise',
        };
    }
}
