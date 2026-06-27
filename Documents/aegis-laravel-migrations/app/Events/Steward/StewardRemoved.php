<?php

declare(strict_types=1);

namespace App\Events\Steward;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class StewardRemoved
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\PlanSteward $steward, public \App\Models\User $practitioner) {}

}
