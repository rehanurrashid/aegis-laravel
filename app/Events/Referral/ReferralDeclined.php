<?php

declare(strict_types=1);

namespace App\Events\Referral;

use App\Models\Referral;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by ReferralService::decline() after status → declined.
 * UC-PRV-111, UC-PRV-123
 *
 * Triggers:
 *  - SendEmailNotificationListener → emails.referral.22-referral-declined (sender, gated)
 */
class ReferralDeclined
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Referral $referral,
        public ?string  $reason = null,
    ) {}
}
