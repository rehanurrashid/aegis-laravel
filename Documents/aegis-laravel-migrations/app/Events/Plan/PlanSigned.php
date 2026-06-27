<?php

declare(strict_types=1);

namespace App\Events\Plan;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class PlanSigned
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\ContinuityPlan $plan, public \App\Models\User $practitioner) {}

}
