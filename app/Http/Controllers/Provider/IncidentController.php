<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incident\ReportIncidentRequest;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Provider portal — incident activation entry point.
 * Critical incident lifecycle (verify, update, escalate, close) lives on
 * SupportSteward/CriticalIncidentController. This controller exposes only
 * the practitioner-initiated activate endpoint.
 */
class IncidentController extends Controller
{
    public function __construct(private IncidentService $incidents) {}

    public function activate(ReportIncidentRequest $request): RedirectResponse
    {
        $this->incidents->report($request->user(), $request->validated());
        return back()->with('success', 'Critical incident reported. Your stewards have been alerted.');
    }
}
