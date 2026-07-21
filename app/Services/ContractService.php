<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Business\ContractCancelled;
use App\Events\Business\ContractCompleted;
use App\Events\Business\MilestoneApproved;
use App\Events\Business\MilestoneRevisionRequested;
use App\Events\Business\MilestoneSubmitted;
use App\Enums\ActivitySeverity;
use App\Enums\ContractStatus;
use App\Enums\MilestoneStatus;
use App\Enums\PaymentStructure;
use App\Events\Business\ContractSigned;
use App\Events\Business\MilestonePaymentFailed;
use App\Models\BpContract;
use App\Models\BpContractTerms;
use App\Models\BpMilestone;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractService
{
    public function __construct(
        private ActivityService $activity,
        private PayoutService   $payouts,
    ) {}

    public function create(string $jobId, string $proposalId, string $practitionerId, string $bpId, int $totalCents, string $paymentType = 'one_time', string $fundingMode = 'per_milestone'): BpContract
    {
        // Milestone-based contracts start as pending_funding — at least one milestone
        // must be added and funded before work begins. One-time contracts go active immediately.
        $initialStatus = $paymentType === 'milestone' ? 'pending_funding' : 'active';

        return BpContract::create([
            'id'                => 'bc_' . Str::lower(Str::random(12)),
            'job_id'            => $jobId,
            'proposal_id'       => $proposalId,
            'practitioner_id'   => $practitionerId,
            'bp_id'             => $bpId,
            'title'             => 'Contract',
            'total_value_cents' => $totalCents,
            'payment_type'      => $paymentType,
            'funding_mode'      => $fundingMode,
            'status'            => $initialStatus,
        ]);
    }

    public function sign(BpContract $contract, User $signer, array $signature): BpContract
    {
        $isPractitioner = $contract->practitioner_id === $signer->id;
        $isBp           = $contract->bp_id === $signer->id;

        if (!$isPractitioner && !$isBp) {
            abort(403, 'You are not a party to this contract.');
        }

        $update = [];
        if ($isPractitioner) {
            $update['practitioner_signed_at']      = now();
            $update['practitioner_signature_name'] = $signature['name'] ?? $signer->display_name;
        } else {
            $update['bp_signed_at']      = now();
            $update['bp_signature_name'] = $signature['name'] ?? $signer->display_name;
        }
        $contract->update($update);
        $contract->refresh();

        $otherId     = $isPractitioner ? $contract->bp_id : $contract->practitioner_id;
        $otherPortal = $isPractitioner ? 'business_partner' : 'provider';

        // Not both signed yet
        if (!$contract->practitioner_signed_at || !$contract->bp_signed_at) {
            $this->activity->log(
                $signer->id, $isPractitioner ? 'provider' : 'business_partner',
                'job_postings', ActivitySeverity::Info,
                'contract_signed', 'You signed the contract: ' . $contract->title,
                "Awaiting the other party's signature.",
                'bp_contract', $contract->id, null, 'log', $signer->id
            );
            $this->activity->log(
                $otherId, $otherPortal,
                'job_postings', ActivitySeverity::Info,
                'contract_pending_signature', $signer->display_name . ' signed the contract',
                'Your signature is needed to activate the contract.',
                'bp_contract', $contract->id, $signer->id, 'notification', $signer->id
            );
            \App\Jobs\SendEmailJob::dispatch(
                'emails.gaps.66-contract-signed',
                ['contract_id' => $contract->id, 'user_id' => $otherId],
                $otherId
            )->onQueue('email');
            return $contract;
        }

        // Both signed
        return DB::transaction(function () use ($contract, $signer, $isPractitioner, $otherId, $otherPortal) {
            // 1. Snapshot terms
            if ($contract->payment_structure && !BpContractTerms::where('contract_id', $contract->id)->exists()) {
                BpContractTerms::create([
                    'id'                 => 'bct_' . Str::lower(Str::random(12)),
                    'contract_id'        => $contract->id,
                    'payment_structure'  => $contract->payment_structure,
                    'upfront_percentage' => $contract->upfront_percentage ?? 0,
                    'upfront_cents'      => $contract->upfront_cents ?? 0,
                    'remaining_cents'    => $contract->remaining_cents ?? $contract->total_value_cents,
                    'total_value_cents'  => $contract->total_value_cents,
                    'terms_note'         => $contract->terms_note,
                    'terms_source'       => $contract->terms_source ?? 'provider_default',
                    'snapshotted_at'     => now(),
                ]);
            }

            // 2. Mark active
            $contract->update([
                'fully_executed_at' => now(),
                'status'            => ContractStatus::Active->value,
            ]);
            $contract->refresh();

            // 3. Upfront charge for full_upfront or split
            $structure = $contract->payment_structure;
            if ($structure && $structure->hasUpfrontCharge() && ($contract->upfront_cents ?? 0) > 0) {
                try {
                    $this->payouts->chargeContractUpfront($contract);
                    $contract->refresh();
                } catch (\Throwable $e) {
                    $contract->update(['payment_failed_at' => now()]);
                    $this->activity->log(
                        $contract->practitioner_id, 'provider', 'job_postings',
                        ActivitySeverity::Critical, 'contract_upfront_payment_failed',
                        'Upfront charge failed — ' . $contract->title, $e->getMessage(),
                        'bp_contract', $contract->id, $contract->bp_id,
                        'notification', $contract->practitioner_id
                    );
                }
            }

            // 4. full_upfront + no failure → prepay milestones + complete
            $contract->refresh();
            if ($structure === PaymentStructure::FullUpfront && !$contract->payment_failed_at) {
                BpMilestone::where('contract_id', $contract->id)->update([
                    'status'     => MilestoneStatus::Prepaid->value,
                    'paid_at'    => now(),
                    'paid_cents' => DB::raw('amount_cents'),
                ]);
                $contract->update([
                    'status'       => ContractStatus::Completed->value,
                    'completed_at' => now(),
                ]);
                $contract->refresh();
            }

            // 5. Activity + event
            $this->activity->log(
                $signer->id, $isPractitioner ? 'provider' : 'business_partner',
                'job_postings', ActivitySeverity::Info,
                'contract_signed', 'You signed the contract: ' . $contract->title,
                'Contract is now fully executed and active.',
                'bp_contract', $contract->id, null, 'log', $signer->id
            );
            $this->activity->log(
                $otherId, $otherPortal, 'job_postings', ActivitySeverity::Info,
                'contract_signed', $signer->display_name . ' signed the contract',
                'Contract is now fully executed and active.',
                'bp_contract', $contract->id, $signer->id, 'notification', $signer->id
            );

            event(new ContractSigned($contract->fresh()));
            return $contract->fresh();
        });
    }

    public function cancel(BpContract $contract, User $actor, ?string $reason = null): BpContract
    {
        if (in_array($contract->status?->value, ['completed', 'cancelled'], true)) {
            throw new \RuntimeException('Contract is already ' . $contract->status?->value . '.');
        }

        // Rev 2 — refund upfront charge if one was made
        if (($contract->paid_cents ?? 0) > 0 && $contract->upfront_charge_intent_id) {
            try {
                $this->payouts->refundContractUpfront(
                    $contract, $actor, 'Contract cancelled: ' . ($reason ?? 'no reason given')
                );
                $contract->refresh();
            } catch (\Throwable $e) {
                // Log failure but proceed with cancellation — admin will reconcile
                $this->activity->log(
                    $actor->id, $actor->id === $contract->practitioner_id ? 'provider' : 'business_partner',
                    'job_postings', ActivitySeverity::Critical, 'contract_refund_failed',
                    'Refund failed on cancel — ' . $contract->title, $e->getMessage(),
                    'bp_contract', $contract->id, null, 'log', $actor->id
                );
            }
        }

        $contract->update([
            'status'        => ContractStatus::Cancelled->value,
            'cancelled_at'  => now(),
            'cancel_reason' => $reason,
        ]);

        $otherId     = $contract->practitioner_id === $actor->id ? $contract->bp_id : $contract->practitioner_id;
        $otherUser   = User::find($otherId);
        $otherPortal = $otherUser?->role === 'business_partner' ? 'business_partner' : 'provider';
        $actorPortal = $actor->id === $contract->practitioner_id ? 'provider' : 'business_partner';

        $this->activity->log(
            $actor->id, $actorPortal, 'job_postings', ActivitySeverity::Warning,
            'contract_cancelled', 'You cancelled the contract: ' . $contract->title,
            $reason ?? 'No reason recorded.',
            'bp_contract', $contract->id, null, 'log', $actor->id
        );
        $this->activity->log(
            $otherId, $otherPortal, 'job_postings', ActivitySeverity::Warning,
            'contract_cancelled', $actor->display_name . ' cancelled the contract',
            $reason ?? 'No reason given.',
            'bp_contract', $contract->id, $actor->id, 'notification', $actor->id
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
        $fresh = $contract->fresh();
        event(new ContractCompleted($fresh));
        return $fresh;
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

    public function approveMilestone(BpMilestone $milestone, ?User $approver): BpMilestone
    {
        $milestone->update([
            'status'      => MilestoneStatus::Approved->value,
            'approved_at' => now(),
        ]);

        $contract = $milestone->contract;

        try {
            // Fire direct charge synchronously (Rev 2 contracts only)
            $this->payouts->chargeMilestone($milestone->fresh());
            $milestone->refresh();

            // Auto-complete contract if all milestones paid
            if ($this->allMilestonesPaid($contract)) {
                $contract->update([
                    'status'       => ContractStatus::Completed->value,
                    'completed_at' => now(),
                ]);
                event(new ContractCompleted($contract->fresh()));
            }
        } catch (\Throwable $e) {
            $milestone->update([
                'status'            => MilestoneStatus::Approved->value,
                'payment_failed_at' => now(),
            ]);
            event(new MilestonePaymentFailed($milestone->fresh(), $e->getMessage()));
            $this->activity->log(
                $contract->practitioner_id, 'provider', 'job_postings',
                ActivitySeverity::Critical, 'milestone_payment_failed',
                'Milestone payment failed — ' . $milestone->title, $e->getMessage(),
                'bp_milestone', $milestone->id, $contract->bp_id,
                'notification', $contract->practitioner_id
            );
        }

        if ($approver) {
            $this->activity->log(
                $approver->id, 'provider', 'job_postings', ActivitySeverity::Info,
                'milestone_approved', 'Milestone approved: ' . $milestone->title,
                'Direct payment sent to Business Partner.',
                'bp_milestone', $milestone->id, null, 'log', $approver->id
            );
        }

        event(new MilestoneApproved($milestone->fresh()));
        return $milestone->fresh();
    }

    /**
     * Checks if all non-cancelled milestones on a contract are paid.
     */
    private function allMilestonesPaid(BpContract $contract): bool
    {
        return !BpMilestone::where('contract_id', $contract->id)
            ->whereNotIn('status', [
                MilestoneStatus::Paid->value,
                MilestoneStatus::Prepaid->value,
                MilestoneStatus::Cancelled->value,
                MilestoneStatus::Released->value,
            ])
            ->exists();
    }

    /**
     * Complete a contract and fire completion charge for on_completion / split-remainder.
     * For per_milestone contracts, all milestones must be paid first.
     *
     * @throws \RuntimeException if not authorized or already completed
     */
    public function completeContract(BpContract $contract, User $actor): BpContract
    {
        if ($contract->practitioner_id !== $actor->id) {
            abort(403, 'Only the practitioner can mark a contract complete.');
        }

        if (in_array($contract->status?->value, ['completed', 'cancelled'], true)) {
            throw new \RuntimeException('Contract is already ' . $contract->status?->value . '.');
        }

        // Fire completion charge if there is an outstanding balance
        $remaining = ($contract->total_value_cents ?? 0) - ($contract->paid_cents ?? 0);
        if ($remaining > 0 && $contract->payment_structure) {
            $this->payouts->chargeContractCompletion($contract);
            $contract->refresh();
        }

        $contract->update([
            'status'       => ContractStatus::Completed->value,
            'completed_at' => now(),
        ]);

        $this->activity->log(
            $actor->id, 'provider', 'job_postings', ActivitySeverity::Info,
            'contract_completed', 'Contract marked complete — ' . $contract->title,
            'All payments have been settled.',
            'bp_contract', $contract->id, $contract->bp_id, 'log', $actor->id
        );
        $this->activity->log(
            $contract->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            'contract_completed', $actor->display_name . ' marked the contract complete',
            'All payments have been settled.',
            'bp_contract', $contract->id, $actor->id, 'notification', $actor->id
        );

        event(new ContractCompleted($contract->fresh()));
        return $contract->fresh();
    }

    /**
     * Cancel a milestone pre-payment. Throws if already paid.
     */
    public function cancelMilestone(BpMilestone $milestone, User $actor): BpMilestone
    {
        $statusVal = $milestone->status instanceof \BackedEnum
            ? $milestone->status->value
            : (string) $milestone->status;

        if (in_array($statusVal, ['paid', 'released', 'prepaid'], true)) {
            throw new \RuntimeException('Milestone already paid. Open a dispute to seek a refund.');
        }

        $milestone->update([
            'status'       => MilestoneStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);

        return $milestone->fresh();
    }

    public function requestRevision(BpMilestone $milestone, User $provider, string $revisionNotes): BpMilestone
    {
        $contract      = $milestone->contract;
        $revisionCount = ((int) ($milestone->revision_count ?? 0)) + 1;

        $milestone->update([
            'status'         => MilestoneStatus::RevisionRequested->value,
            'revision_count' => $revisionCount,
            'revision_notes' => $revisionNotes,
        ]);

        // Update latest submission review_action
        $submission = \App\Models\BpMilestoneSubmission::where('milestone_id', $milestone->id)
            ->whereNull('review_action')
            ->latest()
            ->first();

        if ($submission) {
            $submission->update([
                'reviewed_by'   => $provider->id,
                'reviewed_at'   => now(),
                'review_action' => 'revision',
                'review_notes'  => $revisionNotes,
            ]);
        }

        // Actor log — provider's history
        $this->activity->log(
            $provider->id, 'provider', 'job_postings', ActivitySeverity::Info,
            'milestone_revision_requested',
            "Revision requested: {$milestone->title}",
            'Revision notes sent to the Business Partner.',
            'bp_milestone', $milestone->id, $contract->bp_id,
            'log', $provider->id
        );

        // Notification → BP
        $this->activity->log(
            $contract->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Warning,
            'milestone_revision_requested',
            "{$provider->display_name} requested a revision on: {$milestone->title}",
            'Review the feedback and resubmit your milestone.',
            'bp_milestone', $milestone->id, $provider->id,
            'notification', $provider->id
        );

        event(new MilestoneRevisionRequested($milestone->fresh(), $provider, $revisionNotes));
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
        $contracts = BpContract::where('bp_id', $bpId)
            ->with(['practitioner:id,display_name,slug', 'milestones'])
            ->orderByDesc('created_at')
            ->get();

        // Pre-fetch review records for this BP to avoid N+1
        $myReviews = \App\Models\BpContractReview::where('reviewer_id', $bpId)
            ->whereIn('contract_id', $contracts->pluck('id'))
            ->where('review_dismissed', false)
            ->get(['contract_id', 'rating', 'review_text', 'is_public'])
            ->keyBy('contract_id');

        return $contracts->map(function (BpContract $c) use ($myReviews) {
                $status = $c->status instanceof \BackedEnum ? $c->status->value : (string) $c->status;
                $review = $myReviews->get($c->id);
                return [
                    'id'                      => $c->id,
                    'title'                   => $c->title,
                    'client_name'             => $c->practitioner?->display_name ?? '—',
                    'practitioner_id'         => $c->practitioner_id,
                    'payment_type'            => $c->payment_type ?? 'one_time',
                    'funding_mode'            => $c->funding_mode ?? 'per_milestone',
                    'amount_cents'            => (int) $c->total_value_cents,
                    'status'                  => $status,
                    'has_reviewed'            => !is_null($review),
                    'my_review'               => $review ? [
                        'rating'      => $review->rating,
                        'review_text' => $review->review_text,
                        'is_public'   => $review->is_public,
                    ] : null,
                    'cancel_reason'           => $c->cancel_reason,
                    'terms'                   => $c->terms,
                    // Escrow
                    'escrow_funded_cents'     => (int) ($c->escrow_funded_cents ?? 0),
                    'escrow_released_cents'   => (int) ($c->escrow_released_cents ?? 0),
                    'escrow_refunded_cents'   => (int) ($c->escrow_refunded_cents ?? 0),
                    // Timestamps
                    'signed_at'               => $c->signed_at?->toDateString(),
                    'completed_at'            => $c->completed_at?->toDateString(),
                    'created_at'              => $c->created_at?->toDateString(),
                    // Signature status
                    'provider_has_signed'     => (bool) $c->practitioner_signed_at,
                    'bp_has_signed'           => (bool) $c->bp_signed_at,
                    'fully_executed'          => (bool) $c->fully_executed_at,
                    // Milestones (for detail modal + milestone page context)
                    'milestones'              => $c->milestones->sortBy('sort_order')->map(fn ($m) => [
                        'id'               => $m->id,
                        'title'            => $m->title,
                        'description'      => $m->description,
                        'amount_cents'     => (int) $m->amount_cents,
                        'status'           => $m->status instanceof \BackedEnum ? $m->status->value : (string) $m->status,
                        'due_at'           => $m->due_at?->toDateString(),
                        'funded_cents'     => (int) ($m->funded_cents ?? 0),
                        'released_cents'   => (int) ($m->released_cents ?? 0),
                        'revision_count'   => (int) ($m->revision_count ?? 0),
                        'revision_notes'   => $m->revision_notes,
                        'rejection_reason' => $m->rejection_reason,
                        'auto_release_at'  => $m->auto_release_at?->toISOString(),
                    ])->values(),
                ];
            });
    }
}
