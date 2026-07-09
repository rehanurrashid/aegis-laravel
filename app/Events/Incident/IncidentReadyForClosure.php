<?php

declare(strict_types=1);

namespace App\Events\Incident;

use App\Models\CriticalIncident;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the moment the last incident task is marked complete.
 * Provider + Support Steward receive an email + notification asking
 * them to verify closure.
 */
class IncidentReadyForClosure
{
    use Dispatchable, SerializesModels;

    public function __construct(public CriticalIncident $incident) {}
}
