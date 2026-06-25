<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\SubmitProposalRequest;
use App\Models\BpJob;
use App\Models\BpSavedJob;
use App\Services\BpJobService;
use App\Services\ProposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobsController extends Controller
{
    public function __construct(
        private BpJobService $jobs,
        private ProposalService $proposals,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['category', 'budget_type', 'search']);
        $savedIds = BpSavedJob::where('bp_id', $request->user()->id)->pluck('job_id');

        return Inertia::render('BusinessPartner/FindJobs', [
            'jobs'      => $this->jobs->browse($filters),
            'savedJobs' => BpJob::whereIn('id', $savedIds)->get(),
            'filters'   => $filters,
        ]);
    }

    public function save(Request $request, BpJob $job): RedirectResponse
    {
        $this->jobs->save($job, $request->user());
        return back()->with('success', 'Job saved.');
    }

    public function propose(SubmitProposalRequest $request, BpJob $job): RedirectResponse
    {
        $this->authorize('apply', $job);
        $this->proposals->submit($job, $request->user(), [
            'cover_letter' => $request->validated()['cover_letter'],
            'bid_cents'    => $request->validated()['proposed_rate_cents'],
            'timeline_days'=> $request->input('timeline_days'),
        ]);
        return back()->with('success', 'Proposal submitted.');
    }
}
