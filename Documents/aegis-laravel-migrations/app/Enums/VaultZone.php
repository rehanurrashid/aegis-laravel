<?php

declare(strict_types=1);

namespace App\Enums;

enum VaultZone: string
{
    case Credentials  = 'credentials';
    case Roster       = 'roster';
    case Documents    = 'documents';
    case Instructions = 'instructions';

    public function label(): string
    {
        return match ($this) {
            self::Credentials  => 'Secure Credentials',
            self::Roster       => 'Client Roster',
            self::Documents    => 'Documents',
            self::Instructions => 'Instructions',
        };
    }

    public function isEncrypted(): bool
    {
        return $this === self::Credentials;
    }
}
