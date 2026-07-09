<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Business\ContractCancelled;
use App\Events\Business\MilestoneApproved;
use App\Events\Business\MilestoneSubmitted;
use App\Enums\ActivitySeverity;
use App\Enums\MilestoneStatus;
use App\Events\Business\ContractSigned;
use App\Models\BpContract;
use App\Models\BpMilestone;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ContractService
{
    public function __construct(private ActivityService $activity) {}

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

    public function sign(BpContract $contract, User $signer, array $signature): BpContract
    {
        $isPractitioner = $contract->practitioner_id === $signer->id;
        $isBp = $contract->bp_id === $signer->id;

        $update = [];
        if ($isPractitioner) {
            $update['practitioner_signed_at'] = now();
            $update['practitioner_signature_name'] = $signature['name'] ?? $signer->display_name;
        } elseif ($isBp) {
            $update['bp_signed_at'] = now();
            $update['bp_signature_name'] = $signature['name'] ?? $signer->display_name;
        }
        $contract->update($update);

        // Both signed → mark fully executed
        $fresh = $contract->fresh();
        if ($fresh->practitioner_signed_at && $fresh->bp_signed_at) {
            $fresh->update(['fully_executed_at' => now()]);
            event(new ContractSigned($fresh));
        }

        $otherId     = $isPractitioner ? $contract->bp_id : $contract->practitioner_id;
        $otherPortal = $isPractitioner ? 'business_partner' : 'provider';
        $fullyExecuted = (bool) $fresh->fully_executed_at;

        // Actor log — signer's own history ("I signed the contract")
        $this->activity->log(
            $signer->id,
            $isPractitioner ? 'provider' : 'business_partner',
            'job_postings', ActivitySeverity::Info,
            'contract_signed',
            'You signed the contract: ' . $contract->title,
            $fullyExecuted ? 'Contract is now fully executed.' : 'Awaiting the other party\'s signature.',
            'bp_contract', $contract->id, null,
            'log', $signer->id
        );

        // Notification → other party ("Party X signed the contract")
        $this->activity->log(
            $otherId, $otherPortal, 'job_postings', ActivitySeverity::Info,
            'contract_signed',
            "{$signer->display_name} signed the contract",
            $fullyExecuted ? 'Contract is now fully executed.' : 'Awaiting your signature.',
            'bp_contract', $contract->id, $signer->id,
            'notification', $signer->id
        );

        // Email the other party on every signature (first sign + fully executed)
        // ContractSigned is only fired when both sign; use SendEmailJob directly for mid-sign
        if (!$fullyExecuted) {
            \App\Jobs\SendEmailJob::dispatch(
                'emails.gaps.66-contract-signed',
                ['contract_id' => $contract->id, 'user_id' => $otherId],
                $otherId
            )->onQueue('email');
        }
        // When fully executed, ContractSigned event already fires above and handles both parties

        return $fresh;
    }

    public function cancel(BpContract $contract, User $actor, ?string $reason = null): BpContract
    {
        $contract->update([
            'status'      => 'cancelled',
            'cancelled_at'=> now(),
            'cancel_reason'=> $reason,
        ]);

        $otherId  = $contract->practitioner_id === $actor->id
            ? $contract->bp_id
            : $contract->practitioner_id;
        $otherUser   = User::find($otherId);
        $otherPortal = $otherUser?->role === 'business_partner' ? 'business_partner' : 'provider';
        $actorPortal = $actor->id === $contract->practitioner_id ? 'provider' : 'business_partner';

        // Actor log — who cancelled ("I ended this contract")
        $this->activity->log(
            $actor->id, $actorPortal, 'job_postings', ActivitySeverity::Warning,
            'contract_cancelled',
            'You cancelled the contract: ' . $contract->title,
            $reason ?? 'No reason recorded.',
            'bp_contract', $contract->id, null,
            'log', $actor->id
        );

        // Notification → other party ("Party X cancelled the contract")
        $this->activity->log(
            $otherId, $otherPortal, 'job_postings', ActivitySeverity::Warning,
            'contract_cancelled',
            "{$actor->display_name} cancelled the contract",
            $reason ?? 'No reason given.',
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

    public function approveMilestone(BpMilestone $milestone, User $approver): BpMilestone
    {
        $milestone->update([
            'status'      => MilestoneStatus::Approved->value,
            'approved_at' => now(),
        ]);

        $contract = $milestone->contract;

        // Actor log — provider's own history ("I approved milestone X")
        $this->activity->log(
            $approver->id, 'provider', 'job_postings', ActivitySeverity::Info,
            'milestone_approved',
            "Milestone approved: {$milestone->title}",
            'Payout will be processed within 3 business days.',
            'bp_milestone', $milestone->id, null,
            'log', $approver->id
        );

        // Notification → BP ("Milestone approved — payout coming")
        $this->activity->log(
            $contract->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            'milestone_approved',
            "{$approver->display_name} approved milestone: {$milestone->title}",
            'Payout will be processed shortly.',
            'bp_milestone', $milestone->id, $approver->id,
            'notification', $approver->id
        );

        event(new MilestoneApproved($milestone->fresh()));
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
            ->with(['practitioner:id,display_name,slug'])
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
                    'signed_at'         => $c->signed_at?->toDateString(),
                    'completed_at'      => $c->completed_at?->toDateString(),
                    'created_at'        => $c->created_at?->toDateString(),
                ];
            });
    }
}
