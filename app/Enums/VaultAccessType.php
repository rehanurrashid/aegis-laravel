<?php

declare(strict_types=1);

namespace App\Enums;

enum VaultAccessType: string
{
    case Reveal   = 'reveal';
    case Download = 'download';
    case Export   = 'export';
    case Share    = 'share';
    case View     = 'view';

    public function label(): string
    {
        return match ($this) {
            self::Reveal   => 'Reveal',
            self::Download => 'Download',
            self::Export   => 'Export',
            self::Share    => 'Share',
            self::View     => 'View',
        };
    }
}
