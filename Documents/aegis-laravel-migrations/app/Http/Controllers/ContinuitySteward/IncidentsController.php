<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incident\IncidentUpdateRequest;
use App\Models\CriticalIncident;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncidentsController extends Controller
{
    public function __construct(private IncidentService $incidents) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('continuity-steward/Incidents', [
            'incidents' => CriticalIncident::query()
                ->whereHas('plan.stewards', fn($q) => $q->where('user_id', $user?->id))
                ->orderByDesc('created_at')
                ->limit(100)
                ->get(),
        ]);
    }

    public function show(CriticalIncident $incident): Response
    {
        return Inertia::render('continuity-steward/IncidentDetail', [
            'incident' => $incident->load('plan', 'updates', 'tasks'),
        ]);
    }

    public function postUpdate(IncidentUpdateRequest $request, CriticalIncident $incident): RedirectResponse
    {
        $this->incidents->addUpdate($request->user(), $incident, $request->validated());
        return back()->with('success', 'Update posted.');
    }
}
