<?php

declare(strict_types=1);

namespace App\Enums;

enum TaxDocType: string
{
    case W9     = 'w9';
    case Form1099 = '1099';
    case EinDoc = 'ein_doc';
    case Other  = 'other';

    public function label(): string
    {
        return match ($this) {
            self::W9       => 'W-9',
            self::Form1099 => '1099',
            self::EinDoc   => 'EIN Document',
            self::Other    => 'Other',
        };
    }
}
