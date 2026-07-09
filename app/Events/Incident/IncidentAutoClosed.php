<?php

declare(strict_types=1);

namespace App\Events\Incident;

use App\Models\CriticalIncident;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an incident sits in "ready for closure" state past the
 * configured window without verification (default: 7 days).
 * IncidentAutoCloseCheckJob dispatches this and closes the incident.
 */
class IncidentAutoClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CriticalIncident $incident,
        public int $windowDays,
    ) {}
}
