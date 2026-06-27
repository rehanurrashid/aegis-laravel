<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\CreateJobRequest;
use App\Models\BpContract;
use App\Models\BpJob;
use App\Models\BpProposal;
use App\Services\BpJobService;
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
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $jobs = BpJob::where('practitioner_id', $user->id)->orderByDesc('posted_at')->get();
        $proposalsByJob = BpProposal::whereIn('job_id', $jobs->pluck('id'))->get()->groupBy('job_id');
        $contracts = BpContract::where('practitioner_id', $user->id)->whereIn('status', ['active', 'paused'])->get();
        $totalSpent = BpContract::where('practitioner_id', $user->id)->sum('total_cents');

        return Inertia::render('Provider/JobPostings', [
            'jobs'             => $jobs,
            'proposalsByJob'   => $proposalsByJob,
            'activeContracts'  => $contracts,
            'stats' => [
                'open'             => $jobs->where('status', 'open')->count(),
                'filled'           => $jobs->where('status', 'filled')->count(),
                'total_spent_cents'=> (int) $totalSpent,
            ],
        ]);
    }

    public function store(CreateJobRequest $request): RedirectResponse
    {
        $this->jobs->create($request->user(), $request->validated());
        return back()->with('success', 'Job posted.');
    }

    public function destroy(Request $request, BpJob $job): RedirectResponse
    {
        $this->authorize('close', $job);
        $this->jobs->close($job);
        return back()->with('success', 'Job closed.');
    }

    public function acceptProposal(BpJob $job, BpProposal $proposal): RedirectResponse
    {
        abort_if($proposal->job_id !== $job->id, 404);
        $this->authorize('close', $job); // owner == accept authority
        $this->proposals->accept($proposal);
        return back()->with('success', 'Proposal accepted. Contract created.');
    }

    public function declineProposal(Request $request, BpJob $job, BpProposal $proposal): RedirectResponse
    {
        abort_if($proposal->job_id !== $job->id, 404);
        $this->authorize('close', $job);
        $this->proposals->decline($proposal, $request->input('reason'));
        return back()->with('success', 'Proposal declined.');
    }
}
