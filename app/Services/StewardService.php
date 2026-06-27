<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Steward\AlternateCSActivated;
use App\Events\Steward\StewardRoleChangeRequested;

use App\Enums\ActivitySeverity;
use App\Events\Steward\StewardAccepted;
use App\Events\Steward\StewardDeclined;
use App\Events\Steward\StewardDesignated;
use App\Events\Steward\StewardRemoved;
use App\Jobs\SendEmailJob;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\PlanTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class StewardService
{
    public function __construct(private ActivityService $activity) {}

    /**
     * Designate an existing user as a steward on a plan.
     * Enforces tier limits.
     */
    public function designate(ContinuityPlan $plan, User $steward, string $stewardType, string $role = 'primary', array $extra = []): PlanSteward
    {
        $this->enforceTierLimits($plan, $stewardType);

        $row = PlanSteward::create([
            'id'              => 'ps_' . Str::lower(Str::random(12)),
            'plan_id'         => $plan->id,
            'steward_id'      => $steward->id,
            'steward_type'    => $stewardType,
            'role'            => $role,
            'status'          => 'pending',
            'invited_at'      => now(),
            'expires_at'      => now()->addDays(14),
            'responsibilities'=> isset($extra['responsibilities']) ? json_encode($extra['responsibilities']) : null,
            'steward_category'=> $extra['steward_category'] ?? null,
            'payment_model'   => $extra['payment_model'] ?? null,
            'agreed_fee'      => $extra['agreed_fee'] ?? null,
        ]);

        $practitioner = User::find($plan->practitioner_id);
        event(new StewardDesignated($row, $practitioner));

        $portal = $stewardType === 'continuity_steward' ? 'continuity_steward' : 'support_steward';
        $this->activity->log(
            $steward->id,
            $portal,
            'steward',
            ActivitySeverity::Info,
            'steward_invited',
            "{$practitioner->display_name} invited you as a {$this->humanRole($stewardType)}",
            'Please review the invitation and accept or decline.',
            'plan_steward',
            $row->id,
            $practitioner->id
        );

        SendEmailJob::dispatch(
            'emails.steward.07-steward-invitation',
            ['plan_id' => $plan->id, 'steward_id' => $row->id],
            $steward->id
        );

        return $row;
    }

    public function inviteExternal(ContinuityPlan $plan, string $email, string $displayName, string $stewardType, string $role = 'primary'): PlanSteward
    {
        $this->enforceTierLimits($plan, $stewardType);

        return DB::transaction(function () use ($plan, $email, $displayName, $stewardType, $role) {
            $userId = 'u_' . Str::lower(Str::random(12));
            $stub = User::create([
                'id'           => $userId,
                'role'         => $stewardType,
                'display_name' => $displayName,
                'email'        => $email,
                'slug'         => Str::slug($displayName) . '-' . Str::lower(Str::random(4)),
                'invited_by_id'=> $plan->practitioner_id,
                'verified'     => 0,
                'created_at'   => now(),
            ]);

            $row = PlanSteward::create([
                'id'         => 'ps_' . Str::lower(Str::random(12)),
                'plan_id'    => $plan->id,
                'steward_id' => $stub->id,
                'steward_type' => $stewardType,
                'role'       => $role,
                'status'     => 'pending',
                'invited_at' => now(),
                'expires_at' => now()->addDays(14),
            ]);

            SendEmailJob::dispatch(
                $stewardType === 'support_steward'
                    ? 'emails.steward.19-ss-invite-external'
                    : 'emails.steward.06-external-invitation',
                ['plan_id' => $plan->id, 'steward_id' => $row->id, 'invited' => true],
                $stub->id
            );

            return $row;
        });
    }

    public function accept(PlanSteward $steward, ?string $note = null): PlanSteward
    {
        $steward->update([
            'status'          => 'active',
            'signed_at'       => now(),
            'certification_at'=> now(),
            'certification_note' => $note,
        ]);

        event(new StewardAccepted($steward));

        $plan = ContinuityPlan::find($steward->plan_id);
        $stewardUser = User::find($steward->steward_id);

        $this->activity->log(
            $plan->practitioner_id,
            'provider',
            'steward',
            ActivitySeverity::Info,
            'steward_accepted',
            "{$stewardUser->display_name} accepted your steward invitation",
            'They have been added to your active roster.',
            'plan_steward',
            $steward->id,
            $stewardUser->id
        );

        return $steward->fresh();
    }

    public function decline(PlanSteward $steward, ?string $reason = null): PlanSteward
    {
        $steward->update([
            'status'          => 'declined',
            'declined_at'     => now(),
            'declined_reason' => $reason,
        ]);

        event(new StewardDeclined($steward));

        $plan = ContinuityPlan::find($steward->plan_id);
        $stewardUser = User::find($steward->steward_id);

        $this->activity->log(
            $plan->practitioner_id,
            'provider',
            'steward',
            ActivitySeverity::Warning,
            'steward_declined',
            "{$stewardUser->display_name} declined your steward invitation",
            $reason ? "Reason: {$reason}" : 'No reason given.',
            'plan_steward',
            $steward->id,
            $stewardUser->id
        );

        return $steward->fresh();
    }

    public function resign(PlanSteward $steward, ?string $reason = null): PlanSteward
    {
        $steward->update(['status' => 'released']);

        $plan = ContinuityPlan::find($steward->plan_id);
        $stewardUser = User::find($steward->steward_id);

        $this->activity->log(
            $plan->practitioner_id,
            'provider',
            'steward',
            ActivitySeverity::Warning,
            'steward_resigned',
            "{$stewardUser->display_name} resigned as a steward",
            $reason ? "Reason: {$reason}" : 'No reason given.',
            'plan_steward',
            $steward->id,
            $stewardUser->id
        );

        return $steward->fresh();
    }

    public function remove(PlanSteward $steward, User $actor, ?string $reason = null): PlanSteward
    {
        $steward->update(['status' => 'archived']);

        event(new StewardRemoved($steward, $actor));

        $this->activity->log(
            $steward->steward_id,
            $steward->steward_type === 'continuity_steward' ? 'continuity_steward' : 'support_steward',
            'steward',
            ActivitySeverity::Warning,
            'steward_removed',
            'You were removed from a continuity plan',
            $reason ? "Reason: {$reason}" : 'The practitioner removed you from their plan roster.',
            'plan_steward',
            $steward->id,
            $actor->id
        );

        return $steward->fresh();
    }

    /**
     * Copy plan_tasks from one steward to another (when alternate is promoted).
     */
    public function copyTasks(PlanSteward $from, PlanSteward $to): int
    {
        // plan_tasks belong to plan + incident_type + assigned_to (role),
        // not to a specific steward id. This is a no-op at the plan_tasks level;
        // task ownership is via assignment in incident_tasks at activation time.
        return 0;
    }

    public function setAuthorization(ContinuityPlan $plan, string $incidentType, array $authorizedCsIds, array $authorizedSsIds): void
    {
        $config = \App\Models\PlanIncidentConfig::where('plan_id', $plan->id)
            ->where('incident_type', $incidentType)
            ->firstOrFail();
        $config->update([
            'authorized_cs_ids' => json_encode($authorizedCsIds),
            'authorized_ss_ids' => json_encode($authorizedSsIds),
        ]);
    }

    public function requestRoleChange(PlanSteward $steward, string $requestedRole, ?string $requestNote = null): PlanSteward
    {
        $plan = ContinuityPlan::findOrFail($steward->plan_id);

        $this->activity->log(
            $plan->practitioner_id, 'provider', 'steward', ActivitySeverity::Info,
            'steward_role_change_requested',
            "{$steward->steward?->display_name} requested role change to {$requestedRole}",
            $requestNote ?? 'Review and approve the role change request.',
            'plan_steward', $steward->id, $steward->steward_id
        );

        event(new StewardRoleChangeRequested($steward, $requestedRole, $requestNote));
        return $steward;
    }

    public function activateAlternate(PlanSteward $alternate, PlanSteward $primary): PlanSteward
    {
        $alternate->update(['role' => 'primary', 'activated_at' => now()]);
        $primary->update(['role' => 'alternate']);

        $plan = ContinuityPlan::findOrFail($alternate->plan_id);
        $incident = \App\Models\CriticalIncident::where('plan_id', $plan->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        $this->activity->log(
            $plan->practitioner_id, 'provider', 'steward', ActivitySeverity::Warning,
            'alternate_cs_activated',
            "Alternate Continuity Steward activated: {$alternate->steward?->display_name}",
            "Former primary: {$primary->steward?->display_name}",
            'plan_steward', $alternate->id, null
        );

        if ($incident) {
            event(new AlternateCSActivated($alternate, $incident));
        }

        return $alternate->fresh();
    }

    public function getForPlan(string $planId): Collection
    {
        return PlanSteward::where('plan_id', $planId)
            ->whereIn('status', ['active', 'pending'])
            ->get();
    }

    public function getPendingInvitations(string $userId): Collection
    {
        return PlanSteward::where('steward_id', $userId)
            ->where('status', 'pending')
            ->get();
    }

    /**
     * Access tier: 1 CS + 2 SS max
     * Practice tier: 2 CS (no SS limit)
     */
    private function enforceTierLimits(ContinuityPlan $plan, string $stewardType): void
    {
        $practitioner = User::find($plan->practitioner_id);
        $tier = $practitioner->tier ?? 'access';

        $current = PlanSteward::where('plan_id', $plan->id)
            ->where('steward_type', $stewardType)
            ->whereIn('status', ['active', 'pending'])
            ->count();

        if ($stewardType === 'continuity_steward') {
            $max = $tier === 'practice' ? 2 : 1;
            if ($current >= $max) {
                throw new RuntimeException("Tier '{$tier}' allows max {$max} Continuity Steward(s).");
            }
        } elseif ($stewardType === 'support_steward') {
            if ($tier === 'access' && $current >= 2) {
                throw new RuntimeException("Access tier allows max 2 Support Stewards.");
            }
        }
    }

    private function humanRole(string $type): string
    {
        return $type === 'continuity_steward' ? 'Continuity Steward' : 'Support Steward';
    }
}
