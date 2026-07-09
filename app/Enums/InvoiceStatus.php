<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft    = 'draft';
    case Sent     = 'sent';
    case Paid     = 'paid';
    case Overdue  = 'overdue';
    case Void     = 'void';
    case Disputed = 'disputed';

    public function label(): string
    {
        return match ($this) {
            self::Draft    => 'Draft',
            self::Sent     => 'Sent',
            self::Paid     => 'Paid',
            self::Overdue  => 'Overdue',
            self::Void     => 'Void',
            self::Disputed => 'Disputed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft    => 'gray',
            self::Sent     => 'blue',
            self::Paid     => 'green',
            self::Overdue  => 'red',
            self::Void     => 'gray',
            self::Disputed => 'gold',
        };
    }

    public function isCollectible(): bool
    {
        return in_array($this, [self::Sent, self::Overdue], true);
    }

    public function isPayable(): bool
    {
        // A payer can call the pay endpoint iff the invoice is Sent or Overdue.
        // Disputed invoices are frozen until dispute is resolved.
        return $this->isCollectible();
    }
}
