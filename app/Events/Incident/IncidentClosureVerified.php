<?php

declare(strict_types=1);

namespace App\Events\Incident;

use App\Models\CriticalIncident;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when Provider or Support Steward explicitly verifies that the
 * CS's closure of the incident is acceptable. Distinct from the
 * incident being marked closed — verification is the sign-off that
 * triggers auto-invoice generation on close.
 */
class IncidentClosureVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CriticalIncident $incident,
        public User $verifier,
        public string $verifierRole  // 'provider' | 'support_steward' | 'system_auto'
    ) {}
}
