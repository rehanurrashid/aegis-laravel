<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Business\ProposalDeclined;
use App\Events\Business\ProposalSubmitted;

use App\Enums\ActivitySeverity;
use App\Events\Business\ContractCreated;
use App\Events\Business\ProposalAccepted;
use App\Models\BpContract;
use App\Models\BpJob;
use App\Models\BpProposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProposalService
{
    public function __construct(private ActivityService $activity) {}

    public function submit(BpJob $job, User $bp, array $data): BpProposal
    {
        if ($bp->role !== 'business_partner') {
            throw new RuntimeException('Only Business Partners can submit proposals.');
        }
        if ($job->status !== 'open') {
            throw new RuntimeException('Job is not open for proposals.');
        }
        if (BpProposal::where('job_id', $job->id)->where('bp_id', $bp->id)->exists()) {
            throw new RuntimeException('You have already submitted a proposal for this job.');
        }

        $proposal = BpProposal::create([
            'id'                  => 'bpr_' . Str::lower(Str::random(12)),
            'job_id'              => $job->id,
            'bp_id'               => $bp->id,
            'cover_letter'        => $data['cover_letter'] ?? null,
            'proposed_rate_cents' => $data['proposed_rate_cents'] ?? $data['bid_cents'] ?? 0,
            'proposed_rate_type'  => $data['proposed_rate_type'] ?? $job->budget_type?->value ?? 'fixed',
            'status'              => 'pending',
            'pipeline_stage'      => 'new',
            'submitted_at'        => now(),
        ]);

        // Actor log — BP's own history ("I submitted a proposal")
        $this->activity->log(
            $bp->id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            'proposal_submitted',
            "Proposal submitted: {$job->title}",
            'Your proposal is under review. You\'ll be notified when the provider responds.',
            'bp_proposal', $proposal->id, null,
            'log', $bp->id
        );

        // Notification → provider ("New application received")
        $this->activity->log(
            $job->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'proposal_received',
            "{$bp->display_name} submitted a proposal for: {$job->title}",
            'Bid: $' . number_format($proposal->proposed_rate_cents / 100, 2),
            'bp_proposal', $proposal->id, $bp->id,
            'notification', $bp->id
        );

        event(new ProposalSubmitted($proposal));

        return $proposal;
    }

    /**
     * Accept proposal → decline others on same job → create contract.
     */
    public function accept(BpProposal $proposal, ?int $finalRateCents = null): BpContract
    {
        return DB::transaction(function () use ($proposal, $finalRateCents) {
            // Build the proposal update regardless — ensures status and pipeline_stage
            // are always correct even when re-calling accept() on an already-hired proposal.
            $update = ['status' => 'accepted', 'pipeline_stage' => 'hired', 'responded_at' => now()];
            if ($finalRateCents !== null) {
                $update['proposed_rate_cents'] = $finalRateCents;
            }

            // Idempotency guard: if a contract already exists, still sync the proposal
            // fields (they may have been left stale by a previous partial call) but
            // return the existing contract instead of creating a duplicate.
            $existing = BpContract::where('proposal_id', $proposal->id)->first();
            if ($existing) {
                $proposal->update($update);
                return $existing;
            }

            $proposal->update($update);

            // Decline all other proposals on the same job
            BpProposal::where('job_id', $proposal->job_id)
                ->where('id', '!=', $proposal->id)
                ->where('status', 'pending')
                ->update(['status' => 'declined', 'pipeline_stage' => 'rejected', 'responded_at' => now()]);

            $job = BpJob::find($proposal->job_id);

            $contractId    = 'bc_' . Str::lower(Str::random(12));
            $transferGroup = 'aegis_contract_' . $contractId;

            $contract = BpContract::create([
                'id'                => $contractId,
                'job_id'            => $job->id,
                'proposal_id'       => $proposal->id,
                'practitioner_id'   => $job->practitioner_id,
                'bp_id'             => $proposal->bp_id,
                'title'             => $job->title,
                'total_value_cents' => $proposal->proposed_rate_cents,
                'payment_type'      => $job->budget_type?->value === 'fixed' ? 'one_time' : 'milestone',
                'funding_mode'      => 'per_milestone',
                // Escrow-backed: born in pending_signature, not active
                'status'            => 'pending_signature',
                'transfer_group'    => $transferGroup,
                'terms'             => $this->generateTerms($job, $proposal),
            ]);

            $job->update(['status' => 'filled']);

            event(new ProposalAccepted($proposal->fresh(), $contract));
            event(new ContractCreated($contract));

            $bp           = User::find($proposal->bp_id);
            $practitioner = User::find($job->practitioner_id);

            // Actor log — provider's own history ("I hired Acme Health Services")
            $this->activity->log(
                $practitioner->id, 'provider', 'job_postings', ActivitySeverity::Info,
                'proposal_accepted',
                "You hired {$bp->display_name} for: {$job->title}",
                'Contract created. Work can begin immediately.',
                'bp_contract', $contract->id, $bp->id,
                'log', $practitioner->id
            );

            // Notification → BP ("Your proposal was accepted")
            $this->activity->log(
                $bp->id, 'business_partner', 'job_postings', ActivitySeverity::Info,
                'proposal_accepted',
                "Your proposal was accepted: {$job->title}",
                'A contract has been created. Review the terms and sign to unlock escrow funding.',
                'bp_contract', $contract->id, $practitioner->id,
                'notification', $practitioner->id
            );

            // Actor log — provider's contract history ("Contract created with Acme")
            $this->activity->log(
                $practitioner->id, 'provider', 'job_postings', ActivitySeverity::Info,
                'contract_created',
                "Contract created with {$bp->display_name}",
                'Total: $' . number_format($contract->total_value_cents / 100, 2),
                'bp_contract', $contract->id, $bp->id,
                'log', $practitioner->id
            );

            return $contract;
        });
    }

    public function decline(BpProposal $proposal, ?string $reason = null): BpProposal
    {
        $proposal->update([
            'status' => 'declined',
            'pipeline_stage' => 'rejected',
            'responded_at' => now(),
            'decline_reason' => $reason,
        ]);

        $job = BpJob::find($proposal->job_id);

        // Actor log — provider's own history ("I declined Jamal Torres")
        $this->activity->log(
            $job->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'proposal_declined',
            'Application declined: ' . ($job->title ?? 'Job posting'),
            $reason ?? 'No reason recorded.',
            'bp_proposal', $proposal->id, $proposal->bp_id,
            'log', $job->practitioner_id
        );

        // Notification → BP ("Your proposal was not selected")
        $this->activity->log(
            $proposal->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            'proposal_declined',
            'Your proposal was not selected',
            $reason ?? 'No reason given.',
            'bp_proposal', $proposal->id, $job->practitioner_id,
            'notification', $job->practitioner_id
        );

        event(new ProposalDeclined($proposal->fresh(), $reason));

        return $proposal->fresh();
    }

    public function setStage(BpProposal $proposal, string $stage, ?string $note = null, ?string $interviewType = null, ?string $interviewAt = null): BpProposal
    {
        $update = ['pipeline_stage' => $stage];

        if ($note) {
            $existing = $proposal->internal_notes ? $proposal->internal_notes . "\n" : '';
            $update['internal_notes'] = trim($existing . $note);
        }
        if ($stage === 'interview') {
            $update['interview_type'] = $interviewType;
            $update['interview_at']   = $interviewAt;
        }
        $proposal->update($update);

        $job        = BpJob::find($proposal->job_id);
        $stageLabel = match ($stage) {
            'reviewed'    => 'Marked as Reviewed',
            'shortlisted' => 'Shortlisted',
            'interview'   => 'Scheduled for Interview',
            default       => ucfirst($stage),
        };

        // Actor log — provider's pipeline management history
        $this->activity->log(
            $job->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'applicant_stage_changed',
            "Applicant moved to {$stageLabel}: " . ($proposal->bp?->display_name ?? 'Applicant'),
            $note ?? '',
            'bp_proposal', $proposal->id, $proposal->bp_id,
            'log', $job->practitioner_id
        );

        // Notification → BP when shortlisted or scheduled for interview
        if (in_array($stage, ['shortlisted', 'interview'], true)) {
            $notifTitle = $stage === 'interview'
                ? 'You have been scheduled for an interview'
                : 'You have been shortlisted';
            $notifDesc  = $stage === 'interview'
                ? 'For "' . $job->title . '". Check your messages for scheduling details.'
                : 'Your application for "' . $job->title . '" has been shortlisted.';
            $this->activity->log(
                $proposal->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Info,
                'proposal_stage_updated',
                $notifTitle,
                $notifDesc,
                'bp_proposal', $proposal->id, $job->practitioner_id,
                'notification', $job->practitioner_id
            );
        }

        return $proposal->fresh();
    }

    public function setNotes(BpProposal $proposal, ?string $notes): BpProposal
    {
        $proposal->update(['internal_notes' => $notes]);
        return $proposal->fresh();
    }

    public function withdraw(BpProposal $proposal): BpProposal
    {
        $proposal->update(['status' => 'withdrawn']);

        $job = BpJob::find($proposal->job_id);
        $bp  = User::find($proposal->bp_id);

        // Actor log — BP's own history
        $this->activity->log(
            $proposal->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            'proposal_withdrawn',
            'Proposal withdrawn: ' . ($job?->title ?? 'Job posting'),
            'Your proposal has been withdrawn successfully.',
            'bp_proposal', $proposal->id, null,
            'log', $proposal->bp_id
        );

        // Notification → provider
        if ($job) {
            $this->activity->log(
                $job->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
                'proposal_withdrawn',
                ($bp?->display_name ?? 'A Business Partner') . ' withdrew their proposal',
                'Their application for "' . $job->title . '" has been withdrawn.',
                'bp_proposal', $proposal->id, $proposal->bp_id,
                'notification', $proposal->bp_id
            );
        }

        event(new \App\Events\Business\ProposalWithdrawn($proposal->fresh(), $bp ?? User::findOrFail($proposal->bp_id)));

        return $proposal->fresh();
    }

    public function getForJob(string $jobId): Collection
    {
        return BpProposal::where('job_id', $jobId)
            ->whereIn('status', ['pending', 'under_review', 'accepted'])
            ->orderByDesc('submitted_at')
            ->get();
    }

    public function getForBp(string $bpId): Collection
    {
        return BpProposal::where('bp_id', $bpId)
            ->orderByDesc('submitted_at')
            ->get();
    }

    /**
     * Generate plain-text contract terms from job + proposal data.
     * Used as the basis for the dual-signature contract (Wave 6 adds PDF export).
     */
    private function generateTerms(BpJob $job, BpProposal $proposal): string
    {
        $rate    = '$' . number_format(($proposal->proposed_rate_cents ?? 0) / 100, 2);
        $rateType = $proposal->proposed_rate_type instanceof \App\Enums\BpBudgetType
            ? $proposal->proposed_rate_type->value
            : (string) ($proposal->proposed_rate_type ?? 'fixed');
        $rateLabel = match ($rateType) {
            'hourly'   => "{$rate} per hour",
            'retainer' => "{$rate} per month",
            default    => "{$rate} (fixed price)",
        };
        $timeline = $proposal->timeline_days
            ? ($proposal->timeline_days . ' days estimated')
            : 'Timeline to be agreed between parties';

        $hipaa = $job->requires_hipaa ? "\n• HIPAA Business Associate Agreement required." : '';
        $nda   = $job->requires_nda   ? "\n• Non-Disclosure Agreement required." : '';
        $baa   = $job->requires_baa   ? "\n• Business Associate Agreement required." : '';

        return <<<TEXT
SERVICE CONTRACT — AEGIS PLATFORM

Role: {$job->title}
Category: {$job->category}
Rate: {$rateLabel}
Timeline: {$timeline}

SCOPE OF WORK
{$job->description}

COMPLIANCE REQUIREMENTS{$hipaa}{$nda}{$baa}

PAYMENT TERMS
All payments are processed through Aegis Platform. Funds are held in escrow by
Aegis until work is approved by the Provider. For milestone-based contracts, each
milestone is funded and released independently. The Provider agrees to fund each
milestone before work begins. The Business Partner agrees not to invoice outside
of the Aegis Platform for work covered by this contract.

AUTO-RELEASE POLICY
If the Provider does not review a submitted milestone within the review window
(typically 7 days), the escrow funds are automatically released to the Business
Partner.

DISPUTE RESOLUTION
Disputes are mediated by Aegis Platform administration. Both parties agree to
engage in good faith before escalating. Aegis's decision on escrow release or
refund is final within the platform.

GOVERNING LAW
This contract is governed by applicable United States law.

By signing below, both parties agree to these terms and to Aegis Platform's
Terms of Service (https://aegis.devlet.tech/terms).
TEXT;
    }
}
