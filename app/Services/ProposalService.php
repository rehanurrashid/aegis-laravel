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

        $this->activity->log(
            $job->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'proposal_received',
            "{$bp->display_name} submitted a proposal for: {$job->title}",
            'Bid: $' . number_format($proposal->proposed_rate_cents / 100, 2),
            'bp_proposal', $proposal->id, $bp->id
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

            $contract = BpContract::create([
                'id'                => 'bc_' . Str::lower(Str::random(12)),
                'job_id'            => $job->id,
                'proposal_id'       => $proposal->id,
                'practitioner_id'   => $job->practitioner_id,
                'bp_id'             => $proposal->bp_id,
                'title'             => $job->title,
                'total_value_cents' => $proposal->proposed_rate_cents,
                'status'            => 'active',
                'signed_at'         => now(),
                'started_at'        => now(),
            ]);

            $job->update(['status' => 'filled']);

            event(new ProposalAccepted($proposal->fresh(), $contract));
            event(new ContractCreated($contract));

            $bp = User::find($proposal->bp_id);
            $practitioner = User::find($job->practitioner_id);

            $this->activity->log(
                $bp->id, 'business_partner', 'job_postings', ActivitySeverity::Info,
                'proposal_accepted',
                "Your proposal was accepted: {$job->title}",
                'A contract has been created. Review and sign to begin work.',
                'bp_contract', $contract->id, $practitioner->id
            );

            $this->activity->log(
                $practitioner->id, 'provider', 'job_postings', ActivitySeverity::Info,
                'contract_created',
                "Contract created with {$bp->display_name}",
                "Total: $" . number_format($contract->total_value_cents / 100, 2),
                'bp_contract', $contract->id, $bp->id
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

        $this->activity->log(
            $proposal->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            'proposal_declined',
            'Your proposal was declined',
            $reason ?? 'No reason given.',
            'bp_proposal', $proposal->id
        );

        event(new ProposalDeclined($proposal->fresh(), $reason));

        return $proposal->fresh();
    }

    public function setStage(BpProposal $proposal, string $stage, ?string $note = null, ?string $interviewType = null, ?string $interviewAt = null): BpProposal
    {
        $update = ['pipeline_stage' => $stage];

        // Only update status when transitioning to terminal states.
        // 'under_review' is not a valid ProposalStatus — leave status as 'pending'
        // for all intermediate pipeline stages (reviewed, shortlisted, interview).
        // accepted/declined/withdrawn are set by accept()/decline()/withdraw().

        if ($note) {
            $existing = $proposal->internal_notes ? $proposal->internal_notes . "\n" : '';
            $update['internal_notes'] = trim($existing . $note);
        }
        if ($stage === 'interview') {
            $update['interview_type'] = $interviewType;
            $update['interview_at']   = $interviewAt;
        }
        $proposal->update($update);
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
}
