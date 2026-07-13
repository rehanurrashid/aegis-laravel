<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Enums\ActivitySeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Steward\DesignateStewardRequest;
use App\Models\PlanSteward;
use App\Models\User;
use App\Services\ActivityService;
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
        private ActivityService $activity,
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
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        $tier = $user->tier ?? 'access';

        $tierLimits = config('aegis.tier_limits.' . $tier, config('aegis.tier_limits.access'));
        $ssMax = (int) ($tierLimits['max_support_stewards'] ?? 2);

        // Load all SS records for this plan with steward user data
        $allStewards = $plan
            ? PlanSteward::where('plan_id', $plan->id)
                ->where('steward_category', 'support_steward')
                ->with('steward:id,display_name,credentials,email,phone,title,organization,location,avatar_initials,slug')
                ->orderBy('created_at')
                ->get()
                ->map(fn ($s) => array_merge($s->toArray(), [
                    'steward' => $s->steward ? $s->steward->toArray() : null,
                ]))
            : [];

        // Partition by status
        $active    = collect($allStewards)->whereIn('status', ['active'])->values();
        $suspended = collect($allStewards)->where('status', 'archived')->where('signed_at', '!=', null)->values(); // suspended uses archived + had signed_at
        $pending   = collect($allStewards)->where('status', 'pending')->values();
        $invited   = collect($allStewards)->where('status', 'invited')->values();
        $declined  = collect($allStewards)->where('status', 'declined')->values();
        $archived  = collect($allStewards)->where('status', 'archived')->whereNull('signed_at')->values();

        $ssCount = $active->count() + $pending->count() + $invited->count();

        // Providers this user is serving as SS for (cross-role lookup)
        $servingAs = PlanSteward::where('steward_id', $user->id)
            ->where('steward_category', 'support_steward')
            ->whereIn('status', ['active', 'pending'])
            ->with('plan.practitioner:id,display_name,credentials,organization,location,avatar_initials,slug')
            ->get()
            ->map(fn ($s) => [
                'id'                 => $s->id,
                'role'               => is_object($s->role) ? $s->role->value : $s->role,
                'status'             => is_object($s->status) ? $s->status->value : $s->status,
                'review_due_at'      => $s->review_due_at?->toDateString(),
                'display_name'       => $s->plan?->practitioner?->display_name ?? '—',
                'credentials'        => $s->plan?->practitioner?->credentials ?? '',
                'organization'       => $s->plan?->practitioner?->organization ?? '',
                'location'           => $s->plan?->practitioner?->location ?? '',
                'avatar_initials'    => $s->plan?->practitioner?->avatar_initials ?? '??',
                'slug'               => $s->plan?->practitioner?->slug ?? '',
            ]);

        return Inertia::render('Provider/SupportStewards', [
            'stewards'        => $active,
            'suspended'       => $suspended,
            'pending'         => $pending,
            'invited'         => $invited,
            'declined'        => $declined,
            'archived'        => $archived,
            'servingAsSSFor'  => $servingAs,
            'tier'            => $tier,
            'ssMax'           => $ssMax,
            'ssCount'         => $ssCount,
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

    public function ssSuspend(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $data = $request->validate([
            'reason'  => 'required|string|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        $steward->update(['status' => 'archived', 'declined_reason' => $data['reason']]);

        $actor = $request->user();
        // Log for actor
        $this->activity->log(
            $actor->id, 'provider', 'steward',
            ActivitySeverity::Warning, 'ss_suspended',
            'Support Steward access suspended',
            'Reason: ' . $data['reason'],
            'plan_steward', $steward->id,
            $steward->steward_id,
            'log', $actor->id
        );
        // Notify the SS
        $this->activity->log(
            $steward->steward_id, 'support_steward', 'steward',
            ActivitySeverity::Warning, 'ss_suspended',
            'Your Support Steward access has been suspended',
            'Reason: ' . $data['reason'],
            'plan_steward', $steward->id,
            $actor->id,
            'notification', $actor->id
        );

        return back()->with('success', 'Support Steward access suspended.');
    }

    public function ssReinstate(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $steward->update(['status' => 'active', 'declined_reason' => null]);

        $actor = $request->user();
        $this->activity->log(
            $actor->id, 'provider', 'steward',
            ActivitySeverity::Info, 'ss_reinstated',
            'Support Steward access reinstated',
            'Access restored for support steward.',
            'plan_steward', $steward->id,
            $steward->steward_id,
            'log', $actor->id
        );
        $this->activity->log(
            $steward->steward_id, 'support_steward', 'steward',
            ActivitySeverity::Info, 'ss_reinstated',
            'Your Support Steward access has been reinstated',
            'Your access has been restored by the practitioner.',
            'plan_steward', $steward->id,
            $actor->id,
            'notification', $actor->id
        );

        return back()->with('success', 'Support Steward reinstated.');
    }

    public function ssResend(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $steward->update(['invited_at' => now(), 'expires_at' => now()->addDays(30)]);
        return back()->with('success', 'Invitation resent.');
    }

    public function ssUpdateRole(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $data = $request->validate(['role' => 'required|in:primary,alternate,support', 'reason' => 'nullable|string|max:500']);
        $steward->update(['role' => $data['role']]);

        $actor = $request->user();
        $roleLabel = $data['role'];
        $this->activity->log(
            $actor->id, 'provider', 'steward',
            ActivitySeverity::Info, 'ss_role_changed',
            'Support Steward role updated',
            "Role changed to {$roleLabel}." . ($data['reason'] ?? ''),
            'plan_steward', $steward->id,
            $steward->steward_id,
            'log', $actor->id
        );
        $this->activity->log(
            $steward->steward_id, 'support_steward', 'steward',
            ActivitySeverity::Info, 'ss_role_changed',
            'Your Support Steward role has been updated',
            "Your role has been changed to {$roleLabel}.",
            'plan_steward', $steward->id,
            $actor->id,
            'notification', $actor->id
        );

        return back()->with('success', 'Role updated.');
    }

    public function ssUpdatePermissions(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $data = $request->validate(['responsibilities' => 'nullable|array']);
        $steward->update(['responsibilities' => $data['responsibilities'] ?? []]);

        $actor = $request->user();
        $this->activity->log(
            $actor->id, 'provider', 'steward',
            ActivitySeverity::Info, 'ss_permissions_updated',
            'Support Steward permissions updated',
            'Authorized responsibilities updated.',
            'plan_steward', $steward->id,
            $steward->steward_id,
            'log', $actor->id
        );
        $this->activity->log(
            $steward->steward_id, 'support_steward', 'steward',
            ActivitySeverity::Info, 'ss_permissions_updated',
            'Your Support Steward permissions have been updated',
            'The practitioner has updated your authorized responsibilities.',
            'plan_steward', $steward->id,
            $actor->id,
            'notification', $actor->id
        );

        return back()->with('success', 'Permissions updated.');
    }

    public function ssArchive(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $steward->update(['status' => 'archived']);
        return back()->with('success', 'Record archived.');
    }
}
