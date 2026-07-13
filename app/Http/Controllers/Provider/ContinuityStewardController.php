<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Enums\ActivitySeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Steward\DesignateStewardRequest;
use App\Models\CsInvoice;
use App\Models\PlanIncidentConfig;
use App\Models\PlanSteward;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\PlanService;
use App\Services\StewardService;
use App\Events\Steward\SsSuspended;
use App\Events\Steward\SsReinstated;
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
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        $tier = is_object($user->tier) ? $user->tier->value : ($user->tier ?? 'access');
        $tierLimits = config('aegis.tier_limits.' . $tier, config('aegis.tier_limits.access'));

        $stewards = $plan
            ? \App\Models\PlanSteward::where('plan_id', $plan->id)
                ->where('steward_category', 'continuity_steward')
                ->whereIn('status', ['active', 'pending', 'invited'])
                ->with('steward:id,display_name,credentials,email,phone,title,organization,location,avatar_initials,slug,stripe_account_id')
                ->get()
                ->map(fn ($s) => [
                    'id'                       => $s->id,
                    'role'                     => is_object($s->role) ? $s->role->value : $s->role,
                    'status'                   => is_object($s->status) ? $s->status->value : $s->status,
                    'fee_cents'                => (int) ($s->fee_cents ?? 0),
                    'payment_terms'            => $s->payment_terms ?? 'per_incident',
                    'auto_charge'              => (bool) ($s->auto_charge ?? false),
                    'engagement_document_id'   => $s->engagement_document_id,
                    'signed_at'                => $s->signed_at?->toDateString(),
                    'invited_at'               => $s->invited_at?->toDateString(),
                    'expires_at'               => $s->expires_at?->toDateString(),
                    'stripe_connected'         => (bool) ($s->steward?->stripe_account_id),
                    'has_outstanding_invoices' => \App\Models\CsInvoice::where('cs_id', $s->steward_id)
                        ->where('practitioner_id', $user->id)
                        ->whereIn('status', ['sent', 'overdue', 'disputed'])
                        ->exists(),
                    'steward' => $s->steward ? $s->steward->toArray() : null,
                    'steward_id' => $s->steward_id,
                    'email'      => $s->email,
                    'display_name' => $s->display_name,
                ])
                ->values()
            : [];

        $pendingInvitations = $plan
            ? \App\Models\PlanSteward::where('plan_id', $plan->id)
                ->where('steward_category', 'continuity_steward')
                ->whereIn('status', ['invited', 'pending'])
                ->get()
                ->map(fn ($s) => [
                    'id'         => $s->id,
                    'status'     => is_object($s->status) ? $s->status->value : $s->status,
                    'email'      => $s->email,
                    'display_name' => $s->display_name,
                    'role'       => is_object($s->role) ? $s->role->value : $s->role,
                    'invited_at' => $s->invited_at?->toDateString(),
                    'expires_at' => $s->expires_at?->toDateString(),
                ])
                ->values()
            : [];

        // CS the practitioner is serving under (cross-role)
        $servingAsCSFor = \App\Models\PlanSteward::where('steward_id', $user->id)
            ->where('steward_category', 'continuity_steward')
            ->whereIn('status', ['active', 'pending'])
            ->with('plan.practitioner:id,display_name,credentials,organization,location,avatar_initials,slug')
            ->get()
            ->map(fn ($s) => [
                'id'           => $s->id,
                'role'         => is_object($s->role) ? $s->role->value : $s->role,
                'status'       => is_object($s->status) ? $s->status->value : $s->status,
                'fee_cents'    => (int) ($s->fee_cents ?? 0),
                'display_name' => $s->plan?->practitioner?->display_name ?? '—',
                'organization' => $s->plan?->practitioner?->organization ?? '',
                'location'     => $s->plan?->practitioner?->location ?? '',
                'avatar_initials' => $s->plan?->practitioner?->avatar_initials ?? '??',
                'slug'         => $s->plan?->practitioner?->slug ?? '',
            ]);

        $incidentConfigs = $plan
            ? \App\Models\PlanIncidentConfig::where('plan_id', $plan->id)->get()->map(fn ($c) => [
                'incident_type'     => $c->incident_type,
                'is_active'         => (bool) $c->is_active,
                'authorized_cs_ids' => is_string($c->authorized_cs_ids) ? json_decode($c->authorized_cs_ids, true) : ($c->authorized_cs_ids ?? []),
            ])->values()
            : [];

        return Inertia::render('Provider/ContinuityStewards', [
            'stewards'           => $stewards,
            'pendingInvitations' => $pendingInvitations,
            'servingAsCSFor'     => $servingAsCSFor,
            'tierLimits'         => $tierLimits,
            'tier'               => $tier,
            'csMax'              => (int) ($tierLimits['max_continuity_stewards'] ?? 2),
            'csCount'            => count($stewards),
            'incidentConfigs'    => $incidentConfigs,
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

        try {
            $this->stewards->remove($steward, $request->user(), $request->input('reason'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['remove' => $e->getMessage()]);
        }

        return back()->with('success', 'Steward removed.');
    }

    public function csUpdateFee(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $data = $request->validate([
            'fee_cents'     => 'required|integer|min:0',
            'payment_terms' => 'required|in:per_incident,monthly,flat_rate',
        ]);

        $this->stewards->updateFee($steward, (int) $data['fee_cents'], $data['payment_terms'], $request->user());
        return back()->with('success', 'Fee amendment created. Awaiting CS countersignature.');
    }

    public function csResendInvite(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $this->stewards->resendInvite($steward, $request->user());
        return back()->with('success', 'Invitation resent.');
    }

    public function csCancelInvite(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $this->stewards->cancelInvite($steward, $request->user());
        return back()->with('success', 'Invitation cancelled.');
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
        $tier = is_object($user->tier) ? $user->tier->value : ($user->tier ?? 'access');

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

        event(new SsSuspended($steward, $actor, $data['reason']));

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

        event(new SsReinstated($steward, $actor));

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
