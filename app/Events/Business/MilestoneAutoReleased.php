<?php

declare(strict_types=1);

namespace App\Events\Business;

use App\Models\BpMilestone;
use App\Models\BpPayout;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when the auto-release cron fires because provider did not
 * review a submitted milestone within MILESTONE_AUTO_RELEASE_DAYS.
 */
class MilestoneAutoReleased
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BpMilestone $milestone,
        public BpPayout    $payout,
    ) {}
}
