<?php

declare(strict_types=1);

namespace App\Enums;

enum DisputeResolution: string
{
    case RefundFull             = 'refund_full';
    case RefundPartial          = 'refund_partial';
    case PayFull                = 'pay_full';
    case PayPartial             = 'pay_partial';
    case NoAction               = 'no_action';
    case StripeDisputeEscalated = 'stripe_dispute_escalated';

    public function label(): string
    {
        return match ($this) {
            self::RefundFull             => 'Full refund to disputer',
            self::RefundPartial          => 'Partial refund to disputer',
            self::PayFull                => 'Full payment to respondent',
            self::PayPartial             => 'Partial payment to respondent',
            self::NoAction               => 'No action (dispute dismissed)',
            self::StripeDisputeEscalated => 'Escalated to Stripe chargeback process',
        };
    }
}
