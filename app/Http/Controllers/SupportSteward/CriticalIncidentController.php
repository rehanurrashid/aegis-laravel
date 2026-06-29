<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportSteward;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incident\ReportIncidentRequest;
use App\Models\ContinuityPlan;
use App\Models\CriticalIncident;
use App\Models\PlanSteward;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CriticalIncidentController extends Controller
{
    public function __construct(private IncidentService $incidents) {}

    public function index(Request $request): Response
    {
        $planIds = PlanSteward::where('steward_id', $request->user()->id)
            ->where('steward_type', 'support_steward')
            ->where('status', 'active')->pluck('plan_id');

        return Inertia::render('SupportSteward/CriticalIncidentLog', [
            'activeIncidents' => CriticalIncident::whereIn('plan_id', $planIds)
                ->whereIn('status', ['reported', 'verified', 'active'])->get(),
            'closedIncidents' => CriticalIncident::whereIn('plan_id', $planIds)
                ->where('status', 'closed')->orderByDesc('closed_at')->limit(20)->get(),
        ]);
    }

    public function store(ReportIncidentRequest $request): RedirectResponse
    {
        $plan = ContinuityPlan::findOrFail($request->validated()['plan_id']);
        $this->ensureAssigned($request->user()->id, $plan->id);
        $this->incidents->report($plan, $request->user(), [
            'incident_type' => $request->validated()['incident_type'],
            'narrative'     => $request->validated()['report_narrative'],
        ]);
        return back()->with('success', 'Incident reported.');
    }

    public function update(Request $request, CriticalIncident $incident): RedirectResponse
    {
        $body = $request->validate(['body' => 'required|string|min:5|max:2000'])['body'];
        $this->incidents->addUpdate($incident, $request->user(), $body);
        return back()->with('success', 'Update posted.');
    }

    private function ensureAssigned(string $userId, string $planId): void
    {
        $exists = PlanSteward::where('plan_id', $planId)
            ->where('steward_id', $userId)
            ->where('status', 'active')->exists();
        abort_unless($exists, 403);
    }
}
