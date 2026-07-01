<?php

declare(strict_types=1);

namespace App\Events\Referral;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by ReferralService::close() after status → closed.
 * UC-PRV-124 (mark complete by either party)
 *
 * Triggers:
 *  - SendEmailNotificationListener → emails.referral.23-referral-completed (other party, gated)
 */
class ReferralClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Referral $referral,
        public User     $actor,
    ) {}
}
