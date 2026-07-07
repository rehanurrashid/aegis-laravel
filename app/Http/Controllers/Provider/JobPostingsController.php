<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\CreateJobRequest;
use App\Http\Requests\Business\JobStatusRequest;
use App\Http\Requests\Business\ProposalNoteRequest;
use App\Http\Requests\Business\ProposalStageRequest;
use App\Models\BpMilestone;
use App\Http\Requests\Business\UpdateJobRequest;
use App\Models\BpContract;
use App\Models\BpEngagementRequest;
use App\Models\BpJob;
use App\Models\BpProposal;
use App\Services\BpJobService;
use App\Services\ContractService;
use App\Services\MessagingService;
use App\Services\PayoutService;
use App\Services\ProposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobPostingsController extends Controller
{
    public function __construct(
        private BpJobService $jobs,
        private ProposalService $proposals,
        private ContractService $contracts,
        private MessagingService $messaging,
        private PayoutService $payouts,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $jobs = BpJob::where('practitioner_id', $user->id)->orderByDesc('posted_at')->get();

        // Load proposal IDs that have a contract — used as a hired fallback
        // in case proposal status/pipeline_stage are stale (idempotency bug backfill).
        // Filter out null proposal_ids (contract pre-dates the unique constraint migration).
        $contractedProposalIds = BpContract::whereIn('job_id', $jobs->pluck('id'))
            ->whereNotNull('proposal_id')
            ->pluck('proposal_id')
            ->mapWithKeys(fn ($id) => [$id => true]); // O(1) set lookup

        $proposalsByJob = BpProposal::with('bp:id,display_name,bp_type,avatar_initials,avatar_path,location,verified,slug')
            ->whereIn('job_id', $jobs->pluck('id'))
            ->orderByDesc('submitted_at')
            ->get()
            ->each(function ($p) use ($contractedProposalIds) {
                // Ensure any proposal that has a contract is always marked hired —
                // guards against stale status/pipeline_stage in the database.
                if ($contractedProposalIds->get($p->id)) {
                    $p->status         = 'accepted';
                    $p->pipeline_stage = 'hired';
                }
            })
            ->groupBy('job_id');

        $contracts = BpContract::with('bp:id,display_name,bp_type,avatar_initials,avatar_path,slug')
            ->where('practitioner_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->orderByDesc('started_at')
            ->get();

        $totalSpent = BpContract::where('practitioner_id', $user->id)->sum('total_value_cents');

        $bpIds = $proposalsByJob->flatten()->pluck('bp_id')->unique()->values();
        $jobsDoneByBp = BpContract::whereIn('bp_id', $bpIds)
            ->where('status', 'completed')
            ->selectRaw('bp_id, count(*) as c')
            ->groupBy('bp_id')
            ->pluck('c', 'bp_id');
        $bpUsers = \App\Models\User::whereIn('id', $bpIds)->get(['id', 'bp_hourly_rate_cents'])->keyBy('id');
        $bpStats = $bpIds->mapWithKeys(fn ($id) => [$id => [
            'jobs_done'         => (int) ($jobsDoneByBp[$id] ?? 0),
            'asking_rate_cents' => $bpUsers[$id]->bp_hourly_rate_cents ?? null,
        ]]);

        // Direct engagement requests from public profile (hire/quote/consultation)
        $engagementRequests = BpEngagementRequest::with('bp:id,display_name,bp_type,avatar_initials,slug')
            ->where('practitioner_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'              => $r->id,
                'type'            => $r->type,
                'engagement_type' => $r->engagement_type,
                'service'         => $r->service,
                'meeting_type'    => $r->meeting_type,
                'start_date'      => $r->start_date?->format('M j, Y'),
                'budget'          => $r->budget,
                'payment_terms'   => $r->payment_terms,
                'duration'        => $r->duration,
                'notes'           => $r->notes,
                'agenda'          => $r->agenda,
                'status'          => $r->status,
                'urgent'          => $r->urgent,
                'created_at'      => $r->created_at->format('M j, Y g:i A'),
                'bp'              => $r->bp ? [
                    'id'             => $r->bp->id,
                    'display_name'   => $r->bp->display_name,
                    'bp_type'        => $r->bp->bp_type instanceof \BackedEnum ? $r->bp->bp_type->value : (string) ($r->bp->bp_type ?? ''),
                    'avatar_initials'=> $r->bp->avatar_initials,
                    'slug'           => $r->bp->slug,
                ] : null,
            ])->values()->toArray();

        return Inertia::render('provider/SupportServices', [
            'jobs'             => $jobs,
            'proposalsByJob'   => $proposalsByJob,
            'activeContracts'  => $contracts,
            'engagementRequests' => $engagementRequests,
            'milestonesByContract' => BpMilestone::whereIn('contract_id', $contracts->pluck('id'))
                ->orderBy('sort_order')
                ->get()
                ->groupBy('contract_id')
                ->map(fn ($group) => $group->values()),
            'bpStats'          => $bpStats,
            'stats' => [
                'open'              => $jobs->where('status', 'open')->count(),
                'draft'             => $jobs->where('status', 'draft')->count(),
                'paused'            => $jobs->where('status', 'paused')->count(),
                'filled'            => $jobs->where('status', 'filled')->count(),
                'closed'            => $jobs->whereIn('status', ['closed', 'cancelled'])->count(),
                'total_jobs'        => $jobs->count(),
                'total_proposals'   => $proposalsByJob->flatten()->count(),
                'pending_proposals' => $proposalsByJob->flatten()->where('status', 'pending')->count(),
                'hired'             => $contracts->where('status', 'active')->count(),
                'total_spent_cents' => (int) $totalSpent,
                'engagement_requests' => count($engagementRequests),
            ],
        ]);
    }

    public function store(CreateJobRequest $request): RedirectResponse
    {
        $job = $this->jobs->create($request->user(), $request->validated());

        return back()->with('success', $job->status === 'draft' ? 'Draft saved.' : 'Support request posted.');
    }

    public function update(UpdateJobRequest $request, BpJob $job): RedirectResponse
    {
        $this->authorize('close', $job);
        $this->jobs->update($job, $request->validated());
        return back()->with('success', 'Posting updated.');
    }

    public function setStatus(JobStatusRequest $request, BpJob $job): RedirectResponse
    {
        $this->authorize('close', $job);
        $this->jobs->setStatus($job, $request->validated()['status']);
        return back()->with('success', 'Posting status updated.');
    }

    public function destroy(Request $request, BpJob $job): RedirectResponse
    {
        $this->authorize('close', $job);
        $this->jobs->close($job);
        return back()->with('success', 'Job closed.');
    }

    public function acceptProposal(Request $request, BpJob $job, BpProposal $proposal): RedirectResponse
    {
        abort_if($proposal->job_id !== $job->id, 404);
        $this->authorize('close', $job); // owner == accept authority
        $validated = $request->validate(['final_rate_cents' => 'nullable|integer|min:0']);
        $this->proposals->accept($proposal, $validated['final_rate_cents'] ?? null);
        return back()->with('success', 'Proposal accepted. Contract created.');
    }

    public function declineProposal(Request $request, BpJob $job, BpProposal $proposal): RedirectResponse
    {
        abort_if($proposal->job_id !== $job->id, 404);
        $this->authorize('close', $job);
        $this->proposals->decline($proposal, $request->input('reason'));
        return back()->with('success', 'Proposal declined.');
    }

    public function setProposalStage(ProposalStageRequest $request, BpJob $job, BpProposal $proposal): RedirectResponse
    {
        abort_if($proposal->job_id !== $job->id, 404);
        $this->authorize('close', $job);
        $data = $request->validated();
        $this->proposals->setStage($proposal, $data['pipeline_stage'], $data['note'] ?? null, $data['interview_type'] ?? null, $data['interview_at'] ?? null);

        if ($data['pipeline_stage'] === 'interview' && !empty($data['notify_applicant'])) {
            $user = $request->user();
            $when = $data['interview_at'] ?? null;
            $whenLabel = $when ? \Illuminate\Support\Carbon::parse($when)->format('M j, Y \a\t g:ia') : 'a time to be confirmed';
            $typeLabel = ['video' => 'Video Call', 'phone' => 'Phone Call', 'in_person' => 'In-Person'][$data['interview_type'] ?? ''] ?? 'Interview';
            $body = "We'd like to schedule a {$typeLabel} for \"{$job->title}\" on {$whenLabel}.";
            if (!empty($data['note'])) {
                $body .= "\n\n" . $data['note'];
            }
            $thread = $this->messaging->createThread([$user->id, $proposal->bp_id], 'Interview: ' . $job->title);
            $this->messaging->sendMessage($thread, $user, $body);
        }

        return back()->with('success', 'Applicant status updated.');
    }

    public function setProposalNotes(ProposalNoteRequest $request, BpJob $job, BpProposal $proposal): RedirectResponse
    {
        abort_if($proposal->job_id !== $job->id, 404);
        $this->authorize('close', $job);
        $this->proposals->setNotes($proposal, $request->validated()['internal_notes'] ?? null);
        return back()->with('success', 'Notes saved.');
    }

    public function cancelContract(Request $request, BpContract $contract): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        $this->contracts->cancel($contract, $request->user(), $request->input('reason'));
        return back()->with('success', 'Contract ended.');
    }

    public function endContract(Request $request, BpContract $contract): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        // Block if ANY milestones exist and are not all paid — regardless of payment_type.
        // Once milestones are added, the contract is milestone-driven.
        if ($contract->milestones()->exists()
            && $contract->milestones()->whereNotIn('status', ['paid'])->exists()) {
            return back()->withErrors(['contract' => 'All milestones must be paid before ending this contract.']);
        }
        $contract->update(['status' => 'completed', 'ended_at' => now(), 'completed_at' => now()]);
        return back()->with('success', 'Contract ended.');
    }

    public function releasePayment(Request $request, BpContract $contract): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        // Block if milestones exist — contract is milestone-driven, use payMilestone instead.
        if ($contract->milestones()->exists()) {
            return back()->withErrors(['contract' => 'This contract has milestones. Pay each milestone individually.']);
        }
        $paymentType = $contract->payment_type ?? 'one_time';
        if ($paymentType !== 'one_time') {
            abort(422, 'Only one-time payment contracts can use this action.');
        }
        $statusVal = $contract->status instanceof \BackedEnum ? $contract->status->value : (string) $contract->status;
        if ($statusVal !== 'active') {
            return back()->withErrors(['contract' => 'Contract is not active.']);
        }
        try {
            $payout = $this->payouts->endContractAndRelease($contract, $request->user());
            $amount = number_format($payout->amount_cents / 100, 2);
            return back()->with('success', "Payment of \${$amount} released to BP via Stripe. Contract ended.");
        } catch (\Throwable $e) {
            return back()->withErrors(['contract' => $e->getMessage()]);
        }
    }

    public function storeMilestone(Request $request, BpContract $contract): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:191'],
            'description'  => ['nullable', 'string'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'due_at'       => ['nullable', 'date'],
            'sort_order'   => ['nullable', 'integer'],
        ]);
        BpMilestone::create([
            'id'           => 'bm_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)),
            'contract_id'  => $contract->id,
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'amount_cents' => $data['amount_cents'],
            'due_at'       => $data['due_at'] ?? null,
            'sort_order'   => $data['sort_order'] ?? 0,
            'status'       => 'pending',
        ]);
        return back()->with('success', 'Milestone added.');
    }

    public function updateMilestone(Request $request, BpContract $contract, BpMilestone $milestone): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        abort_if($milestone->contract_id !== $contract->id, 404);
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:191'],
            'description'  => ['nullable', 'string'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'due_at'       => ['nullable', 'date'],
        ]);
        $milestone->update($data);
        return back()->with('success', 'Milestone updated.');
    }

    public function destroyMilestone(Request $request, BpContract $contract, BpMilestone $milestone): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        abort_if($milestone->contract_id !== $contract->id, 404);
        $statusVal = $milestone->status instanceof \BackedEnum ? $milestone->status->value : (string) $milestone->status;
        if ($statusVal === 'paid') {
            return back()->withErrors(['milestone' => 'Cannot delete a paid milestone.']);
        }
        $milestone->delete();
        return back()->with('success', 'Milestone removed.');
    }

    public function approveMilestone(Request $request, BpContract $contract, BpMilestone $milestone): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        abort_if($milestone->contract_id !== $contract->id, 404);
        $this->contracts->approveMilestone($milestone, $request->user());
        return back()->with('success', 'Milestone approved. You can now release payment.');
    }

    public function payMilestone(Request $request, BpContract $contract, BpMilestone $milestone): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        abort_if($milestone->contract_id !== $contract->id, 404);
        try {
            $payout = $this->payouts->payMilestone($milestone, $request->user());
            $amount = number_format($payout->amount_cents / 100, 2);
            return back()->with('success', "Milestone payment of \${$amount} released via Stripe.");
        } catch (\Throwable $e) {
            return back()->withErrors(['milestone' => $e->getMessage()]);
        }
    }
}
