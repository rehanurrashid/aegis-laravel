<?php

declare(strict_types=1);

namespace App\Events\Business;

use App\Models\BpContract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when all milestones are approved and the contract is marked completed. */
class ContractCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BpContract $contract,
    ) {}
}
