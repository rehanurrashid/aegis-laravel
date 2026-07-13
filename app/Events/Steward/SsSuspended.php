<?php

declare(strict_types=1);

namespace App\Events\Steward;

use App\Models\PlanSteward;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SsSuspended
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PlanSteward $steward,
        public User $practitioner,
        public string $reason
    ) {}
}
