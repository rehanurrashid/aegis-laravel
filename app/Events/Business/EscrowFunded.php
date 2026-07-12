<?php

declare(strict_types=1);

namespace App\Events\Business;

use App\Models\BpContract;
use App\Models\BpMilestone;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a provider successfully funds escrow — either full contract
 * upfront or a single milestone. $milestone is null for full-contract funding.
 */
class EscrowFunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BpContract   $contract,
        public User         $provider,
        public int          $amountCents,
        public ?BpMilestone $milestone = null,
    ) {}
}
