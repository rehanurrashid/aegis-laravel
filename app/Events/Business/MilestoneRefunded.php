<?php

declare(strict_types=1);

namespace App\Events\Business;

use App\Models\BpMilestone;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when escrow funds are refunded back to provider. */
class MilestoneRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BpMilestone $milestone,
        public User        $actor,           // who triggered (provider, admin)
        public int         $refundedCents,
        public string      $reason,
    ) {}
}
