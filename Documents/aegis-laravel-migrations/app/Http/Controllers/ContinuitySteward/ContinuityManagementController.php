<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incident\VerifyIncidentRequest;
use App\Models\CriticalIncident;
use App\Models\PlanSteward;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContinuityManagementController extends Controller
{
    public function __construct(private IncidentService $incidents) {}

    public function index(Request $request): Response
    {
        $planIds = PlanSteward::where('steward_id', $request->user()->id)
            ->where('steward_type', 'continuity_steward')
            ->where('status', 'active')
            ->pluck('plan_id');

        return Inertia::render('ContinuitySteward/ContinuityManagement', [
            'activeIncidents' => CriticalIncident::whereIn('plan_id', $planIds)
                ->whereIn('status', ['monitoring', 'active'])->get(),
            'closedIncidents' => CriticalIncident::whereIn('plan_id', $planIds)
                ->where('status', 'closed')->orderByDesc('closed_at')->limit(20)->get(),
        ]);
    }

    public function verify(VerifyIncidentRequest $request, CriticalIncident $incident): RedirectResponse
    {
        $this->authorize('verify', $incident);
        $this->incidents->verify($incident, $request->user(), $request->validated());
        return back()->with('success', 'Incident verified.');
    }

    public function activate(Request $request, CriticalIncident $incident): RedirectResponse
    {
        $this->authorize('activate', $incident);
        $this->incidents->activate($incident);
        return back()->with('success', 'Incident activated.');
    }

    public function close(Request $request, CriticalIncident $incident): RedirectResponse
    {
        $this->authorize('close', $incident);
        $summary = $request->validate(['summary' => 'required|string|min:10|max:2000'])['summary'];
        $this->incidents->close($incident, $request->user(), $summary);
        return back()->with('success', 'Incident closed.');
    }

    public function update(Request $request, CriticalIncident $incident): RedirectResponse
    {
        $this->authorize('update', $incident);
        $body = $request->validate(['body' => 'required|string|min:5|max:2000'])['body'];
        $this->incidents->addUpdate($incident, $request->user(), $body);
        return back()->with('success', 'Update posted.');
    }
}
