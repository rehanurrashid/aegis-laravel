<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Steward\AlternateCSActivated;
use App\Events\Steward\StewardInviteCancelled;
use App\Events\Steward\StewardInviteResent;
use App\Events\Steward\StewardRoleChangeRequested;

use App\Enums\ActivitySeverity;
use App\Events\Steward\StewardAccepted;
use App\Events\Steward\StewardDeclined;
use App\Events\Steward\StewardDesignated;
use App\Events\Steward\StewardRemoved;
use App\Jobs\SendEmailJob;
use App\Models\ContinuityDocument;
use App\Models\ContinuityPlan;
use App\Models\CsInvoice;
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
            'id'               => 'ps_' . Str::lower(Str::random(12)),
            'plan_id'          => $plan->id,
            'steward_id'       => $steward->id,
            'steward_category' => $stewardType,
            'role'             => $role,
            'status'           => 'pending',
            'invited_at'       => now(),
            'expires_at'       => now()->addDays(14),
            'responsibilities' => isset($extra['responsibilities']) ? json_encode($extra['responsibilities']) : null,
            'payment_model'    => $extra['payment_model'] ?? null,
            'fee_cents'        => $extra['fee_cents'] ?? null,
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
            $practitioner->id,
            'notification',
            $practitioner->id
        );

        SendEmailJob::dispatchSync(
            $stewardType === 'support_steward'
                ? 'emails.steward.20-ss-invite-internal'
                : 'emails.steward.18-cs-invite-internal',
            [
                'plan_steward_id'   => $row->id,
                'practitioner_name' => $practitioner?->display_name ?? 'A healthcare practitioner',
                'expires_days'      => 14,
            ],
            $steward->id
        );

        return $row;
    }

    public function inviteExternal(ContinuityPlan $plan, string $email, string $displayName, string $stewardType, string $role = 'primary'): PlanSteward
    {
        $this->enforceTierLimits($plan, $stewardType);

        return DB::transaction(function () use ($plan, $email, $displayName, $stewardType, $role) {
            // Reuse existing user if email already registered — never double-insert
            $stub = User::where('email', $email)->first();
            if (!$stub) {
                $stub = User::create([
                    'id'           => 'u_' . Str::lower(Str::random(12)),
                    'role'         => $stewardType,
                    'display_name' => $displayName,
                    'email'        => $email,
                    'slug'         => Str::slug($displayName) . '-' . Str::lower(Str::random(4)),
                    'invited_by_id'=> $plan->practitioner_id,
                    'verified'     => 0,
                    'created_at'   => now(),
                ]);
            }

            // If already on this plan (any non-archived status), reuse or reactivate
            $existing = PlanSteward::where('plan_id', $plan->id)
                ->where('steward_id', $stub->id)
                ->where('steward_category', $stewardType)
                ->whereNotIn('status', ['archived', 'declined'])
                ->first();
            if ($existing) {
                throw new \RuntimeException('This person already has an active or pending agreement on your plan.');
            }

            $row = PlanSteward::create([
                'id'         => 'ps_' . Str::lower(Str::random(12)),
                'plan_id'    => $plan->id,
                'steward_id' => $stub->id,
                'steward_category' => $stewardType,
                'role'       => $role,
                'status'     => 'pending',
                'invited_at' => now(),
                'expires_at' => now()->addDays(14),
            ]);

            $practitioner = User::find($plan->practitioner_id);
            $isExistingUser = (bool) User::where('email', $email)->where('verified', 1)->exists();
            $template = $isExistingUser
                ? ($stewardType === 'support_steward' ? 'emails.steward.20-ss-invite-internal' : 'emails.steward.18-cs-invite-internal')
                : ($stewardType === 'support_steward' ? 'emails.steward.19-ss-invite-external' : 'emails.steward.17-cs-invite-external');

            SendEmailJob::dispatchSync(
                $template,
                [
                    'plan_steward_id'    => $row->id,
                    'practitioner_name'  => $practitioner?->display_name ?? 'A healthcare practitioner',
                    'expires_days'       => 14,
                ],
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
            $stewardUser->id,
            'notification',
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
            $stewardUser->id,
            'notification',
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
            $stewardUser->id,
            'notification',
            $stewardUser->id
        );

        return $steward->fresh();
    }

    public function remove(PlanSteward $steward, User $actor, ?string $reason = null): PlanSteward
    {
        // Guard: block removal when outstanding invoices exist
        $outstanding = CsInvoice::where('cs_id', $steward->steward_id)
            ->where('practitioner_id', $actor->id)
            ->whereIn('status', ['sent', 'overdue', 'disputed'])
            ->exists();

        if ($outstanding) {
            throw new RuntimeException(
                'Cannot remove this Continuity Steward while outstanding invoices exist. Resolve all sent, overdue, or disputed invoices first.'
            );
        }

        $steward->update(['status' => 'archived']);

        event(new StewardRemoved($steward, $actor));

        // Notification to the removed steward
        $this->activity->log(
            $steward->steward_id,
            $steward->steward_category === 'continuity_steward' ? 'continuity_steward' : 'support_steward',
            'steward',
            ActivitySeverity::Warning,
            'steward_removed',
            'You were removed from a continuity plan',
            $reason ? "Reason: {$reason}" : 'The practitioner removed you from their plan roster.',
            'plan_steward',
            $steward->id,
            $actor->id,
            'notification',
            $actor->id
        );

        // Actor log for the provider
        $this->activity->log(
            $actor->id,
            'provider',
            'steward',
            ActivitySeverity::Warning,
            'steward_removed',
            'Continuity Steward removed',
            $reason ? "Reason: {$reason}" : 'Steward removed from plan roster.',
            'plan_steward',
            $steward->id,
            $steward->steward_id,
            'log',
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
        // Resolve plan_steward IDs to stable user IDs for storage
        $csUserIds = PlanSteward::whereIn('id', $authorizedCsIds)->pluck('steward_id')->toArray();
        $ssUserIds = PlanSteward::whereIn('id', $authorizedSsIds)->pluck('steward_id')->toArray();

        $config = \App\Models\PlanIncidentConfig::where('plan_id', $plan->id)
            ->where('incident_type', $incidentType)
            ->firstOrFail();
        $config->update([
            'authorized_cs_ids' => json_encode($csUserIds),
            'authorized_ss_ids' => json_encode($ssUserIds),
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
            'plan_steward', $steward->id, $steward->steward_id,
            'notification', $steward->steward_id
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
        $tier = is_object($practitioner->tier) ? $practitioner->tier->value : ($practitioner->tier ?? 'access');

        $limits = config('aegis.tier_limits.' . $tier, config('aegis.tier_limits.access'));

        $current = PlanSteward::where('plan_id', $plan->id)
            ->where('steward_category', $stewardType)
            ->where('status', 'active')
            ->count();

        if ($stewardType === 'continuity_steward') {
            $max = (int) ($limits['max_continuity_stewards'] ?? 2);
            if ($current >= $max) {
                throw new RuntimeException("Tier '{$tier}' allows max {$max} Continuity Steward(s).");
            }
        } elseif ($stewardType === 'support_steward') {
            $max = (int) ($limits['max_support_stewards'] ?? 2);
            if ($current >= $max) {
                throw new RuntimeException("Tier '{$tier}' allows max {$max} Support Steward(s).");
            }
        }
    }

    private function humanRole(string $type): string
    {
        return $type === 'continuity_steward' ? 'Continuity Steward' : 'Support Steward';
    }

    /**
     * Update a CS fee. Creates a ContinuityDocument amendment; does NOT mutate fee_cents
     * until the CS countersigns that document.
     */
    public function updateFee(PlanSteward $steward, int $newFeeCents, string $paymentTerms, User $actor): ContinuityDocument
    {
        // Normalize legacy alias: per_incident is identical to on_close
        $paymentTerms = $paymentTerms === 'per_incident' ? 'on_close' : $paymentTerms;

        $oldFeeCents = (int) ($steward->fee_cents ?? 0);
        $newFeeFormatted = number_format($newFeeCents / 100, 2);

        $doc = ContinuityDocument::create([
            'id'                => 'doc_' . Str::lower(Str::random(12)),
            'plan_id'           => $steward->plan_id,
            'practitioner_id'   => $actor->id,
            'party_b_id'        => $steward->steward_id,
            'holder_steward_id' => $steward->steward_id,
            'doc_type'          => 'fee_amendment',
            'title'             => 'CS Fee Amendment',
            'status'            => 'pending_sign',
            'notes'             => json_encode([
                'old_fee_cents' => $oldFeeCents,
                'new_fee_cents' => $newFeeCents,
                'payment_terms' => $paymentTerms,
                'proposed_at'   => now()->toIso8601String(),
            ]),
        ]);

        // Notify the CS — fee change requires countersignature
        $this->activity->log(
            $steward->steward_id,
            'continuity_steward',
            'steward',
            ActivitySeverity::Warning,
            'cs_fee_amended',
            'Your Continuity Steward fee has been amended',
            "New fee: \${$newFeeFormatted}. Please review and countersign the amendment document.",
            'continuity_document',
            $doc->id,
            $actor->id,
            'notification',
            $actor->id
        );

        // Actor log
        $this->activity->log(
            $actor->id,
            'provider',
            'steward',
            ActivitySeverity::Info,
            'cs_fee_amended',
            'CS fee amendment created',
            "Amendment for \${$newFeeFormatted} sent to CS for countersignature.",
            'continuity_document',
            $doc->id,
            $steward->steward_id,
            'log',
            $actor->id
        );

        event(new \App\Events\Steward\CsFeeAmended($steward, $doc, $actor));

        return $doc;
    }

    /**
     * Resend a pending invitation email and reset invited_at / expires_at.
     */
    public function resendInvite(PlanSteward $steward, User $actor, int $expiryDays = 14, ?string $message = null): PlanSteward
    {
        $steward->update([
            'invited_at' => now(),
            'expires_at' => now()->addDays($expiryDays),
        ]);

        event(new StewardInviteResent($steward, $actor, $message, $expiryDays));

        if ($steward->steward_id) {
            $stewardUser = User::find($steward->steward_id);
            $isVerified  = (bool) ($stewardUser?->verified ?? false);
            $template    = $isVerified
                ? ($steward->steward_category === 'support_steward' ? 'emails.steward.20-ss-invite-internal' : 'emails.steward.18-cs-invite-internal')
                : ($steward->steward_category === 'support_steward' ? 'emails.steward.19-ss-invite-external' : 'emails.steward.17-cs-invite-external');

            $portalLabel = $steward->steward_category === 'continuity_steward' ? 'continuity_steward' : 'support_steward';
            $this->activity->log(
                $steward->steward_id,
                $portalLabel,
                'steward',
                ActivitySeverity::Info,
                'steward_invite_resent',
                'Steward invitation re-sent',
                'Your invitation to serve as a steward has been resent.',
                'plan_steward',
                $steward->id,
                $actor->id,
                'notification',
                $actor->id
            );

            SendEmailJob::dispatchSync(
                $template,
                ['plan_steward_id' => $steward->id, 'expires_days' => $expiryDays, 'follow_up_message' => $message],
                $steward->steward_id
            );
        } else {
            // External stub user — resend to stored email
            $template = $steward->steward_category === 'support_steward'
                ? 'emails.steward.19-ss-invite-external'
                : 'emails.steward.17-cs-invite-external';

            SendEmailJob::dispatchSync(
                $template,
                ['plan_steward_id' => $steward->id, 'expires_days' => $expiryDays, 'follow_up_message' => $message],
                $steward->steward_id
            );
        }

        return $steward->fresh();
    }

    /**
     * Cancel a pending invitation (status → archived).
     */
    public function cancelInvite(PlanSteward $steward, User $actor): PlanSteward
    {
        $steward->update(['status' => 'archived']);

        $this->activity->log(
            $actor->id,
            'provider',
            'steward',
            ActivitySeverity::Info,
            'steward_invite_cancelled',
            'Steward invitation cancelled',
            'The pending steward invitation was cancelled.',
            'plan_steward',
            $steward->id,
            $steward->steward_id,
            'log',
            $actor->id
        );

        if ($steward->steward_id) {
            $this->activity->log(
                $steward->steward_id,
                'continuity_steward',
                'steward',
                ActivitySeverity::Info,
                'invite_cancelled',
                'Continuity Steward invitation withdrawn',
                'The practitioner has withdrawn your Continuity Steward invitation.',
                'plan_steward',
                $steward->id,
                $actor->id,
                'notification',
                $actor->id
            );
        }

        event(new StewardInviteCancelled($steward, $actor));

        return $steward->fresh();
    }
}
