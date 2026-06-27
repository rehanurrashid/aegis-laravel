<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Http\Controllers\Controller;
use App\Models\ContinuityPlan;
use App\Models\CriticalIncident;
use App\Models\IncidentTask;
use App\Models\PlanSteward;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private ActivityService $activity) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $stewardRows = PlanSteward::where('steward_id', $user->id)
            ->where('steward_type', 'continuity_steward')
            ->where('status', 'active')
            ->get();

        $planIds = $stewardRows->pluck('plan_id');
        $practitioners = ContinuityPlan::whereIn('id', $planIds)
            ->with('practitioner')
            ->get();

        $activeIncidents = CriticalIncident::whereIn('plan_id', $planIds)
            ->where('status', 'active')->get();

        $pendingTasks = IncidentTask::where('assigned_to_user_id', $user->id)
            ->where('status', 'pending')->get();

        return Inertia::render('ContinuitySteward/Dashboard', [
            'user'           => $user,
            'practitioners'  => $practitioners,
            'activeTasks'    => $pendingTasks,
            'activeIncidents'=> $activeIncidents,
            'recentActivity' => $this->activity->getForUser($user->id, [], 10),
            'stats'          => [
                'serving'           => $stewardRows->count(),
                'active_incidents'  => $activeIncidents->count(),
                'pending_tasks'     => $pendingTasks->count(),
            ],
        ]);
    }
}
