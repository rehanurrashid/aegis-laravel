<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Steward\DesignateStewardRequest;
use App\Models\PlanSteward;
use App\Models\User;
use App\Services\PlanService;
use App\Services\StewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Provider-side view of Support Steward designation, status, and notifications.
 * Per UC-PRV-061..068.
 *
 * Mirrors the proven ss* logic on ContinuityStewardController: scopes to the
 * practitioner's plan, enforces the ContinuityPlan 'update' policy, uses the
 * canonical 'support_steward' steward_type, and supports both internal
 * designation and external (email) invitation via StewardService.
 */
class SupportStewardController extends Controller
{
    public function __construct(
        private StewardService $stewards,
        private PlanService $plans,
    ) {}

    public function index(Request $request): Response
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);

        return Inertia::render('Provider/SupportStewards', [
            'stewards' => $plan
                ? PlanSteward::where('plan_id', $plan->id)
                    ->where('steward_type', 'support_steward')
                    ->whereIn('status', ['active', 'pending'])->get()
                : [],
            'pendingInvitations' => $plan
                ? PlanSteward::where('plan_id', $plan->id)
                    ->where('steward_type', 'support_steward')
                    ->where('status', 'pending')->get()
                : [],
        ]);
    }

    public function designate(DesignateStewardRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('update', $plan);

        $data = $request->validated();
        if (!empty($data['user_id'])) {
            $this->stewards->designate(
                $plan,
                User::findOrFail($data['user_id']),
                'support_steward',
                $data['role'] ?? 'primary'
            );
        } else {
            $this->stewards->inviteExternal(
                $plan,
                $data['email'],
                $data['display_name'] ?? 'Support Steward',
                'support_steward',
                $data['role'] ?? 'primary'
            );
        }

        return back()->with('success', 'Support Steward designated.');
    }

    public function remove(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $this->stewards->remove($steward, $request->user(), $request->input('reason'));

        return back()->with('success', 'Support Steward removed.');
    }
}
