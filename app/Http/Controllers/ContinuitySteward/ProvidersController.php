<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Enums\ActivitySeverity;
use App\Http\Controllers\Controller;
use App\Models\ContinuityDocument;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\DocumentService;
use App\Services\StewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CS "My Practitioners" — active roster + pending invitations.
 */
class ProvidersController extends Controller
{
    public function __construct(
        private DocumentService $documents,
        private StewardService  $stewards,
        private ActivityService $activity,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // Active roster — plans where this CS is currently designated
        $activePlanIds = PlanSteward::where('steward_id', $user->id)
            ->where('steward_type', 'continuity_steward')
            ->where('status', 'active')
            ->pluck('plan_id');

        $activePlans = ContinuityPlan::whereIn('id', $activePlanIds)
            ->with(['practitioner:id,display_name,slug,email'])
            ->get();

        $providers = $activePlans->map(function (ContinuityPlan $p) {
            return [
                'id'                => $p->practitioner_id,
                'plan_id'           => $p->id,
                'display_name'      => $p->practitioner?->display_name ?? '—',
                'slug'              => $p->practitioner?->slug,
                'vault_count'       => $p->vault_count ?? 0,
                'last_attested_at'  => $p->vault_attested_at,
                'incident_active'   => in_array((string) ($p->status ?? ''), ['incident_reported', 'incident_active'], true),
            ];
        })->values();

        // Pending — invitations awaiting this CS's acceptance
        $pending = PlanSteward::where('steward_id', $user->id)
            ->where('steward_type', 'continuity_steward')
            ->whereIn('status', ['invited', 'pending'])
            ->with(['plan.practitioner:id,display_name,slug'])
            ->orderByDesc('invited_at')
            ->get()
            ->map(fn (PlanSteward $s) => [
                'id'            => $s->id,           // PlanSteward id (used as {invitation})
                'display_name'  => $s->plan?->practitioner?->display_name ?? '—',
                'practitioner_id'=> $s->plan?->practitioner_id,
                'role'          => $s->role,
                'requested_at'  => $s->invited_at ?? $s->created_at,
            ]);

        return Inertia::render('continuity-steward/Providers', [
            'providers' => $providers,
            'pending'   => $pending,
        ]);
    }

    public function show(Request $request, User $provider): Response
    {
        // Guard: CS must actively steward this provider
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)
            ->orderByDesc('created_at')
            ->firstOrFail();
        $this->ensureAssigned($request->user()->id, $plan->id);

        return Inertia::render('continuity-steward/Providers', [
            'detail' => [
                'provider' => $provider->only(['id', 'display_name', 'email', 'slug']),
                'plan'     => $plan->only(['id', 'title', 'status', 'created_at']),
            ],
        ]);
    }

    public function plan(Request $request, User $provider): Response
    {
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)
            ->orderByDesc('created_at')->firstOrFail();
        $this->ensureAssigned($request->user()->id, $plan->id);

        return Inertia::render('continuity-steward/Providers', [
            'detail' => [
                'provider'  => $provider,
                'plan'      => $plan,
            ],
        ]);
    }

    public function countersign(Request $request, User $provider, ContinuityDocument $document = null): RedirectResponse
    {
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)->firstOrFail();
        $this->ensureAssigned($request->user()->id, $plan->id);

        $document = $document ?? ContinuityDocument::where('plan_id', $plan->id)
            ->where('status', 'countersign_pending')->latest()->firstOrFail();
        $this->authorize('countersign', $document);

        $data = $request->validate(['name' => 'required|string|max:100']);
        $this->documents->countersign($document, $request->user(), ['name' => $data['name'], 'ip' => $request->ip()]);
        return back()->with('success', 'Document countersigned.');
    }

    /**
     * Accept a pending steward-designation invitation.
     * The {invitation} param binds to the PlanSteward row (not the User).
     */
    public function acceptInvitation(Request $request, PlanSteward $invitation): RedirectResponse
    {
        abort_unless($invitation->steward_id === $request->user()->id, 403);

        $status = $invitation->status instanceof \BackedEnum ? $invitation->status->value : (string) $invitation->status;
        if (!in_array($status, ['invited', 'pending'], true)) {
            return back()->withErrors(['invitation' => 'This invitation is no longer pending.']);
        }

        $note = $request->input('note');
        $this->stewards->accept($invitation, is_string($note) ? $note : null);

        return back()->with('success', 'Designation accepted. You are now an active Continuity Steward for this practitioner.');
    }

    /**
     * Decline a pending steward-designation invitation.
     */
    public function declineInvitation(Request $request, PlanSteward $invitation): RedirectResponse
    {
        abort_unless($invitation->steward_id === $request->user()->id, 403);

        $status = $invitation->status instanceof \BackedEnum ? $invitation->status->value : (string) $invitation->status;
        if (!in_array($status, ['invited', 'pending'], true)) {
            return back()->withErrors(['invitation' => 'This invitation is no longer pending.']);
        }

        $reason = $request->input('reason');
        $this->stewards->decline($invitation, is_string($reason) ? $reason : null);

        return back()->with('success', 'Designation declined. The practitioner has been notified.');
    }

    private function ensureAssigned(string $userId, string $planId): void
    {
        $exists = PlanSteward::where('plan_id', $planId)
            ->where('steward_id', $userId)
            ->where('status', 'active')
            ->exists();
        abort_unless($exists, 403);
    }
}
