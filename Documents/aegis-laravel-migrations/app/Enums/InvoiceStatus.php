<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft   = 'draft';
    case Sent    = 'sent';
    case Paid    = 'paid';
    case Overdue = 'overdue';
    case Void    = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft   => 'Draft',
            self::Sent    => 'Sent',
            self::Paid    => 'Paid',
            self::Overdue => 'Overdue',
            self::Void    => 'Void',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft   => 'gray',
            self::Sent    => 'blue',
            self::Paid    => 'green',
            self::Overdue => 'red',
            self::Void    => 'gray',
        };
    }

    public function isCollectible(): bool
    {
        return in_array($this, [self::Sent, self::Overdue], true);
    }
}
