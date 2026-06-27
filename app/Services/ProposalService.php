<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Business\ProposalDeclined;

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
            'id'             => 'bpr_' . Str::lower(Str::random(12)),
            'job_id'         => $job->id,
            'bp_id'          => $bp->id,
            'cover_letter'   => $data['cover_letter'] ?? null,
            'bid_cents'      => $data['bid_cents'] ?? 0,
            'timeline_days'  => $data['timeline_days'] ?? null,
            'status'         => 'submitted',
            'submitted_at'   => now(),
        ]);

        $this->activity->log(
            $job->practitioner_id, 'provider', 'payment', ActivitySeverity::Info,
            'proposal_received',
            "{$bp->display_name} submitted a proposal for: {$job->title}",
            "Bid: $" . number_format($proposal->bid_cents / 100, 2),
            'bp_proposal', $proposal->id, $bp->id
        );

        return $proposal;
    }

    /**
     * Accept proposal → decline others on same job → create contract.
     */
    public function accept(BpProposal $proposal): BpContract
    {
        return DB::transaction(function () use ($proposal) {
            $proposal->update(['status' => 'accepted', 'responded_at' => now()]);

            // Decline all other proposals on the same job
            BpProposal::where('job_id', $proposal->job_id)
                ->where('id', '!=', $proposal->id)
                ->where('status', 'submitted')
                ->update(['status' => 'declined', 'responded_at' => now()]);

            $job = BpJob::find($proposal->job_id);

            $contract = BpContract::create([
                'id'              => 'bc_' . Str::lower(Str::random(12)),
                'job_id'          => $job->id,
                'proposal_id'     => $proposal->id,
                'practitioner_id' => $job->practitioner_id,
                'bp_id'           => $proposal->bp_id,
                'total_cents'     => $proposal->bid_cents,
                'status'          => 'active',
                'created_at'      => now(),
            ]);

            $job->update(['status' => 'filled', 'filled_at' => now()]);

            event(new ProposalAccepted($proposal->fresh(), $contract));
            event(new ContractCreated($contract));

            $bp = User::find($proposal->bp_id);
            $practitioner = User::find($job->practitioner_id);

            $this->activity->log(
                $bp->id, 'business_partner', 'payment', ActivitySeverity::Info,
                'proposal_accepted',
                "Your proposal was accepted: {$job->title}",
                'A contract has been created. Review and sign to begin work.',
                'bp_contract', $contract->id, $practitioner->id
            );

            $this->activity->log(
                $practitioner->id, 'provider', 'payment', ActivitySeverity::Info,
                'contract_created',
                "Contract created with {$bp->display_name}",
                "Total: $" . number_format($contract->total_cents / 100, 2),
                'bp_contract', $contract->id, $bp->id
            );

            return $contract;
        });
    }

    public function decline(BpProposal $proposal, ?string $reason = null): BpProposal
    {
        $proposal->update([
            'status' => 'declined',
            'responded_at' => now(),
            'decline_reason' => $reason,
        ]);

        $this->activity->log(
            $proposal->bp_id, 'business_partner', 'payment', ActivitySeverity::Info,
            'proposal_declined',
            'Your proposal was declined',
            $reason ?? 'No reason given.',
            'bp_proposal', $proposal->id
        );

        event(new ProposalDeclined($proposal->fresh(), $reason));

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
            ->whereIn('status', ['submitted', 'accepted'])
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
