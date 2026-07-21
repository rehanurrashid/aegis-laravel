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
    case ServiceSessionDeposit    = 'service_session_deposit';    // @deprecated Rev 4 — use ServiceSessionUpfront
    case ServiceSessionBalance    = 'service_session_balance';    // @deprecated Rev 4 — use ServiceSessionCompletion
    case ServiceSessionUpfront    = 'service_session_upfront';    // Rev 4: upfront portion (any %)
    case ServiceSessionCompletion = 'service_session_completion'; // Rev 4: completion portion
    case ServiceSessionRefund     = 'service_session_refund';     // refund record (negative)

    public function label(): string
    {
        return match ($this) {
            self::Subscription          => 'Subscription',
            self::MaatAddon             => 'MAAT Add-on',
            self::CsFee                 => 'CS Fee',
            self::BpInvoice             => 'BP Invoice',
            self::Refund                => 'Refund',
            self::ServiceSession        => 'Service Session',
            self::ServiceSessionDeposit    => 'Session Deposit (30%)',        // legacy
            self::ServiceSessionBalance    => 'Session Balance (70%)',        // legacy
            self::ServiceSessionUpfront    => 'Session Upfront Payment',
            self::ServiceSessionCompletion => 'Session Completion Payment',
            self::ServiceSessionRefund     => 'Session Refund',
        };
    }

    /** True for any clinical session-related kind. */
    public function isSessionKind(): bool
    {
        return in_array($this, [
            self::ServiceSession,
            self::ServiceSessionDeposit,
            self::ServiceSessionBalance,
            self::ServiceSessionUpfront,
            self::ServiceSessionCompletion,
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
            self::ServiceSessionUpfront,
            self::ServiceSessionCompletion,
        ], true);
    }
}
