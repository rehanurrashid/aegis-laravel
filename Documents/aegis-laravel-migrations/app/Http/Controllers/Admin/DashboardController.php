<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ContinuityPlan;
use App\Models\CriticalIncident;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users'      => User::count(),
                'users_by_role'    => User::groupBy('role')->selectRaw('role, COUNT(*) as count')->pluck('count', 'role'),
                'signups_30d'      => User::where('created_at', '>=', now()->subDays(30))->count(),
                'active_plans'     => ContinuityPlan::where('status', 'active')->count(),
                'active_incidents' => CriticalIncident::where('status', 'active')->count(),
                'open_complaints'  => Complaint::whereIn('status', ['open', 'in_progress'])->count(),
            ],
            'activeIncidents'  => CriticalIncident::where('status', 'active')->orderByDesc('reported_at')->limit(20)->get(),
            'recentComplaints' => Complaint::orderByDesc('created_at')->limit(10)->get(),
        ]);
    }
}
