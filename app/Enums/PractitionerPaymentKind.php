<?php

declare(strict_types=1);

namespace App\Enums;

// Wave 1 — Clinical Services Overhaul
// Added service_session_deposit, service_session_balance, service_session_refund
// to support the two-charge (30%+70%) clinical session payment flow.

enum PractitionerPaymentKind: string
{
    case Subscription           = 'subscription';
    case MaatAddon              = 'maat_addon';
    case CsFee                  = 'cs_fee';
    case BpInvoice              = 'bp_invoice';
    case Refund                 = 'refund';
    case ServiceSession         = 'service_session';         // legacy — single-charge sessions
    case ServiceSessionDeposit  = 'service_session_deposit'; // 30% upfront charge
    case ServiceSessionBalance  = 'service_session_balance'; // 70% balance on completion
    case ServiceSessionRefund   = 'service_session_refund';  // refund record (negative)

    public function label(): string
    {
        return match ($this) {
            self::Subscription          => 'Subscription',
            self::MaatAddon             => 'MAAT Add-on',
            self::CsFee                 => 'CS Fee',
            self::BpInvoice             => 'BP Invoice',
            self::Refund                => 'Refund',
            self::ServiceSession        => 'Service Session',
            self::ServiceSessionDeposit => 'Session Deposit (30%)',
            self::ServiceSessionBalance => 'Session Balance (70%)',
            self::ServiceSessionRefund  => 'Session Refund',
        };
    }

    /** True for any clinical session-related kind. */
    public function isSessionKind(): bool
    {
        return in_array($this, [
            self::ServiceSession,
            self::ServiceSessionDeposit,
            self::ServiceSessionBalance,
            self::ServiceSessionRefund,
        ], true);
    }

    /** True when this kind represents money going TO a provider (not a refund). */
    public function isIncoming(): bool
    {
        return in_array($this, [
            self::ServiceSession,
            self::ServiceSessionDeposit,
            self::ServiceSessionBalance,
        ], true);
    }
}
