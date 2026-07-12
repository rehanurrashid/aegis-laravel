<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Enums\ContractStatus;
use App\Enums\MilestoneStatus;
use App\Events\Business\ContractCancelled;
use App\Events\Business\ContractSigned;
use App\Events\Business\MilestoneApproved;
use App\Events\Business\MilestoneRevisionRequested;
use App\Events\Business\MilestoneSubmitted;
use App\Models\BpContract;
use App\Models\BpMilestone;
use App\Models\BpMilestoneSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractService
{
    public function __construct(
        private ActivityService $activity,
        private EscrowService   $escrow,
    ) {}

    public function create(string $jobId, string $proposalId, string $practitionerId, string $bpId, int $totalCents): BpContract
    {
        return BpContract::create([
            'id'                => 'bc_' . Str::lower(Str::random(12)),
            'job_id'            => $jobId,
            'proposal_id'       => $proposalId,
            'practitioner_id'   => $practitionerId,
            'bp_id'             => $bpId,
            'title'             => 'Contract',
            'total_value_cents' => $totalCents,
            'status'            => 'active',
        ]);
    }

    /**
     * Real dual-signature. Contract state machine:
     *   pending_signature → (both sign) → pending_funding → (provider funds) → active
     */
    public function sign(BpContract $contract, User $signer, array $signature): BpContract
    {
        $contractStatus = $contract->status instanceof ContractStatus
            ? $contract->status->value
            : (string) $contract->status;

        if (!in_array($contractStatus, [
            ContractStatus::PendingSignature->value,
            ContractStatus::Draft->value,
        ], true)) {
            throw new \RuntimeException("Contract cannot be signed in status: {$contractStatus}");
        }

        $isPractitioner = $contract->practitioner_id === $signer->id;
        $isBp           = $contract->bp_id === $signer->id;

        if (!$isPractitioner && !$isBp) {
            throw new \RuntimeException('You are not a party to this contract.');
        }
        if ($isPractitioner && $contract->practitioner_signed_at) {
            throw new \RuntimeException('You have already signed this contract.');
        }
        if ($isBp && $contract->bp_signed_at) {
            throw new \RuntimeException('You have already signed this contract.');
        }

        $update = $isPractitioner
            ? ['practitioner_signed_at' => now(), 'practitioner_signature_name' => $signature['name'] ?? $signer->display_name]
            : ['bp_signed_at' => now(), 'bp_signature_name' => $signature['name'] ?? $signer->display_name];

        $contract->update($update);
        $fresh         = $contract->fresh();
        $fullyExecuted = $fresh->practitioner_signed_at && $fresh->bp_signed_at;

        if ($fullyExecuted) {
            $fresh->update([
                'fully_executed_at' => now(),
                'status'            => ContractStatus::PendingFunding->value,
            ]);
            $fresh = $fresh->fresh();
            event(new ContractSigned($fresh));
        }

        $otherId      = $isPractitioner ? $contract->bp_id : $contract->practitioner_id;
        $otherPortal  = $isPractitioner ? 'business_partner' : 'provider';
        $signerPortal = $isPractitioner ? 'provider' : 'business_partner';

        $this->activity->log(
            $signer->id, $signerPortal, 'job_postings', ActivitySeverity::Info,
            'contract_signed',
            'You signed the contract: ' . $contract->title,
            $fullyExecuted ? 'Contract fully executed. Please fund escrow to begin work.' : "Awaiting the other party's signature.",
            'bp_contract', $contract->id, null,
            'log', $signer->id
        );
        $this->activity->log(
            $otherId, $otherPortal, 'job_postings', ActivitySeverity::Info,
            'contract_signed',
            "{$signer->display_name} signed the contract",
            $fullyExecuted ? 'Contract fully executed. Provider must fund escrow to activate.' : 'Awaiting your signature. Please review and sign.',
            'bp_contract', $contract->id, $signer->id,
            'notification', $signer->id
        );

        return $fresh;
    }

    /**
     * Cancel contract. Escrow-aware: refunds all held milestone funds to provider.
     * Allowed from pending_signature, pending_funding, and active states.
     */
    public function cancel(BpContract $contract, User $actor, ?string $reason = null): BpContract
    {
        $contractStatus = $contract->status instanceof ContractStatus
            ? $contract->status->value
            : (string) $contract->status;

        if (!in_array($contractStatus, [
            ContractStatus::PendingSignature->value,
            ContractStatus::PendingFunding->value,
            ContractStatus::Active->value,
        ], true)) {
            throw new \RuntimeException("Cannot cancel a contract with status: {$contractStatus}");
        }

        // Refund any escrow before updating status
        $heldCents = $contract->escrowHeldCents();
        if ($heldCents > 0) {
            $this->escrow->cancelContractEscrow($contract, $actor, $reason ?? 'Contract cancelled');
        }

        $contract->update([
            'status'       => ContractStatus::Cancelled->value,
            'cancelled_at' => now(),
            'cancel_reason'=> $reason,
        ]);

        $otherId     = $contract->practitioner_id === $actor->id ? $contract->bp_id : $contract->practitioner_id;
        $otherUser   = User::find($otherId);
        $otherPortal = $otherUser?->role === 'business_partner' ? 'business_partner' : 'provider';
        $actorPortal = $actor->id === $contract->practitioner_id ? 'provider' : 'business_partner';

        $refundNote  = $heldCents > 0
            ? ' Escrow funds ($' . number_format($heldCents / 100, 2) . ') have been refunded.'
            : '';

        $this->activity->log(
            $actor->id, $actorPortal, 'job_postings', ActivitySeverity::Warning,
            'contract_cancelled',
            'You cancelled the contract: ' . $contract->title,
            ($reason ?? 'No reason recorded.') . $refundNote,
            'bp_contract', $contract->id, null,
            'log', $actor->id
        );
        $this->activity->log(
            $otherId, $otherPortal, 'job_postings', ActivitySeverity::Warning,
            'contract_cancelled',
            "{$actor->display_name} cancelled the contract",
            ($reason ?? 'No reason given.') . $refundNote,
            'bp_contract', $contract->id, $actor->id,
            'notification', $actor->id
        );

        event(new ContractCancelled($contract->fresh(), $actor, $reason));

        return $contract->fresh();
    }


    public function pause(BpContract $contract): BpContract
    {
        $contract->update(['status' => 'paused', 'paused_at' => now()]);
        return $contract->fresh();
    }

    public function resume(BpContract $contract): BpContract
    {
        $contract->update(['status' => 'active', 'resumed_at' => now()]);
        return $contract->fresh();
    }

    public function complete(BpContract $contract): BpContract
    {
        $contract->update(['status' => 'completed', 'completed_at' => now()]);
        return $contract->fresh();
    }

    public function submitMilestone(BpMilestone $milestone, User $submitter): BpMilestone
    {
        $milestone->update([
            'status'       => MilestoneStatus::Submitted->value,
            'submitted_at' => now(),
        ]);

        $contract = $milestone->contract;

        // Actor log — BP's own history ("I submitted milestone X")
        $this->activity->log(
            $submitter->id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            'milestone_submitted',
            "Milestone submitted: {$milestone->title}",
            'Awaiting provider review and approval.',
            'bp_milestone', $milestone->id, null,
            'log', $submitter->id
        );

        // Notification → provider ("Milestone submitted — review needed")
        $this->activity->log(
            $contract->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'milestone_submitted',
            "{$submitter->display_name} submitted milestone: {$milestone->title}",
            'Review and approve to release payment.',
            'bp_milestone', $milestone->id, $submitter->id,
            'notification', $submitter->id
        );

        event(new MilestoneSubmitted($milestone->fresh()));
        return $milestone->fresh();
    }

    /**
     * Provider approves milestone submission → release escrow to BP.
     */
    public function approveMilestone(BpMilestone $milestone, User $approver): BpMilestone
    {
        $status = $milestone->status instanceof MilestoneStatus
            ? $milestone->status->value
            : (string) $milestone->status;

        if ($status !== MilestoneStatus::Submitted->value) {
            throw new \RuntimeException("Only submitted milestones can be approved. Current: {$status}");
        }

        // Mark approved first so releaseMilestone guard passes
        $milestone->update([
            'status'      => MilestoneStatus::Approved->value,
            'approved_at' => now(),
        ]);

        // Update the latest submission audit row
        BpMilestoneSubmission::where('milestone_id', $milestone->id)
            ->whereNull('review_action')
            ->latest()
            ->update([
                'reviewed_by'   => $approver->id,
                'reviewed_at'   => now(),
                'review_action' => 'approved',
            ]);

        // Release escrow → transfer to BP Connect
        $this->escrow->releaseMilestone($milestone->fresh(), $approver);

        event(new MilestoneApproved($milestone->fresh()));

        return $milestone->fresh();
    }

    /**
     * Provider requests revision on a submitted milestone.
     * Returns milestone to revision_requested state — BP can resubmit.
     */
    public function requestRevision(BpMilestone $milestone, User $provider, string $notes): BpMilestone
    {
        $status = $milestone->status instanceof MilestoneStatus
            ? $milestone->status->value
            : (string) $milestone->status;

        if ($status !== MilestoneStatus::Submitted->value) {
            throw new \RuntimeException("Revision can only be requested on submitted milestones.");
        }

        $revisionCount = ((int) $milestone->revision_count) + 1;

        $milestone->update([
            'status'         => MilestoneStatus::RevisionRequested->value,
            'revision_count' => $revisionCount,
            'revision_notes' => $notes,
            'auto_release_at'=> null,   // stop auto-release countdown
        ]);

        // Update submission audit row
        BpMilestoneSubmission::where('milestone_id', $milestone->id)
            ->whereNull('review_action')
            ->latest()
            ->update([
                'reviewed_by'   => $provider->id,
                'reviewed_at'   => now(),
                'review_action' => 'revision_requested',
                'review_notes'  => $notes,
            ]);

        $contract = $milestone->contract;

        $this->activity->log(
            $provider->id, 'provider', 'job_postings', ActivitySeverity::Info,
            'revision_requested',
            "Revision requested: {$milestone->title}",
            "Feedback sent to Business Partner. Revision #{$revisionCount}.",
            'bp_milestone', $milestone->id, $contract->bp_id,
            'log', $provider->id
        );
        $this->activity->log(
            $contract->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Warning,
            'revision_requested',
            "{$provider->display_name} requested a revision: {$milestone->title}",
            $notes,
            'bp_milestone', $milestone->id, $provider->id,
            'notification', $provider->id
        );

        event(new MilestoneRevisionRequested($milestone->fresh(), $provider, $notes));

        return $milestone->fresh();
    }


    public function getForPractitioner(string $practitionerId): Collection
    {
        return BpContract::where('practitioner_id', $practitionerId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getForBp(string $bpId): Collection
    {
        return BpContract::where('bp_id', $bpId)
            ->with(['practitioner:id,display_name,slug', 'milestones'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (BpContract $c) {
                $status = $c->status instanceof \BackedEnum ? $c->status->value : (string) $c->status;
                return [
                    'id'                => $c->id,
                    'title'             => $c->title,
                    'client_name'       => $c->practitioner?->display_name ?? '—',
                    'practitioner_id'   => $c->practitioner_id,
                    'payment_type'      => $c->payment_type,      // one_time | milestone
                    'amount_cents'      => (int) $c->total_value_cents,
                    'status'            => $status,                // active | completed | cancelled
                    'signed_at'               => $c->signed_at?->toDateString(),
                    'completed_at'            => $c->completed_at?->toDateString(),
                    'created_at'              => $c->created_at?->toDateString(),
                    'cancel_reason'           => $c->cancel_reason,
                    // Escrow
                    'escrow_funded_cents'     => (int) ($c->escrow_funded_cents ?? 0),
                    'escrow_released_cents'   => (int) ($c->escrow_released_cents ?? 0),
                    'escrow_refunded_cents'   => (int) ($c->escrow_refunded_cents ?? 0),
                    'funding_mode'            => $c->funding_mode ?? 'per_milestone',
                    // Signature status (for sign CTA in BP portal)
                    'bp_has_signed'           => (bool) $c->bp_signed_at,
                    'provider_has_signed'     => (bool) $c->practitioner_signed_at,
                    'fully_executed'          => (bool) $c->fully_executed_at,
                    // Milestones (for detail modal)
                    'milestones'              => $c->milestones->map(fn ($m) => [
                        'id'           => $m->id,
                        'title'        => $m->title,
                        'amount_cents' => (int) $m->amount_cents,
                        'status'       => $m->status instanceof \BackedEnum ? $m->status->value : (string) $m->status,
                        'due_at'       => $m->due_at?->toDateString(),
                        'funded_cents' => (int) ($m->funded_cents ?? 0),
                        'released_cents' => (int) ($m->released_cents ?? 0),
                    ])->values(),
                ];
            });
    }
}
