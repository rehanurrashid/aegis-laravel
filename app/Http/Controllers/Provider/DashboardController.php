<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\CriticalIncident;
use App\Models\PlanSteward;
use App\Models\CeuEntry;
use App\Services\ActivityService;
use App\Services\CeuService;
use App\Services\IncidentService;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private PlanService $plans,
        private IncidentService $incidents,
        private CeuService $ceus,
        private ActivityService $activity,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);

        $stewards = $plan
            ? PlanSteward::where('plan_id', $plan->id)
                ->whereIn('status', ['active', 'pending'])
                ->get()
            : collect();

        $activeIncident = CriticalIncident::where('practitioner_id', $user->id)
            ->where('status', 'active')
            ->first();

        $progress = $this->ceus->getProgress($user->id);

        return Inertia::render('Provider/Dashboard', [
            'user'        => $user,
            'planStatus'  => $plan?->status ?? 'none',
            'plan'        => $plan,
            'stats'       => [
                'active_plans'      => $plan && in_array($plan->status, ['active', 'annual_review_due']) ? 1 : 0,
                'ceus_total'        => $progress['total'],
                'ceus_count'        => $progress['count'],
                'active_incidents'  => $activeIncident ? 1 : 0,
            ],
            'activeIncident'      => $activeIncident,
            'continuityStewards'  => $stewards->where('steward_type', 'continuity_steward')->values(),
            'supportStewards'     => $stewards->where('steward_type', 'support_steward')->values(),
            'recentActivity'      => $this->activity->getForUser($user->id, [], 10),
            'upcomingCEUs'        => CeuEntry::where('practitioner_id', $user->id)
                                        ->orderByDesc('completed_on')->limit(5)->get(),
        ]);
    }
}
