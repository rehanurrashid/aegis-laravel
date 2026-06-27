<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\AddPlanTaskRequest;
use App\Http\Requests\Plan\AnnualReviewRequest;
use App\Http\Requests\Plan\AttestVaultRequest;
use App\Http\Requests\Plan\SignPlanRequest;
use App\Models\ContinuityDocument;
use App\Models\PlanIncidentConfig;
use App\Models\PlanSteward;
use App\Models\PlanTask;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContinuityPlanController extends Controller
{
    public function __construct(private PlanService $plans) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);

        return Inertia::render('Provider/ContinuityPlan', [
            'plan'            => $plan,
            'tasks'           => $plan ? PlanTask::where('plan_id', $plan->id)->orderBy('sort_order')->get() : [],
            'incidentConfigs' => $plan ? PlanIncidentConfig::where('plan_id', $plan->id)->get() : [],
            'stewards'        => $plan ? PlanSteward::where('plan_id', $plan->id)->whereIn('status', ['active', 'pending'])->get() : [],
            'documents'       => $plan ? ContinuityDocument::where('plan_id', $plan->id)->get() : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->plans->createDraft($request->user());
        return back()->with('success', 'Draft plan created.');
    }

    public function sign(SignPlanRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('sign', $plan);

        $this->plans->sign($plan, [
            'name'  => $request->user()->display_name,
            'title' => $request->input('title'),
        ], $request->ip());
        return back()->with('success', 'Plan signed and activated.');
    }

    public function attest(AttestVaultRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('attestVault', $plan);

        $this->plans->attestVault($plan, $request->input('note'));
        return back()->with('success', 'Vault attested.');
    }

    public function reviewStart(Request $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('annualReview', $plan);

        $this->plans->beginAnnualReview($plan);
        return back()->with('success', 'Annual review started.');
    }

    public function reviewComplete(AnnualReviewRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('annualReview', $plan);

        $this->plans->completeAnnualReview($plan, $request->validated()['checklist'], $request->input('notes'));
        return back()->with('success', 'Annual review completed.');
    }

    public function addTask(AddPlanTaskRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('update', $plan);

        $this->plans->addTask($plan, $request->validated());
        return back()->with('success', 'Task added.');
    }

    public function removeTask(Request $request, PlanTask $task): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $task->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $this->plans->removeTask($task);
        return back()->with('success', 'Task removed.');
    }

    public function configureIncident(Request $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('update', $plan);

        $data = $request->validate([
            'incident_type'       => 'required|string',
            'enabled'             => 'required|boolean',
            'is_optin'            => 'nullable|boolean',
            'docs_required'       => 'nullable|string',
            'authorized_ss_ids'   => 'nullable|array',
            'authorized_cs_ids'   => 'nullable|array',
        ]);
        $this->plans->configureIncidentType($plan, $data['incident_type'], $data);
        return back()->with('success', 'Incident configuration saved.');
    }
}
