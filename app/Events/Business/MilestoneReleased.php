<?php

declare(strict_types=1);

namespace App\Events\Business;

use App\Models\BpMilestone;
use App\Models\BpPayout;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when escrow funds are transferred to BP. */
class MilestoneReleased
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BpMilestone $milestone,
        public BpPayout    $payout,
        public User        $approver,        // provider who approved (or system for auto-release)
    ) {}
}
