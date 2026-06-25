<?php

declare(strict_types=1);

namespace App\Events\Business;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProposalWithdrawn
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\BpProposal $proposal, public \App\Models\User $actor) {}
}
