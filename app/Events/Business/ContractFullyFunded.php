<?php

declare(strict_types=1);

namespace App\Events\Business;

use App\Models\BpContract;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when full contract value is funded in escrow (full_upfront mode). */
class ContractFullyFunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BpContract $contract,
        public User       $provider,
    ) {}
}
