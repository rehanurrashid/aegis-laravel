<?php

declare(strict_types=1);

namespace App\Enums;

enum VaultItemStatus: string
{
    case VaultOnly = 'vault_only';
    case Active    = 'active';
    case Priority  = 'priority';

    public function label(): string
    {
        return match ($this) {
            self::VaultOnly => 'Vault Only',
            self::Active    => 'Active',
            self::Priority  => 'Priority',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::VaultOnly => 'gray',
            self::Active    => 'green',
            self::Priority  => 'gold',
        };
    }
}
