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

class ContinuityStewardController extends Controller
{
    public function __construct(
        private StewardService $stewards,
        private PlanService $plans,
    ) {}

    // ── CS ─────────────────────────────────────────────────────────────────────
    public function csIndex(Request $request): Response
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        $tierLimits = $request->user()->tier === 'practice' ? ['max_cs' => 2] : ['max_cs' => 1, 'max_ss' => 2];

        return Inertia::render('Provider/ContinuityStewards', [
            'stewards' => $plan
                ? PlanSteward::where('plan_id', $plan->id)
                    ->where('steward_type', 'continuity_steward')
                    ->whereIn('status', ['active', 'pending'])->get()
                : [],
            'pendingInvitations' => $plan
                ? PlanSteward::where('plan_id', $plan->id)
                    ->where('steward_type', 'continuity_steward')
                    ->where('status', 'pending')->get()
                : [],
            'tierLimits' => $tierLimits,
        ]);
    }

    public function csInvite(DesignateStewardRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('update', $plan);

        $data = $request->validated();
        if (!empty($data['user_id'])) {
            $this->stewards->designate($plan, User::findOrFail($data['user_id']), 'continuity_steward', $data['role'] ?? 'primary');
        } else {
            $this->stewards->inviteExternal($plan, $data['email'], $data['display_name'] ?? 'Continuity Steward', 'continuity_steward', $data['role'] ?? 'primary');
        }
        return back()->with('success', 'Continuity Steward invited.');
    }

    public function csRemove(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $this->stewards->remove($steward, $request->user(), $request->input('reason'));
        return back()->with('success', 'Steward removed.');
    }

    public function csAuthorize(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $data = $request->validate([
            'incident_type'     => 'required|string',
            'authorized_cs_ids' => 'nullable|array',
            'authorized_ss_ids' => 'nullable|array',
        ]);
        $this->stewards->setAuthorization($plan, $data['incident_type'], $data['authorized_cs_ids'] ?? [], $data['authorized_ss_ids'] ?? []);
        return back()->with('success', 'Authorization updated.');
    }

    // ── SS ─────────────────────────────────────────────────────────────────────
    public function ssIndex(Request $request): Response
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

    public function ssInvite(DesignateStewardRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('update', $plan);

        $data = $request->validated();
        if (!empty($data['user_id'])) {
            $this->stewards->designate($plan, User::findOrFail($data['user_id']), 'support_steward', $data['role'] ?? 'primary');
        } else {
            $this->stewards->inviteExternal($plan, $data['email'], $data['display_name'] ?? 'Support Steward', 'support_steward', $data['role'] ?? 'primary');
        }
        return back()->with('success', 'Support Steward invited.');
    }

    public function ssRemove(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $this->stewards->remove($steward, $request->user(), $request->input('reason'));
        return back()->with('success', 'Steward removed.');
    }
}
