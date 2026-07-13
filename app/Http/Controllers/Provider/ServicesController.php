<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\AcceptServiceRequestRequest;
use App\Http\Requests\Provider\SendServiceRequestFromExploreRequest;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceSession;
use App\Models\SessionRefundRequest;
use App\Services\ServiceService;
use App\Services\ServiceSessionPdfService;
use App\Services\SessionRefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServicesController extends Controller
{
    public function __construct(
        private ServiceService           $services,
        private SessionRefundService     $refunds,
        private ServiceSessionPdfService $pdf,
    ) {}

    // ═══════════════════════════════════════════════════════════════════════
    // INDEX — main Services page with all tabs
    // ═══════════════════════════════════════════════════════════════════════

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->tier?->value === 'access') {
            return redirect()->route('provider.settings.index', ['section' => 'billing', 'upgrade' => '1']);
        }

        $user->loadMissing('meta');
        $filters        = $request->only(['q', 'category', 'status']);
        $exploreFilters = $request->only(['q', 'category', 'format', 'availability', 'sort', 'min_price_cents', 'max_price_cents']);

        // ── Paginated provider sessions (I run) ──────────────────────────────
        $bookingsPaginator = $this->services->getSessionsForPractitioner($user->id, 10);
        $bookings = collect($bookingsPaginator->items())
            ->map(fn($s) => $this->services->shapeSession($s))
            ->values();

        // ── Paginated client sessions (I booked) ─────────────────────────────
        $clientSessionsPaginator = $this->services->getSessionsAsClient($user->id, 10);
        $clientSessions = collect($clientSessionsPaginator->items())
            ->map(fn($s) => $this->services->shapeClientSession($s))
            ->values();

        // ── Explore results (browse public services from other practitioners) ─
        $explorePaginator = $this->services->getForExplore($exploreFilters, $user->id, 12);
        $exploreResults = collect($explorePaginator->items())
            ->map(fn($s) => $this->services->shapeForExplore($s))
            ->values();

        // ── Refund requests incoming (I am the provider) ─────────────────────
        $incomingRefundRequests = SessionRefundRequest::where('provider_id', $user->id)
            ->with(['session.service', 'requester'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(SessionRefundRequest $r) => $this->shapeRefundRequest($r))
            ->values();

        return Inertia::render('Provider/Services', [
            // ── Existing tabs ─────────────────────────────────────────────────
            'listings'         => $this->services->getForPractitioner($user->id, $filters)
                                    ->map(fn($s) => $this->services->shapeForListing($s))
                                    ->values(),
            'serviceRequests'  => $this->services->getRequestsForPractitioner($user->id)
                                    ->map(fn($r) => $this->services->shapeRequest($r))
                                    ->values(),
            'outgoingRequests' => $this->services->getRequestsSentByPractitioner($user->id)
                                    ->map(fn($r) => $this->services->shapeOutgoingRequest($r))
                                    ->values(),
            // ── Updated: paginated + full payment shape ───────────────────────
            'bookings'         => $bookings,
            'bookingsMeta'     => [
                'current_page'  => $bookingsPaginator->currentPage(),
                'last_page'     => $bookingsPaginator->lastPage(),
                'total'         => $bookingsPaginator->total(),
                'per_page'      => $bookingsPaginator->perPage(),
            ],
            'clientSessions'   => $clientSessions,
            'clientSessionsMeta' => [
                'current_page'  => $clientSessionsPaginator->currentPage(),
                'last_page'     => $clientSessionsPaginator->lastPage(),
                'total'         => $clientSessionsPaginator->total(),
                'per_page'      => $clientSessionsPaginator->perPage(),
            ],
            // ── Wave 3 new: explore tab ───────────────────────────────────────
            'exploreResults'   => $exploreResults,
            'exploreMeta'      => [
                'current_page'  => $explorePaginator->currentPage(),
                'last_page'     => $explorePaginator->lastPage(),
                'total'         => $explorePaginator->total(),
                'per_page'      => $explorePaginator->perPage(),
            ],
            'exploreFilters'   => $exploreFilters,
            // ── Wave 3 new: refund requests for provider ─────────────────────
            'incomingRefundRequests' => $incomingRefundRequests,
            // ── Existing ─────────────────────────────────────────────────────
            'stats'            => $this->services->statsForPractitioner($user),
            'profileCompletion'=> (int) ($user->profile_completion ?? 0),
            'servicesMode'     => (bool) $user->services_mode,
            'heroRating'       => '4.8 / 5.0',
            'filters'          => $filters,
            'serviceProfile'   => $this->buildServiceProfile($user),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // EXPLORE — infinite scroll endpoint (JSON for subsequent pages)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * GET /provider/services/explore?page=N&category=...
     * Used by the explore tab infinite scroll after initial page load.
     * Returns JSON so Vue can append cards without a full Inertia reload.
     */
    public function explore(Request $request): JsonResponse
    {
        $user    = $request->user();
        $filters = $request->only(['q', 'category', 'format', 'availability', 'sort', 'min_price_cents', 'max_price_cents']);
        $page    = (int) $request->get('page', 1);

        $paginator = $this->services->getForExplore($filters, $user->id, 12);
        $results   = collect($paginator->items())
            ->map(fn($s) => $this->services->shapeForExplore($s))
            ->values();

        return response()->json([
            'results'      => $results,
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STORE SERVICE — create a new listing
    // ═══════════════════════════════════════════════════════════════════════

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'              => 'required|string|max:200',
            'description'        => 'nullable|string|max:5000',
            'category'           => 'nullable|string|max:100',
            'price_cents'        => 'nullable|integer|min:0',
            'price_type'         => 'nullable|string|in:fixed,hourly,session,inquiry',
            'duration_min'       => 'nullable|integer|min:5|max:480',
            'format'             => 'nullable|string|in:telehealth,in_person,both',
            'availability'       => 'nullable|string|in:open,limited',
            'availability_label' => 'nullable|string|max:60',
            'is_public'          => 'nullable|boolean',
            'status'             => 'nullable|string|in:active,draft',
        ]);
        $this->services->create($request->user(), $data);
        return back()->with('success', 'Service created.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UPDATE SERVICE
    // ═══════════════════════════════════════════════════════════════════════

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('manage', $service);
        $data = $request->validate([
            'title'              => 'nullable|string|max:200',
            'description'        => 'nullable|string|max:5000',
            'category'           => 'nullable|string|max:100',
            'price_cents'        => 'nullable|integer|min:0',
            'price_type'         => 'nullable|string|in:fixed,hourly,session,inquiry',
            'duration_min'       => 'nullable|integer|min:5|max:480',
            'format'             => 'nullable|string|in:telehealth,in_person,both',
            'availability'       => 'nullable|string|in:open,limited',
            'availability_label' => 'nullable|string|max:60',
            'status'             => 'nullable|string|in:active,inactive,draft,paused,archived',
            'is_public'          => 'nullable|boolean',
        ]);
        $this->services->update($service, $data);
        return back()->with('success', 'Service updated.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // DELETE SERVICE
    // ═══════════════════════════════════════════════════════════════════════

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('manage', $service);
        $this->services->archive($service);
        return back()->with('success', 'Service archived.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SERVICE REQUESTS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Accept a service request. Now uses typed FormRequest.
     * Supports optional negotiated_amount_cents for price negotiation.
     */
    public function acceptRequest(AcceptServiceRequestRequest $request, Service $service, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('manage', $service);
        $this->services->acceptRequest($serviceRequest, $request->validated());
        return back()->with('success', 'Request accepted. Client notified to pay their deposit.');
    }

    public function declineRequest(Request $request, Service $service, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('manage', $service);
        $this->services->declineRequest($serviceRequest, $request->input('reason'));
        return back()->with('success', 'Request declined.');
    }

    public function withdrawRequest(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->services->withdrawRequest($serviceRequest, $request->user()->id);
        return back()->with('success', 'Request withdrawn.');
    }

    /**
     * Send a service request FROM the explore tab (no public profile involved).
     * Uses a separate FormRequest so validation is explicit and route is separate.
     */
    public function storeExploreRequest(SendServiceRequestFromExploreRequest $request): RedirectResponse
    {
        $data    = $request->validated();
        $service = Service::findOrFail($data['service_id']);
        $this->services->submitRequest($service, $request->user(), $data);
        return back()->with('success', 'Service request sent. The provider will respond within 72 hours.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SESSIONS — payment flow
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * POST /provider/services/sessions/{session}/deposit
     * Client pays the 30% deposit to confirm the booking.
     * Outside services.mode middleware — any authenticated provider-as-client can pay.
     */
    public function payDeposit(Request $request, ServiceSession $session): RedirectResponse
    {
        $request->validate([
            'agree_terms' => 'required|accepted',
        ]);

        try {
            $this->services->payDeposit($session, $request->user());
            return back()->with('success', 'Deposit paid. Your session is confirmed.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['deposit' => $e->getMessage()]);
        }
    }

    /**
     * POST /provider/services/sessions/{session}/complete
     * Client confirms session happened and pays the 70% balance.
     * Outside services.mode middleware — any authenticated provider-as-client can confirm.
     */
    public function completeSession(Request $request, ServiceSession $session): RedirectResponse
    {
        try {
            $this->services->completeSession($session, $request->user()->id);
            return back()->with('success', 'Session confirmed. Full payment sent to the provider.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['session' => $e->getMessage()]);
        }
    }

    public function cancelSession(Request $request, ServiceSession $session): RedirectResponse
    {
        $this->authorize('manage', $session->service);
        $data = $request->validate([
            'reason'           => 'required|string|max:255',
            'note'             => 'nullable|string|max:1000',
            'offer_reschedule' => 'nullable|boolean',
        ]);
        $this->services->cancelSession($session, $data);
        return back()->with('success', 'Session cancelled.');
    }

    public function saveSessionNotes(Request $request, ServiceSession $session): RedirectResponse
    {
        $this->authorize('manage', $session->service);
        $data = $request->validate([
            'summary'               => 'nullable|string|max:5000',
            'action_items'          => 'nullable|string|max:2000',
            'share_with_supervisee' => 'nullable|boolean',
        ]);
        $this->services->saveSessionNotes($session, $data);
        return back()->with('success', 'Notes saved.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // INVOICE DOWNLOAD
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * GET /provider/services/sessions/{session}/invoice
     * Returns a printable HTML session invoice, rendered by the unified
     * AegisPdfService. Both the client and the provider can download.
     */
    public function downloadInvoice(Request $request, ServiceSession $session)
    {
        $user = $request->user();

        // Only parties involved in the session can download
        if ($session->client_id !== $user->id && $session->practitioner_id !== $user->id) {
            abort(403);
        }

        return response($this->pdf->render($session), 200, [
            'Content-Type'  => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Robots-Tag'  => 'noindex',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REFUND REQUESTS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * POST /provider/services/sessions/{session}/refund-requests
     * Client opens a refund request for a session they paid for.
     */
    public function storeRefundRequest(Request $request, ServiceSession $session): RedirectResponse
    {
        $data = $request->validate([
            'reason'        => 'required|string|in:session_did_not_occur,provider_no_show,quality_issue,duplicate_charge,session_cancelled_by_provider,other',
            'reason_detail' => 'nullable|string|max:2000',
            'refund_type'   => 'required|string|in:deposit_only,balance_only,full',
        ]);

        try {
            $this->refunds->open($session, $request->user(), $data);
            return back()->with('success', 'Refund request submitted. The provider has ' . env('DISPUTE_RESPONDENT_REPLY_DAYS', 5) . ' days to respond.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        }
    }

    /**
     * POST /provider/services/refund-requests/{refund}/approve
     * Provider approves a refund request — Stripe refund issued immediately.
     */
    public function approveRefundRequest(Request $request, SessionRefundRequest $refund): RedirectResponse
    {
        try {
            $this->refunds->approve($refund, $request->user());
            return back()->with('success', 'Refund approved. Funds will be returned to the client within 5–10 business days.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        }
    }

    /**
     * POST /provider/services/refund-requests/{refund}/deny
     * Provider denies a refund request with a mandatory note.
     */
    public function denyRefundRequest(Request $request, SessionRefundRequest $refund): RedirectResponse
    {
        $data = $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        try {
            $this->refunds->deny($refund, $request->user(), $data['note']);
            return back()->with('success', 'Refund request denied. Client has been notified and may escalate.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        }
    }

    /**
     * POST /provider/services/refund-requests/{refund}/escalate
     * Client escalates a denied request to the formal dispute system.
     */
    public function escalateRefundRequest(Request $request, SessionRefundRequest $refund): RedirectResponse
    {
        try {
            $dispute = $this->refunds->escalate($refund, $request->user());
            return back()->with('success', 'Escalated to dispute #' . substr($dispute->id, 0, 8) . '. Our team will review.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PROFILE SERVICES (settings)
    // ═══════════════════════════════════════════════════════════════════════

    // (Kept in ProviderProfileController — not moved here)

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    private function buildServiceProfile(\App\Models\User $user): array
    {
        $raw = fn(string $k) => optional($user->meta->firstWhere('meta_key', $k))->meta_value;
        $str = fn(string $k) => ($v = $raw($k)) !== null
            ? (is_string($d = json_decode($v, true)) ? $d : ($d === null ? $v : $v))
            : '';
        $arr = fn(string $k) => ($v = $raw($k)) !== null
            ? (is_array($d = json_decode($v, true))
                ? $d
                : (is_array($d2 = json_decode($d ?? '', true)) ? $d2 : []))
            : [];

        return [
            'headline'         => $str('service_headline'),
            'bio'              => $str('service_bio'),
            'years_experience' => (int) ($raw('years_experience') ?? 0),
            'specialties'      => $arr('service_specialties'),
        ];
    }

    private function shapeRefundRequest(SessionRefundRequest $r): array
    {
        $status = $r->status instanceof \App\Enums\SessionRefundRequestStatus
            ? $r->status
            : \App\Enums\SessionRefundRequestStatus::from((string) $r->status);

        $refundType = $r->refund_type instanceof \App\Enums\SessionRefundType
            ? $r->refund_type
            : \App\Enums\SessionRefundType::tryFrom((string) $r->refund_type);

        return [
            'id'                     => $r->id,
            'session_id'             => $r->session_id,
            'service_title'          => $r->session?->service?->title ?? '—',
            'session_date'           => $r->session?->scheduled_at?->format('M j, Y') ?? '—',
            'requested_by_name'      => $r->requester?->display_name ?? '—',
            'requested_by_avatar'    => $r->requester?->avatar_initials ?? '',
            'reason'                 => $r->reason,
            'reason_detail'          => $r->reason_detail,
            'refund_type'            => $r->refund_type instanceof \App\Enums\SessionRefundType ? $r->refund_type->value : (string) $r->refund_type,
            'refund_type_label'      => $refundType?->label() ?? (string) $r->refund_type,
            'amount_requested_cents' => (int) $r->amount_requested_cents,
            'amount_requested'       => '$' . number_format($r->amount_requested_cents / 100, 2),
            'status'                 => $status->value,
            'status_label'           => $status->label(),
            'status_variant'         => $status->badgeVariant(),
            'provider_response'      => $r->provider_response,
            'responded_at'           => $r->responded_at?->format('M j, Y'),
            'provider_deadline_at'   => $r->provider_deadline_at?->format('M j, Y g:i A'),
            'is_overdue'             => $r->is_overdue,
            'can_escalate'           => $r->can_escalate,
            'is_actionable'          => $r->is_actionable,
            'refunded_cents'         => (int) $r->refunded_cents,
            'created_at'             => $r->created_at->format('M j, Y'),
        ];
    }
}
