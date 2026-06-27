<?php

declare(strict_types=1);

namespace App\Enums;

enum PractitionerPaymentKind: string
{
    case Subscription = 'subscription';
    case MaatAddon    = 'maat_addon';
    case CsFee        = 'cs_fee';
    case BpInvoice    = 'bp_invoice';
    case Refund       = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::Subscription => 'Subscription',
            self::MaatAddon    => 'MAAT Add-on',
            self::CsFee        => 'CS Fee',
            self::BpInvoice    => 'BP Invoice',
            self::Refund       => 'Refund',
        };
    }
}
