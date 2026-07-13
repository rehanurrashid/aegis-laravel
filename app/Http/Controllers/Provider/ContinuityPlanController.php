<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Enums\IncidentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\AddPlanTaskRequest;
use App\Http\Requests\Plan\AnnualReviewRequest;
use App\Http\Requests\Plan\AttestVaultRequest;
use App\Http\Requests\Plan\SignPlanRequest;
use App\Models\ContinuityDocument;
use App\Models\PlanIncidentConfig;
use App\Models\PlanSteward;
use App\Models\PlanTask;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\PlanService;
use App\Enums\ActivitySeverity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContinuityPlanController extends Controller
{
    public function __construct(
        private PlanService $plans,
        private ActivityService $activity,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);

        $incidentTypes = collect(IncidentType::cases())->map(fn ($t) => [
            'value'    => $t->value,
            'label'    => $t->label(),
            'is_optin' => $t->isOptIn(),
        ]);

        $stewards = $plan
            ? PlanSteward::with('steward')
                ->where('plan_id', $plan->id)
                ->whereIn('status', ['active', 'pending', 'invited'])
                ->get()
                ->map(fn ($s) => [
                    'id'           => $s->id,
                    'steward_id'   => $s->steward_id,
                    'steward_type' => $s->steward_category,
                    'role'         => $s->role?->value ?? $s->role,
                    'status'       => $s->status?->value ?? $s->status,
                    'fee_cents'    => $s->fee_cents ?? 0,
                    'payment_terms'=> $s->payment_terms,
                    'auto_charge'  => $s->auto_charge ?? false,
                    'display_name' => $s->steward?->display_name ?? '—',
                    'avatar_initials' => $this->initials($s->steward?->display_name),
                ])
            : [];

        $tasks = $plan
            ? PlanTask::where('plan_id', $plan->id)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($t) => [
                    'id'            => $t->id,
                    'assigned_to'   => $t->assigned_to?->value ?? $t->assigned_to,
                    'title'         => $t->title,
                    'timeline'      => $t->timeline,
                    'sort_order'    => $t->sort_order,
                ])
            : [];

        $incidentConfigs = $plan
            ? PlanIncidentConfig::where('plan_id', $plan->id)
                ->get()
                ->map(fn ($c) => [
                    'id'               => $c->id,
                    'incident_type'    => $c->incident_type?->value ?? $c->incident_type,
                    'is_active'        => (bool) $c->is_active,
                    'docs_required'    => $c->docs_required ?? [],
                    'authorized_ss_ids'=> $c->authorized_ss_ids ?? [],
                    'authorized_cs_ids'=> $c->authorized_cs_ids ?? [],
                ])
            : [];

        $documents = $plan
            ? ContinuityDocument::where('plan_id', $plan->id)
                ->where('status', 'fully_executed')
                ->get()
            : [];

        $planSections = $plan ? $this->plans->computeSections($plan) : [];
        $canSign      = $plan ? $this->plans->canSign($plan) : false;
        $canActivate  = $plan ? $this->plans->canActivate($plan, $user) : false;

        return Inertia::render('Provider/ContinuityPlan', [
            'plan'            => $plan ? [
                'id'                   => $plan->id,
                'status'               => $plan->status?->value ?? $plan->status,
                'plan_version'         => $plan->plan_version ?? 1,
                'signed_at'            => $plan->signed_at?->toISOString(),
                'signature_name'       => $plan->signature_name,
                'signature_title'      => $plan->signature_title,
                'vault_attested_at'    => $plan->vault_attested_at?->toISOString(),
                'annual_review_date'   => $plan->annual_review_date?->toISOString(),
                'expires_at'           => $plan->expires_at?->toISOString(),
            ] : null,
            'planSections'    => $planSections,
            'tasks'           => $tasks,
            'incidentConfigs' => $incidentConfigs,
            'stewards'        => $stewards,
            'documents'       => $documents,
            'incidentTypes'   => $incidentTypes,
            'canSign'         => $canSign,
            'canActivate'     => $canActivate,
            'tierLimits'      => config('aegis.tier_limits.' . ($user->tier ?? 'access'), []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->plans->createDraft($request->user());
        return back()->with('success', 'Draft plan created.');
    }

    public function sign(SignPlanRequest $request): RedirectResponse
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        abort_if(!$plan, 404);
        $this->authorize('sign', $plan);

        $this->plans->sign($plan, [
            'name'  => $user->display_name,
            'title' => $request->input('title'),
        ], $request->ip());

        $this->activity->log(
            $user->id, 'provider', 'plan', ActivitySeverity::Info,
            'plan_signed', 'Continuity Plan signed',
            'You signed and activated your Continuity Plan.',
            'continuity_plan', $plan->id, null,
            'log', $user->id
        );

        return back()->with('success', 'Plan signed and activated.');
    }

    public function attest(AttestVaultRequest $request): RedirectResponse
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        abort_if(!$plan, 404);
        $this->authorize('attestVault', $plan);

        $this->plans->attestVault($plan, $request->input('note'));

        $this->activity->log(
            $user->id, 'provider', 'vault', ActivitySeverity::Info,
            'vault_attested', 'Vault attested',
            'You confirmed the vault contents are accurate.',
            'continuity_plan', $plan->id, null,
            'log', $user->id
        );

        return back()->with('success', 'Vault attested.');
    }

    public function reviewStart(Request $request): RedirectResponse
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        abort_if(!$plan, 404);
        $this->authorize('annualReview', $plan);

        $this->plans->beginAnnualReview($plan);

        $this->activity->log(
            $user->id, 'provider', 'plan', ActivitySeverity::Info,
            'annual_review_started', 'Annual review started',
            'You started the annual review for your Continuity Plan.',
            'continuity_plan', $plan->id, null,
            'log', $user->id
        );

        return back()->with('success', 'Annual review started.');
    }

    public function reviewComplete(AnnualReviewRequest $request): RedirectResponse
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        abort_if(!$plan, 404);
        $this->authorize('annualReview', $plan);

        $this->plans->completeAnnualReview($plan, $request->validated()['checklist'], $request->input('notes'));

        $this->activity->log(
            $user->id, 'provider', 'plan', ActivitySeverity::Info,
            'annual_review_completed', 'Annual review completed',
            'You completed the annual review and renewed your plan.',
            'continuity_plan', $plan->id, null,
            'log', $user->id
        );

        return back()->with('success', 'Annual review completed.');
    }

    // ── NEW: Task management ─────────────────────────────────────────────────

    public function addTask(AddPlanTaskRequest $request): RedirectResponse
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        abort_if(!$plan, 404);
        $this->authorize('update', $plan);

        $task = $this->plans->addTask($plan, $request->validated());

        $this->activity->log(
            $user->id, 'provider', 'plan', ActivitySeverity::Info,
            'plan_task_added', 'Task added to plan',
            "Added task \"{$task->title}\".",
            'plan_task', $task->id, null,
            'log', $user->id
        );

        return back()->with('success', 'Task added.');
    }

    public function removeTask(Request $request, PlanTask $task): RedirectResponse
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        abort_if(!$plan || $task->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $title = $task->title;
        $this->plans->removeTask($task);

        $this->activity->log(
            $user->id, 'provider', 'plan', ActivitySeverity::Info,
            'plan_task_removed', 'Task removed from plan',
            "Removed task \"{$title}\".",
            'continuity_plan', $plan->id, null,
            'log', $user->id
        );

        return back()->with('success', 'Task removed.');
    }

    public function reorderTasks(Request $request): RedirectResponse
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        abort_if(!$plan, 404);
        $this->authorize('update', $plan);

        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'string'])['ids'];
        $this->plans->reorderTasks($plan, $ids);

        return back()->with('success', 'Tasks reordered.');
    }

    public function configureIncident(Request $request): RedirectResponse
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        abort_if(!$plan, 404);
        $this->authorize('update', $plan);

        $data = $request->validate([
            'incident_type'       => 'required|string',
            'is_active'           => 'required|boolean',
            'is_optin'            => 'nullable|boolean',
            'docs_required'       => 'nullable|array',
            'docs_required.*'     => 'string',
            'authorized_ss_ids'   => 'nullable|array',
            'authorized_cs_ids'   => 'nullable|array',
        ]);

        $this->plans->configureIncidentType($plan, $data['incident_type'], $data);

        $status = $data['is_active'] ? 'enabled' : 'disabled';
        $this->activity->log(
            $user->id, 'provider', 'plan', ActivitySeverity::Info,
            'incident_config_updated', 'Incident type configuration updated',
            "You {$status} the {$data['incident_type']} incident type.",
            'continuity_plan', $plan->id, null,
            'log', $user->id
        );

        return back()->with('success', 'Incident configuration saved.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function initials(?string $name): string
    {
        if (!$name) return '??';
        $parts = explode(' ', preg_replace('/^Dr\.\s+/i', '', $name));
        return strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
    }
}
