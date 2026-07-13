<?php
declare(strict_types=1);
namespace App\Enums;

enum VaultZone: string
{
    case Standard    = 'standard';
    case Emergency   = 'emergency';
    case Credentials = 'credentials';
    case Roster      = 'roster';

    public function label(): string
    {
        return match ($this) {
            self::Standard    => 'All Documents',
            self::Emergency   => 'Sensitive Information',
            self::Credentials => 'System Access Credentials',
            self::Roster      => 'Client Roster',
        };
    }

    public function isEncrypted(): bool
    {
        return $this === self::Credentials;
    }
}
