<?php

declare(strict_types=1);

namespace App\Events\Referral;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by ReferralService::cancel() after status → cancelled.
 * UC-PRV-110 (sender-side cancel)
 *
 * Triggers:
 *  - SendEmailNotificationListener → emails.referral.24-referral-cancelled (recipient, gated)
 */
class ReferralCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Referral $referral,
        public User     $actor,
        public ?string  $reason = null,
    ) {}
}
