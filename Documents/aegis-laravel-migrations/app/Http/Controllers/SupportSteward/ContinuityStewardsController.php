<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportSteward;

use App\Http\Controllers\Controller;
use App\Models\PlanSteward;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Support Steward\'s read-only view of Continuity Stewards they support.
 * Per UC-SS-020..025.
 */
class ContinuityStewardsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('support-steward/ContinuityStewards', [
            'continuity_stewards' => PlanSteward::query()
                ->where('steward_type', 'cs')
                ->whereHas('plan.stewards', fn($q) => $q->where('user_id', $user?->id)->where('steward_type', 'ss'))
                ->with('user', 'plan')
                ->get(),
        ]);
    }
}
