<?php

declare(strict_types=1);

namespace App\Events\Steward;

use App\Models\ContinuityDocument;
use App\Models\PlanSteward;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CsFeeAmended
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PlanSteward         $steward,
        public readonly ContinuityDocument  $document,
        public readonly User                $actor,
    ) {}
}
