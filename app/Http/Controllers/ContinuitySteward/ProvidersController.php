<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Http\Controllers\Controller;
use App\Models\ContinuityDocument;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProvidersController extends Controller
{
    public function __construct(private DocumentService $documents) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $planIds = PlanSteward::where('steward_id', $user->id)
            ->where('steward_type', 'continuity_steward')
            ->where('status', 'active')
            ->pluck('plan_id');

        $plans = ContinuityPlan::whereIn('id', $planIds)->get();

        return Inertia::render('ContinuitySteward/Providers', [
            'providers' => $plans->map(fn ($p) => [
                'plan'              => $p,
                'practitioner'      => User::find($p->practitioner_id),
                'vault_attested_at' => $p->vault_attested_at,
                'status'            => $p->status,
            ])->values(),
        ]);
    }

    public function plan(Request $request, User $provider): Response
    {
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)
            ->orderByDesc('created_at')->firstOrFail();
        $this->ensureAssigned($request->user()->id, $plan->id);

        return Inertia::render('ContinuitySteward/Providers', [
            'detail' => [
                'provider'  => $provider,
                'plan'      => $plan,
                'documents' => $this->documents->getForPlan($plan->id),
            ],
        ]);
    }

    public function countersign(Request $request, User $provider, ContinuityDocument $document = null): RedirectResponse
    {
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)->firstOrFail();
        $this->ensureAssigned($request->user()->id, $plan->id);

        $document = $document ?? ContinuityDocument::where('plan_id', $plan->id)
            ->where('status', 'countersign_pending')->latest()->firstOrFail();
        $this->authorize('countersign', $document);

        $data = $request->validate(['name' => 'required|string|max:100']);
        $this->documents->countersign($document, $request->user(), ['name' => $data['name'], 'ip' => $request->ip()]);
        return back()->with('success', 'Document countersigned.');
    }

    private function ensureAssigned(string $userId, string $planId): void
    {
        $exists = PlanSteward::where('plan_id', $planId)
            ->where('steward_id', $userId)
            ->where('status', 'active')
            ->exists();
        abort_unless($exists, 403);
    }
}
