<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Incident\IncidentActivated;
use App\Events\Incident\IncidentReported;
use App\Events\Incident\IncidentVerified;
use App\Jobs\IncidentNotificationJob;
use App\Jobs\SendEmailJob;
use App\Models\PlanSteward;

/**
 * UNGATED — bypasses notify_* preferences entirely.
 * Critical incident alerts must always be sent.
 */
class SendIncidentAlertsListener
{
    public function handle(IncidentReported|IncidentVerified|IncidentActivated $event): void
    {
        $template = match (true) {
            $event instanceof IncidentReported  => 'emails.incident.26-incident-reported',
            $event instanceof IncidentVerified  => 'emails.incident.27-incident-verified',
            $event instanceof IncidentActivated => 'emails.incident.28-incident-activated',
        };

        $stewards = PlanSteward::where('plan_id', $event->incident->plan_id)
            ->whereIn('status', ['active'])
            ->get();

        foreach ($stewards as $ps) {
            SendEmailJob::dispatch(
                $template,
                ['incident_id' => $event->incident->id, 'plan_id' => $event->incident->plan_id],
                $ps->steward_id
            );
        }

        // Practitioner always notified on activation
        if ($event instanceof IncidentActivated) {
            SendEmailJob::dispatch(
                $template,
                ['incident_id' => $event->incident->id],
                $event->incident->practitioner_id
            );
        }

        // High-priority notification job for SMS-eligible users
        IncidentNotificationJob::dispatch($event->incident->id, $this->stageFor($event));
    }

    private function stageFor(object $event): string
    {
        return match (true) {
            $event instanceof IncidentReported  => 'reported',
            $event instanceof IncidentVerified  => 'verified',
            $event instanceof IncidentActivated => 'activated',
        };
    }
}
