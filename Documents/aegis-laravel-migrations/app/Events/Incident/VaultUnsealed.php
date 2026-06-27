<?php

declare(strict_types=1);

namespace App\Events\Incident;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class VaultUnsealed
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\ContinuityPlan $plan, public \App\Models\CriticalIncident $incident) {}

}
