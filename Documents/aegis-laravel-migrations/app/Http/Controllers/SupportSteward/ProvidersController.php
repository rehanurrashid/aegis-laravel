<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportSteward;

use App\Http\Controllers\Controller;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProvidersController extends Controller
{
    public function index(Request $request): Response
    {
        $planIds = PlanSteward::where('steward_id', $request->user()->id)
            ->where('steward_type', 'support_steward')
            ->where('status', 'active')->pluck('plan_id');

        return Inertia::render('SupportSteward/Providers', [
            'providers' => ContinuityPlan::whereIn('id', $planIds)->get(),
        ]);
    }

    public function show(Request $request, User $provider): Response
    {
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)->firstOrFail();
        $this->ensureAssigned($request->user()->id, $plan->id);
        return Inertia::render('SupportSteward/Providers', ['detail' => ['plan' => $plan, 'provider' => $provider]]);
    }

    public function continuityStewarded(Request $request): Response
    {
        $planIds = PlanSteward::where('steward_id', $request->user()->id)
            ->where('steward_type', 'support_steward')
            ->where('status', 'active')->pluck('plan_id');

        $csContacts = PlanSteward::whereIn('plan_id', $planIds)
            ->where('steward_type', 'continuity_steward')
            ->where('status', 'active')->get();

        return Inertia::render('SupportSteward/ContinuityStewards', ['csContacts' => $csContacts]);
    }

    private function ensureAssigned(string $userId, string $planId): void
    {
        $exists = PlanSteward::where('plan_id', $planId)
            ->where('steward_id', $userId)
            ->where('status', 'active')->exists();
        abort_unless($exists, 403);
    }
}
