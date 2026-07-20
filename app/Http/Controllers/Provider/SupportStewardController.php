<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Enums\ActivitySeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Steward\DesignateStewardRequest;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\PlanService;
use App\Services\ProfileService;
use App\Services\StewardService;
use App\Events\Steward\SsSuspended;
use App\Events\Steward\SsReinstated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Provider-side Support Steward management.
 * All ss* logic consolidated here from ContinuityStewardController.
 * Per UC-PRV-061..068.
 */
class SupportStewardController extends Controller
{
    public function __construct(
        private StewardService $stewards,
        private PlanService $plans,
        private ActivityService $activity,
        private ProfileService $profiles,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        $tier = is_object($user->tier) ? $user->tier->value : ($user->tier ?? 'access');

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
            ->filter(fn ($s) => !empty($s['declined_reason']) && !str_starts_with($s['declined_reason'] ?? '', 'terminated:'))->values()
            ->map(fn ($s) => $s); // suspended (with declined_reason, not terminated)
        $pending   = collect($allStewards)->whereIn('status', ['invited', 'pending'])->values();
        $declined  = collect($allStewards)->where('status', 'declined')->values();
        $archived  = collect([])->values(); // terminated records hidden from UI (audit trail only)

        $ssMax   = (int) (config('aegis.tier_limits.' . $tier . '.max_support_stewards', 2));
        $ssCount = $stewards->count() + $pending->count();

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
                'signed_at'     => $s->signed_at?->toDateString(),
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

        $notifyPrefs = [];
        try {
            $metaRow = \App\Models\UserMeta::where('user_id', $user->id)
                ->where('meta_key', 'notify_ss_activity')->first();
            if ($metaRow) {
                $notifyPrefs = json_decode($metaRow->meta_value, true) ?? [];
            }
        } catch (\Throwable $e) {}

        $availableAsSs = (bool) (\App\Models\UserMeta::where('user_id', $user->id)
            ->where('meta_key', 'available_as_ss')
            ->value('meta_value'));

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
            'availableAsSs'      => $availableAsSs,
        ]);
    }

    public function invite(DesignateStewardRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('update', $plan);

        $data = $request->validated();
        try {
            if (!empty($data['user_id'])) {
                $this->stewards->designate($plan, User::findOrFail($data['user_id']), 'support_steward', $data['role'] ?? 'primary');
            } else {
                $this->stewards->inviteExternal($plan, $data['email'], $data['display_name'] ?? 'Support Steward', 'support_steward', $data['role'] ?? 'primary');
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }
        return back()->with('success', 'Support Steward invited.');
    }

    public function remove(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $this->stewards->remove($steward, $request->user(), $request->input('reason'));
        return back()->with('success', 'Steward removed.');
    }

    public function suspend(Request $request, PlanSteward $steward): RedirectResponse
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
        $this->activity->log(
            $actor->id, 'provider', 'steward',
            ActivitySeverity::Warning, 'ss_suspended',
            'Support Steward access suspended',
            'Reason: ' . $data['reason'],
            'plan_steward', $steward->id,
            $steward->steward_id,
            'log', $actor->id
        );
        $this->activity->log(
            $steward->steward_id, 'support_steward', 'steward',
            ActivitySeverity::Warning, 'ss_suspended',
            'Your Support Steward access has been suspended',
            'Reason: ' . $data['reason'],
            'plan_steward', $steward->id,
            $actor->id,
            'notification', $actor->id
        );

        event(new SsSuspended($steward, $actor, $data['reason']));

        return back()->with('success', 'Support Steward access suspended.');
    }

    public function reinstate(Request $request, PlanSteward $steward): RedirectResponse
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

        event(new SsReinstated($steward, $actor));

        return back()->with('success', 'Support Steward reinstated.');
    }

    public function resend(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $steward->update(['invited_at' => now(), 'expires_at' => now()->addDays(30)]);
        return back()->with('success', 'Invitation resent.');
    }

    public function updateRole(Request $request, PlanSteward $steward): RedirectResponse
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

    public function updatePermissions(Request $request, PlanSteward $steward): RedirectResponse
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

    public function update(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $data = $request->validate([
            'display_name' => 'required|string|max:100',
            'relationship' => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:30',
            'role'         => 'nullable|in:primary,alternate,support',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $steward->update([
            'role'  => $data['role']  ?? $steward->role,
            'notes' => $data['notes'] ?? $steward->notes,
        ]);

        if ($steward->steward) {
            $steward->steward->update(array_filter([
                'display_name' => $data['display_name'],
                'phone'        => $data['phone'] ?? null,
            ]));
        }

        $actor = $request->user();
        $this->activity->log(
            $actor->id, 'provider', 'steward',
            ActivitySeverity::Info, 'ss_updated',
            'Support Steward details updated',
            'Contact information and role updated.',
            'plan_steward', $steward->id,
            $steward->steward_id,
            'log', $actor->id
        );
        $this->activity->log(
            $steward->steward_id, 'support_steward', 'steward',
            ActivitySeverity::Info, 'ss_updated',
            'Your Support Steward details have been updated',
            'The practitioner has updated your support steward record.',
            'plan_steward', $steward->id,
            $actor->id,
            'notification', $actor->id
        );

        return back()->with('success', 'Support Steward details updated.');
    }

    public function archive(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $reason = $request->input('reason');
        $details = $request->input('details');
        $note = $reason ? ($details ? $reason . ' — ' . $details : $reason) : null;

        // Prefix with 'terminated:' so index can distinguish from suspension
        $steward->update([
            'status'         => 'archived',
            'declined_reason' => $note ? 'terminated:' . $note : 'terminated',
        ]);
        return back()->with('success', 'Support Steward removed.');
    }

    public function downloadAgreement(Request $request, PlanSteward $steward): \Illuminate\Http\Response
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('view', $plan);

        $steward->load(['steward:id,display_name,email,credentials,organization']);
        $pdf  = app(\App\Services\AegisPdfService::class);
        $html = $pdf->ssAgreement($steward);

        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="ss-agreement-' . $steward->id . '.html"',
        ]);
    }
}
