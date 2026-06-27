<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\CriticalIncident;
use Illuminate\Support\Facades\Broadcast;

/*
| Broadcast channel authorization. Incident channels are private and gated
| to: the affected practitioner, any assigned stewards (CS/SS), and the
| admin role. See SendIncidentAlertsListener for emission.
*/

Broadcast::channel('incident.{incidentId}', function (User $user, string $incidentId) {
    $incident = CriticalIncident::find($incidentId);
    if (!$incident) return false;

    if ($user->id === $incident->practitioner_id) return true;
    if ((string) $user->role === 'admin') return true;

    // Steward access is via PlanSteward rows on the affected plan.
    return $incident->plan?->stewards()->where('user_id', $user->id)->exists() ?? false;
});

Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return (string) $user->id === (string) $userId;
});
