<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamPermissionRole: string
{
    case Admin      = 'admin';
    case Manager    = 'manager';
    case Specialist = 'specialist';
    case Viewer     = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin      => 'Admin',
            self::Manager    => 'Manager',
            self::Specialist => 'Specialist',
            self::Viewer     => 'Viewer',
        };
    }
}
