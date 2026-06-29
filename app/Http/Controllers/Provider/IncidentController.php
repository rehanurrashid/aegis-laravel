<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incident\ReportIncidentRequest;
use App\Models\ContinuityPlan;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

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
        $data = $request->validated();

        $plan = ContinuityPlan::where('id', $data['plan_id'])
            ->where('practitioner_id', $request->user()->id)
            ->first();

        if (!$plan) {
            throw new RuntimeException('No active continuity plan found for this practitioner.');
        }

        // FormRequest field is `report_narrative`; service expects `narrative` in $data
        $data['narrative'] = $data['report_narrative'] ?? null;

        $this->incidents->report($plan, $request->user(), $data);

        return back()->with('success', 'Critical incident reported. Your stewards have been alerted.');
    }
}
