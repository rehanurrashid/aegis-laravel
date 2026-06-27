<?php

declare(strict_types=1);

namespace App\Events\Incident;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class IncidentEscalated
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\CriticalIncident $incident, public string $reason) {}

}
