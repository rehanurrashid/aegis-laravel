<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $filters = $request->only(['status', 'severity', 'from', 'to']);
        return Inertia::render('Admin/Incidents', [
            'incidents' => CriticalIncident::query()
                ->when($filters['status']   ?? null, fn($q,$v) => $q->where('status', $v))
                ->when($filters['severity'] ?? null, fn($q,$v) => $q->where('severity', $v))
                ->when($filters['from']     ?? null, fn($q,$v) => $q->where('created_at', '>=', $v))
                ->when($filters['to']       ?? null, fn($q,$v) => $q->where('created_at', '<=', $v))
                ->orderByDesc('created_at')
                ->limit(200)
                ->get(),
            'filters'   => $filters,
        ]);
    }

    public function show(CriticalIncident $incident): Response
    {
        return Inertia::render('Admin/IncidentDetail', [
            'incident' => $incident->load('plan', 'updates', 'tasks'),
        ]);
    }

    public function escalate(Request $request, CriticalIncident $incident): RedirectResponse
    {
        $note = $request->validate(['note' => 'nullable|string|max:1000'])['note'] ?? null;
        $this->incidents->escalate($request->user(), $incident, $note);
        return back()->with('success', 'Incident escalated.');
    }

    public function close(Request $request, CriticalIncident $incident): RedirectResponse
    {
        $note = $request->validate(['note' => 'nullable|string|max:1000'])['note'] ?? null;
        $this->incidents->close($request->user(), $incident, $note);
        return back()->with('success', 'Incident closed.');
    }
}
