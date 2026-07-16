<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Steward\DesignateStewardRequest;
use App\Models\ContinuityPlan;
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
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        $tier = is_object($user->tier) ? $user->tier->value : ($user->tier ?? 'access');

        // Providers this user is serving as SS for (cross-role)
        $servingAsSsFor = PlanSteward::where('steward_id', $user->id)
            ->where('steward_category', 'support_steward')
            ->whereIn('status', ['active', 'pending'])
            ->with(['plan.practitioner:id,display_name,credentials,organization,location,avatar_initials,slug'])
            ->get()
            ->map(fn ($s) => [
                'id'            => $s->id,
                'role'          => is_object($s->role) ? $s->role->value : $s->role,
                'status'        => is_object($s->status) ? $s->status->value : $s->status,
                'review_due_at' => $s->review_due_at?->toDateString()
                    ?? $s->plan?->annual_review_date?->toDateString(),
                'provider' => [
                    'display_name'    => $s->plan?->practitioner?->display_name ?? '—',
                    'credentials'     => $s->plan?->practitioner?->credentials ?? '',
                    'organization'    => $s->plan?->practitioner?->organization ?? '',
                    'location'        => $s->plan?->practitioner?->location ?? '',
                    'avatar_initials' => $s->plan?->practitioner?->avatar_initials ?? '??',
                    'slug'            => $s->plan?->practitioner?->slug ?? null,
                ],
                'plan_status' => $s->plan?->status?->value ?? 'none',
            ]);

        $allStewards = $plan
            ? PlanSteward::where('plan_id', $plan->id)
                ->where('steward_category', 'support_steward')
                ->with('steward:id,display_name,credentials,email,phone,title,organization,location,avatar_initials,slug')
                ->orderBy('created_at')
                ->get()
                ->map(fn ($s) => array_merge($s->toArray(), [
                    'steward' => $s->steward ? $s->steward->toArray() : null,
                ]))
            : collect();

        $stewards  = collect($allStewards)->whereIn('status', ['active'])->values();
        $suspended = collect($allStewards)->where('status', 'archived')
            ->filter(fn ($s) => !empty($s['declined_reason']))->values();
        $pending   = collect($allStewards)->whereIn('status', ['invited', 'pending'])->values();
        $declined  = collect($allStewards)->where('status', 'declined')->values();
        $archived  = collect($allStewards)->where('status', 'archived')
            ->filter(fn ($s) => empty($s['declined_reason']))->values();

        $ssMax   = (int) (config('aegis.tier_limits.' . $tier . '.max_support_stewards', 2));
        $ssCount = $stewards->count() + $pending->count();

        $notifyPrefs = [];
        try {
            $metaRow = \App\Models\UserMeta::where('user_id', $user->id)
                ->where('meta_key', 'notify_ss_activity')->first();
            if ($metaRow) {
                $notifyPrefs = json_decode($metaRow->meta_value, true) ?? [];
            }
        } catch (\Throwable $e) {}

        return Inertia::render('Provider/SupportStewards', [
            'stewards'           => $stewards,
            'suspended'          => $suspended,
            'pending'            => $pending,
            'invited'            => collect($allStewards)->where('status', 'invited')->values(),
            'declined'           => $declined,
            'archived'           => $archived,
            'servingAsSsFor'     => $servingAsSsFor,
            'tier'               => $tier,
            'ssMax'              => $ssMax,
            'ssCount'            => $ssCount,
            'notifyPrefs'        => $notifyPrefs,
            'planStatus'         => $plan?->status?->value ?? null,
            'annualReviewDate'   => $plan?->annual_review_date?->toISOString() ?? null,
            'hasDraftInProgress' => ContinuityPlan::where('practitioner_id', $user->id)->where('status', 'draft')->exists(),
            'draftPlanVersion'   => ContinuityPlan::where('practitioner_id', $user->id)->where('status', 'draft')->value('plan_version'),
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
