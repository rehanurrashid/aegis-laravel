<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\CreateJobRequest;
use App\Http\Requests\Business\JobStatusRequest;
use App\Http\Requests\Business\ProposalNoteRequest;
use App\Http\Requests\Business\ProposalStageRequest;
use App\Http\Requests\Business\UpdateJobRequest;
use App\Models\BpContract;
use App\Models\BpJob;
use App\Models\BpProposal;
use App\Services\BpJobService;
use App\Services\ContractService;
use App\Services\MessagingService;
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
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $jobs = BpJob::where('practitioner_id', $user->id)->orderByDesc('posted_at')->get();

        $proposalsByJob = BpProposal::with('bp:id,display_name,bp_type,avatar_initials,location,verified')
            ->whereIn('job_id', $jobs->pluck('id'))
            ->orderByDesc('submitted_at')
            ->get()
            ->groupBy('job_id');

        $contracts = BpContract::with('bp:id,display_name,bp_type,avatar_initials')
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

        return Inertia::render('Provider/SupportServices', [
            'jobs'             => $jobs,
            'proposalsByJob'   => $proposalsByJob,
            'activeContracts'  => $contracts,
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
            ],
        ]);
    }

    public function store(CreateJobRequest $request): RedirectResponse
    {
        $this->jobs->create($request->user(), $request->validated());
        return back()->with('success', 'Support request posted.');
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
}
