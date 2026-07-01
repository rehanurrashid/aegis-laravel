<?php

declare(strict_types=1);

namespace App\Events\Referral;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by ReferralService::send() after the Referral row is persisted.
 * UC-PRV-108, UC-PRV-121
 *
 * Triggers:
 *  - SendEmailNotificationListener → emails.referral.20-referral-received (recipient, gated)
 *  - SendEmailNotificationListener → emails.referral.20a-referral-sent-confirmation (sender, gated)
 */
class ReferralSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Referral $referral,
        public User     $sender,
        public User     $recipient,
    ) {}
}
