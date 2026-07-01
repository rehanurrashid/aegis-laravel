<?php

declare(strict_types=1);

namespace App\Events\Referral;

use App\Models\Referral;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by ReferralService::accept() after status → accepted.
 * UC-PRV-111, UC-PRV-122
 *
 * Triggers:
 *  - SendEmailNotificationListener → emails.referral.21-referral-accepted (sender, gated)
 */
class ReferralAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Referral $referral,
    ) {}
}
