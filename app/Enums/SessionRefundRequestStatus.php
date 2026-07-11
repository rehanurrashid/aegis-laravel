<?php

declare(strict_types=1);

namespace App\Enums;

// Wave 1 — Clinical Services Overhaul
// Status lifecycle for a client-initiated session refund request.
//
// State machine:
//   pending_review → approved        (provider approves; Stripe refund issued)
//                 → denied           (provider denies; client may escalate)
//                 → auto_approved    (system auto-approves per platform policy)
//   denied → escalated_to_dispute    (client escalates to formal dispute system)

enum SessionRefundRequestStatus: string
{
    case PendingReview       = 'pending_review';
    case Approved            = 'approved';
    case Denied              = 'denied';
    case AutoApproved        = 'auto_approved';
    case EscalatedToDispute  = 'escalated_to_dispute';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview      => 'Pending Review',
            self::Approved           => 'Approved',
            self::Denied             => 'Denied',
            self::AutoApproved       => 'Auto-Approved',
            self::EscalatedToDispute => 'Escalated to Dispute',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::PendingReview      => 'gold',
            self::Approved           => 'green',
            self::Denied             => 'red',
            self::AutoApproved       => 'green',
            self::EscalatedToDispute => 'red',
        };
    }

    /** Can the provider still take action? */
    public function isActionable(): bool
    {
        return $this === self::PendingReview;
    }

    /** Can the client escalate to a dispute? */
    public function canEscalate(): bool
    {
        return $this === self::Denied;
    }

    /** Has money moved back to the client? */
    public function refundIssued(): bool
    {
        return in_array($this, [self::Approved, self::AutoApproved], true);
    }
}
