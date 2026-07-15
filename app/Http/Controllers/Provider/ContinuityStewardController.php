<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Enums\ActivitySeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Steward\DesignateStewardRequest;
use App\Models\ContinuityPlan;
use App\Models\CsInvoice;
use App\Models\PlanIncidentConfig;
use App\Models\PlanSteward;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\AegisPdfService;
use App\Services\PlanService;
use App\Services\ProfileService;
use App\Services\StewardService;
use App\Enums\VaultAccess;
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
        private ProfileService $profiles,
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
                ->where('status', 'active')
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
                    'steward_id'   => $s->steward_id,
                    'email'        => $s->email,
                    'display_name' => $s->display_name,
                    'review_overdue' => $s->review_due_at && $s->review_due_at->isPast(),
                    'vault_access'   => is_object($s->vault_access) ? $s->vault_access->value : ($s->vault_access ?? 'scoped'),
                ])
                ->values()
            : [];

        $pendingInvitations = $plan
            ? \App\Models\PlanSteward::where('plan_id', $plan->id)
                ->where('steward_category', 'continuity_steward')
                ->whereIn('status', ['invited', 'pending'])
                ->with('steward:id,display_name,email,avatar_initials,credentials,location')
                ->get()
                ->map(fn ($s) => [
                    'id'           => $s->id,
                    'status'       => is_object($s->status) ? $s->status->value : $s->status,
                    // Resolve name/email from steward relation if not on the plan_steward row
                    'email'        => $s->email ?? $s->steward?->email ?? '',
                    'display_name' => $s->display_name ?? $s->steward?->display_name ?? '—',
                    'avatar_initials' => $s->steward?->avatar_initials ?? strtoupper(substr($s->display_name ?? $s->steward?->display_name ?? '?', 0, 2)),
                    'credentials'  => $s->steward?->credentials ?? '',
                    'location'     => $s->steward?->location ?? '',
                    'role'         => is_object($s->role) ? $s->role->value : $s->role,
                    'invited_at'   => $s->invited_at?->toDateString(),
                    'expires_at'   => $s->expires_at?->toDateString(),
                    'steward_id'   => $s->steward_id,
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
                'id'               => $s->id,
                'role'             => is_object($s->role) ? $s->role->value : $s->role,
                'status'           => is_object($s->status) ? $s->status->value : $s->status,
                'fee_cents'        => (int) ($s->fee_cents ?? 0),
                'display_name'     => $s->plan?->practitioner?->display_name ?? '—',
                'organization'     => $s->plan?->practitioner?->organization ?? '',
                'location'         => $s->plan?->practitioner?->location ?? '',
                'avatar_initials'  => $s->plan?->practitioner?->avatar_initials ?? '??',
                'slug'             => $s->plan?->practitioner?->slug ?? '',
                'review_due_at'    => $s->review_due_at?->toDateString(),
                'vault_access'     => $s->vault_access ?? 'none',
                'active_incidents' => $s->plan_id ? \App\Models\CriticalIncident::where('plan_id', $s->plan_id)->where('status', 'active')->count() : 0,
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
            'csCount'            => collect($stewards)->where('status', 'active')->count(),
            'incidentConfigs'    => $incidentConfigs,
            'annualReviewDue'    => $plan?->annual_review_date?->toDateString(),
            'planStatus'         => $plan?->status?->value ?? null,
            'annualReviewDate'   => $plan?->annual_review_date?->toISOString() ?? null,
            'hasDraftInProgress' => ContinuityPlan::where('practitioner_id', $user->id)
                ->where('status', 'draft')
                ->exists(),
            'draftPlanVersion'   => ContinuityPlan::where('practitioner_id', $user->id)
                ->where('status', 'draft')
                ->value('plan_version'),
            'notifyPrefs'        => $this->profiles->getMeta($user, 'notify_cs_activity', []),
        ]);
    }

    public function csInvite(DesignateStewardRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('update', $plan);

        $data = $request->validated();
        $role = $data['role'] ?? 'primary';

        try {
            // Path A: preselected_user_id — known Aegis CS Business user, designate directly
            if (!empty($data['preselected_user_id'])) {
                $csUser = User::findOrFail($data['preselected_user_id']);
                $this->stewards->designate($plan, $csUser, 'continuity_steward', $role, [
                    'fee_cents'     => $data['fee_cents']     ?? 0,
                    'payment_model' => $data['payment_terms'] ?? 'per_incident',
                ]);
            // Path B: existing user by user_id
            } elseif (!empty($data['user_id'])) {
                $this->stewards->designate($plan, User::findOrFail($data['user_id']), 'continuity_steward', $role, [
                    'fee_cents'     => $data['fee_cents']     ?? 0,
                    'payment_model' => $data['payment_terms'] ?? 'per_incident',
                ]);
            // Path C: external email invite
            } else {
                $this->stewards->inviteExternal(
                    $plan,
                    $data['email'],
                    $data['display_name'] ?? 'Continuity Steward',
                    'continuity_steward',
                    $role
                );
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }

        return back()->with('success', 'Continuity Steward invited.');
    }

    public function csUpdate(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $request->validate([
            'role'         => 'required|in:primary,alternate',
            'vault_access' => 'nullable|in:none,metadata,scoped,full',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $role        = $request->input('role');
        $notes       = $request->input('notes');
        $vaultAccess = $request->input('vault_access');

        $actor   = $request->user();
        $oldRole = is_object($steward->role) ? $steward->role->value : $steward->role;

        $updates = ['role' => $role];
        if ($vaultAccess !== null) {
            $updates['vault_access'] = $vaultAccess;
        }
        $steward->update($updates);

        if ($oldRole !== $role) {
            $this->stewards->requestRoleChange($steward, $role, $notes);
        }

        if ($vaultAccess !== null && $vaultAccess !== (is_object($steward->getOriginal('vault_access')) ? $steward->getOriginal('vault_access') : $steward->getOriginal('vault_access'))) {
            $this->activity->log(
                $steward->steward_id, 'continuity_steward', 'steward',
                ActivitySeverity::Info, 'vault_access_updated',
                'Vault access updated',
                "Provider updated your vault access to {$vaultAccess}",
                'plan_steward', $steward->id,
                $actor->id,
                'notification', $actor->id
            );
        }

        $this->activity->log(
            $actor->id, 'provider', 'steward',
            ActivitySeverity::Info, 'cs_updated',
            'Continuity Steward details updated',
            'Role: ' . $role . ($notes ? ' — ' . $notes : ''),
            'plan_steward', $steward->id,
            $steward->steward_id,
            'log', $actor->id
        );

        return back()->with('success', 'Steward updated.');
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

        $expiryDays = (int) $request->input('expiry_days', 14);
        $message    = $request->input('message');

        $this->stewards->resendInvite($steward, $request->user(), $expiryDays, $message);
        return back()->with('success', 'Invitation resent.');
    }

    public function downloadAgreement(Request $request, PlanSteward $steward): \Illuminate\Http\Response
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('view', $plan);

        $steward->load(['steward:id,display_name,email,credentials,organization']);
        $pdf = app(AegisPdfService::class);
        $html = $pdf->csAgreement($steward);

        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="cs-agreement-' . $steward->id . '.html"',
        ]);
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
    public function csVaultAccess(Request $request, PlanSteward $steward): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan || $steward->plan_id !== $plan->id, 404);
        $this->authorize('update', $plan);

        $data = $request->validate([
            'vault_access' => 'required|in:none,metadata,scoped,full',
        ]);

        $vaultAccess = $data['vault_access'];
        $steward->update(['vault_access' => $vaultAccess]);

        $user = $request->user();

        $this->activity->log(
            $steward->steward_id, 'continuity_steward', 'steward',
            ActivitySeverity::Info, 'vault_access_updated',
            'Vault access updated',
            "Provider updated your vault access to {$vaultAccess}",
            'plan_steward', $steward->id,
            $user->id,
            'notification', $user->id
        );

        $this->activity->log(
            $user->id, 'provider', 'steward',
            ActivitySeverity::Info, 'vault_access_updated',
            'CS vault access updated',
            "Vault access set to {$vaultAccess} for {$steward->steward?->display_name}.",
            'plan_steward', $steward->id,
            $steward->steward_id,
            'log', $user->id
        );

        return back()->with('success', 'Vault access updated.');
    }

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
