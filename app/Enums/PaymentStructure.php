<?php

declare(strict_types=1);

namespace App\Enums;

// Clinical Services Rev 4 — Wave 1
// Replaces the hardcoded 30/70 escrow-flavoured split with three configurable structures.
// Used on: services.default_payment_structure, service_requests.proposed_payment_structure,
//          service_sessions.payment_structure

enum PaymentStructure: string
{
    case FullUpfront      = 'full_upfront';
    case Split            = 'split';
    case FullOnCompletion = 'full_on_completion';
    // BP Support Services structures
    case PerMilestone  = 'per_milestone';
    case OnCompletion  = 'on_completion';

    /** Human-readable label for UI display. */
    public function label(): string
    {
        return match ($this) {
            self::FullUpfront      => 'Full Payment Upfront',
            self::Split            => 'Split Payment',
            self::FullOnCompletion => 'Pay After Session',
            self::PerMilestone     => 'Per Milestone',
            self::OnCompletion     => 'Pay on Completion',
        };
    }

    /**
     * Compact chip label shown on service tiles and invoice cards.
     *
     * @param  int  $pct  Upfront percentage (only used for Split)
     */
    public function chipLabel(int $pct = 30): string
    {
        return match ($this) {
            self::FullUpfront      => '100% upfront',
            self::Split            => "{$pct}% upfront + " . (100 - $pct) . '% completion',
            self::FullOnCompletion => 'Pay after session',
            self::PerMilestone     => 'Per milestone',
            self::OnCompletion     => 'Pay on completion',
        };
    }

    /** Short description for client-facing modal copy. */
    public function description(int $pct = 30): string
    {
        return match ($this) {
            self::FullUpfront      => 'Full payment is due before the session begins. Payment routes directly to the provider.',
            self::Split            => "{$pct}% is due now to confirm your session. The remaining " . (100 - $pct) . '% is charged after the session is confirmed complete.',
            self::FullOnCompletion => 'No payment is collected until the session is confirmed complete by both parties.',
            self::PerMilestone     => 'Payment is charged per milestone as each is approved. Funds route directly to the Business Partner.',
            self::OnCompletion     => 'No payment is collected until the contract is marked complete by the provider.',
        };
    }

    /** True when an upfront charge applies (structure requires payment before completion). */
    public function hasUpfrontCharge(): bool
    {
        return in_array($this, [self::FullUpfront, self::Split], true);
    }

    /** True when a completion charge applies. */
    public function hasCompletionCharge(): bool
    {
        return in_array($this, [self::Split, self::FullOnCompletion, self::OnCompletion], true);
    }
}
