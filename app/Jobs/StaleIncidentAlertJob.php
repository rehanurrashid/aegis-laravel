<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ActivitySeverity;
use App\Models\CriticalIncident;
use App\Models\PlanSteward;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Every 6 hours. Finds active critical incidents open > 72 hours and alerts
 * assigned CS + system admins for escalation review.
 */
class StaleIncidentAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(ActivityService $activity): void
    {
        $cutoff = now()->subHours(72);

        $stale = CriticalIncident::where('status', 'active')
            ->where('reported_at', '<=', $cutoff)
            ->get();

        $admins = User::where('role', 'admin')->whereNull('deactivated_at')->get();

        foreach ($stale as $incident) {
            // Notify assigned CS
            $stewards = PlanSteward::where('plan_id', $incident->plan_id)
                ->where('steward_type', 'continuity_steward')
                ->where('status', 'active')
                ->get();

            foreach ($stewards as $ps) {
                $activity->log(
                    $ps->steward_id,
                    'continuity_steward',
                    'incident',
                    ActivitySeverity::Warning,
                    'incident_stale',
                    'Incident has been active for more than 72 hours',
                    'Please review status and either provide an update or initiate closure.',
                    'critical_incident',
                    $incident->id,
                    null
                );
            }

            // Notify admins
            foreach ($admins as $admin) {
                $activity->log(
                    $admin->id,
                    'admin',
                    'incident',
                    ActivitySeverity::Warning,
                    'incident_stale_admin',
                    'Stale incident requires oversight',
                    "Incident {$incident->id} has been active for >72h.",
                    'critical_incident',
                    $incident->id,
                    null
                );
            }
        }
    }
}
