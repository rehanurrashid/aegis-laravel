<?php

declare(strict_types=1);

namespace App\Enums;

enum VaultAccess: string
{
    case None     = 'none';
    case Metadata = 'metadata';
    case Scoped   = 'scoped';
    case Full     = 'full';

    public function label(): string
    {
        return match ($this) {
            self::None     => 'No Access',
            self::Metadata => 'Metadata Only',
            self::Scoped   => 'Scoped',
            self::Full     => 'Full Access',
        };
    }

    public function canReveal(): bool
    {
        return $this === self::Full;
    }

    public function canDownload(): bool
    {
        return in_array($this, [self::Scoped, self::Full], true);
    }
}
