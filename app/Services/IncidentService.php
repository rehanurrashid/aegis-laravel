<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Events\Incident\IncidentActivated;
use App\Events\Incident\IncidentClosed;
use App\Events\Incident\IncidentEscalated;
use App\Events\Incident\IncidentReported;
use App\Events\Incident\IncidentVerified;
use App\Events\Incident\VaultUnsealed;
use App\Jobs\ActivityFanoutJob;
use App\Jobs\IncidentNotificationJob;
use App\Models\ContinuityPlan;
use App\Models\CriticalIncident;
use App\Models\IncidentTask;
use App\Models\PlanSteward;
use App\Models\PlanTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class IncidentService
{
    public function __construct(private ActivityService $activity) {}

    /**
     * SS reports a critical incident. Bypasses notify_* prefs.
     */
    public function report(ContinuityPlan $plan, User $reporter, array $data): CriticalIncident
    {
        if ($reporter->role !== 'support_steward' && $reporter->id !== $plan->practitioner_id) {
            throw new RuntimeException('Only the practitioner or an assigned Support Steward can report.');
        }

        $incident = CriticalIncident::create([
            'id'                => 'ci_' . Str::lower(Str::random(12)),
            'practitioner_id'   => $plan->practitioner_id,
            'plan_id'           => $plan->id,
            'incident_type'     => $data['incident_type'],
            'reported_by_id'    => $reporter->id,
            'reported_at'       => now(),
            'summary'           => $data['narrative'] ?? null,
            'status'            => 'reported',
        ]);

        // Actor log
        $this->activity->log(
            $reporter->id, $reporter->role?->value === 'support_steward' ? 'support_steward' : 'provider',
            'incident', ActivitySeverity::Critical,
            'incident_reported', 'Critical incident reported',
            "You reported a critical incident: {$data['incident_type']}.",
            CriticalIncident::class, $incident->id, $plan->practitioner_id, 'log', $reporter->id,
        );

        event(new IncidentReported($incident));

        // UNGATED notification — bypasses notify_* prefs
        IncidentNotificationJob::dispatch($incident->id, 'reported');

        return $incident;
    }

    /**
     * CS verifies the report.
     */
    public function verify(CriticalIncident $incident, User $cs, array $data): CriticalIncident
    {
        $isAssignedCs = PlanSteward::where('plan_id', $incident->plan_id)
            ->where('steward_id', $cs->id)
            ->where('steward_type', 'continuity_steward')
            ->where('status', 'active')
            ->exists();
        if (!$isAssignedCs) {
            throw new RuntimeException('Only an assigned Continuity Steward can verify.');
        }

        $incident->update([
            'verified_by_id'     => $cs->id,
            'verified_at'        => now(),
        ]);

        $this->activity->log(
            $cs->id, 'continuity_steward',
            'incident', ActivitySeverity::Critical,
            'incident_verified', 'Critical incident verified',
            "You verified the critical incident.",
            CriticalIncident::class, $incident->id, $incident->practitioner_id, 'log', $cs->id,
        );

        event(new IncidentVerified($incident->fresh()));
        IncidentNotificationJob::dispatch($incident->id, 'verified');

        return $incident->fresh();
    }

    /**
     * Activate the incident. Generates incident_tasks, unseals vault,
     * fans out to ALL stewards via queued job.
     */
    public function activate(CriticalIncident $incident): CriticalIncident
    {
        if ($incident->status === 'active') {
            return $incident;
        }
        if (!$incident->verified_at) {
            throw new RuntimeException('Incident must be verified before activation.');
        }

        DB::transaction(function () use ($incident) {
            $incident->update(['status' => 'active']);

            // Generate incident_tasks from plan_tasks for this incident type
            $planTasks = PlanTask::where('plan_id', $incident->plan_id)
                ->where('incident_type', $incident->incident_type)
                ->orderBy('sort_order')
                ->get();

            foreach ($planTasks as $pt) {
                // Resolve assigned user: first active steward of matching type
                $assignee = PlanSteward::where('plan_id', $incident->plan_id)
                    ->where('steward_type', $pt->assigned_to)
                    ->where('status', 'active')
                    ->orderBy('role') // primary first
                    ->first();
                if (!$assignee) continue;

                IncidentTask::create([
                    'id'                  => 'it_' . Str::lower(Str::random(12)),
                    'incident_id'         => $incident->id,
                    'plan_task_id'        => $pt->id,
                    'title'               => $pt->title,
                    'assigned_to_user_id' => $assignee->steward_id,
                    'status'              => 'pending',
                    'task_list_type'      => $pt->assigned_to,
                ]);
            }
        });

        $plan = ContinuityPlan::find($incident->plan_id);
        event(new IncidentActivated($incident->fresh()));
        event(new VaultUnsealed($plan, $incident->fresh()));

        // Heavy fan-out via queued Job
        $recipients = $this->activity->getPlanStewardRecipients($incident->plan_id);
        $recipients[] = ['user_id' => $plan->practitioner_id, 'portal' => 'provider'];

        ActivityFanoutJob::dispatch($recipients, [
            'module'          => 'incident',
            'severity'        => ActivitySeverity::Critical->value,
            'event_type'      => 'incident_activated',
            'title'           => 'Critical incident activated',
            'description'     => 'A verified critical incident has been activated. Vault access is now available to assigned stewards.',
            'linkable_type'   => 'critical_incident',
            'linkable_id'     => $incident->id,
            'related_user_id' => $incident->verified_by_id,
        ]);

        IncidentNotificationJob::dispatch($incident->id, 'activated');

        return $incident->fresh();
    }

    public function addUpdate(CriticalIncident $incident, User $author, string $body): void
    {
        $this->activity->fanOut(
            $this->activity->getPlanStewardRecipients($incident->plan_id),
            [
                'module'          => 'incident',
                'severity'        => ActivitySeverity::Info,
                'event_type'      => 'incident_update',
                'title'           => "{$author->display_name} posted an incident update",
                'description'     => Str::limit($body, 140),
                'linkable_type'   => 'critical_incident',
                'linkable_id'     => $incident->id,
                'related_user_id' => $author->id,
            ]
        );
    }

    public function close(CriticalIncident $incident, User $closer, string $summary): CriticalIncident
    {
        $incident->update([
            'status'             => 'closed',
            'closed_at'          => now(),
            'summary'            => $summary,
        ]);

        $this->activity->log(
            $closer->id, $closer->role?->value === 'continuity_steward' ? 'continuity_steward' : 'provider',
            'incident', ActivitySeverity::Info,
            'incident_closed', 'Critical incident closed',
            "You closed the critical incident.",
            CriticalIncident::class, $incident->id, $incident->practitioner_id, 'log', $closer->id,
        );

        event(new IncidentClosed($incident->fresh()));

        // Re-seal vault & fan out
        $recipients = $this->activity->getPlanStewardRecipients($incident->plan_id);
        $recipients[] = ['user_id' => $incident->practitioner_id, 'portal' => 'provider'];
        $this->activity->fanOut($recipients, [
            'module'          => 'incident',
            'severity'        => ActivitySeverity::Info,
            'event_type'      => 'incident_closed',
            'title'           => 'Critical incident closed',
            'description'     => Str::limit($summary, 140),
            'linkable_type'   => 'critical_incident',
            'linkable_id'     => $incident->id,
            'related_user_id' => $closer->id,
        ]);

        return $incident->fresh();
    }

    public function escalate(CriticalIncident $incident, string $reason): CriticalIncident
    {
        event(new IncidentEscalated($incident, $reason));

        $recipients = $this->activity->getPlanStewardRecipients($incident->plan_id);
        $this->activity->fanOut($recipients, [
            'module'          => 'incident',
            'severity'        => ActivitySeverity::Critical,
            'event_type'      => 'incident_escalated',
            'title'           => 'Critical incident escalated',
            'description'     => $reason,
            'linkable_type'   => 'critical_incident',
            'linkable_id'     => $incident->id,
        ]);

        return $incident;
    }

    public function getActive(): Collection
    {
        return CriticalIncident::where('status', 'active')->get();
    }

    public function getForPractitioner(string $practitionerId): Collection
    {
        return CriticalIncident::where('practitioner_id', $practitionerId)
            ->orderByDesc('reported_at')
            ->get();
    }

    public function hasActiveForPlan(string $planId): bool
    {
        return CriticalIncident::where('plan_id', $planId)
            ->where('status', 'active')
            ->exists();
    }
}
