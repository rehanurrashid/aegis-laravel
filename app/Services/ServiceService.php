<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Enums\ServiceSessionPaymentStatus;
use App\Enums\ServiceStatus;
use App\Enums\ServiceSessionStatus;
use App\Enums\UserTier;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceSession;
use App\Models\SessionRefundRequest;
use App\Models\User;
use App\Models\PractitionerPayment;
use App\Enums\PractitionerPaymentKind;
use App\Enums\PractitionerPaymentStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use App\Services\PayoutService;

class ServiceService
{
    public function __construct(
        private ActivityService $activity,
        private PayoutService   $payouts,
    ) {}

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function typeIcon(string $category): string
    {
        return match ($category) {
            'supervision'         => 'graduation-cap',
            'consultation'        => 'message',
            'training'            => 'book-open',
            'coaching'            => 'leaf',
            'practice_continuity' => 'shield',
            default               => 'briefcase',
        };
    }

    private function formatPrice(Service $s): array
    {
        $type = $s->price_type instanceof \App\Enums\ServicePriceType
            ? $s->price_type->value
            : (string) ($s->price_type ?? 'inquiry');

        if (!$s->price_cents || $type === 'inquiry') {
            return ['price' => 'Contact for pricing', 'price_unit' => '', 'price_note' => ''];
        }
        $dollars = '$' . number_format($s->price_cents / 100, 0);
        $unit    = match ($type) {
            'hourly'  => '/ hr',
            'session' => '/ session',
            default   => '',
        };
        return ['price' => $dollars, 'price_unit' => $unit, 'price_note' => ''];
    }

    private function formatMoney(int $cents): string
    {
        return '$' . number_format($cents / 100, 2);
    }

    // ── Shaping for Vue ───────────────────────────────────────────────────────

    public function shapeForListing(Service $s): array
    {
        $priceData = $this->formatPrice($s);
        $category  = $s->category ?? 'other';
        $status    = $s->status instanceof ServiceStatus ? $s->status->value : (string) ($s->status ?? 'active');

        $meta = [];
        if ($s->duration_min) $meta[] = ['icon' => 'clock',    'text' => $s->duration_min . ' min'];
        if ($s->format)       $meta[] = ['icon' => 'monitor',  'text' => match ($s->format) { 'telehealth' => 'Virtual', 'in_person' => 'In-person', 'both' => 'Virtual & In-person', default => $s->format }];
        if ($s->availability) $meta[] = ['icon' => 'calendar', 'text' => $s->availability_label ?: ucfirst($s->availability)];

        $sessionCount    = ServiceSession::where('service_id', $s->id)->count();
        $completedCount  = ServiceSession::where('service_id', $s->id)->where('status', ServiceSessionStatus::Completed->value)->count();
        $pendingRequests = ServiceRequest::where('service_id', $s->id)->where('status', 'new')->count();

        return [
            'id'           => $s->id,
            'title'        => $s->title,
            'service_type' => ucfirst(str_replace('_', ' ', $category)),
            'type_icon'    => $this->typeIcon($category),
            'description'  => $s->description ?? '',
            'status'       => $status,
            'meta'         => $meta,
            'price'        => $priceData['price'],
            'price_unit'   => $priceData['price_unit'],
            'price_note'   => $priceData['price_note'],
            'price_cents'  => $s->price_cents,
            'price_type'   => $s->price_type instanceof \App\Enums\ServicePriceType ? $s->price_type->value : ($s->price_type ?? 'inquiry'),
            'duration_min' => $s->duration_min,
            'format'       => $s->format,
            'availability' => $s->availability,
            'is_public'    => (bool) $s->is_public,
            'category'     => $category,
            'metrics'      => [
                ['val' => (string) $sessionCount,    'label' => 'Sessions'],
                ['val' => (string) $completedCount,  'label' => 'Completed'],
                ['val' => (string) $pendingRequests, 'label' => 'Requests'],
            ],
        ];
    }

    /**
     * Shape a service for the public explore grid (search-by-other-practitioners).
     * Includes practitioner info for the card.
     */
    public function shapeForExplore(Service $s): array
    {
        $priceData   = $this->formatPrice($s);
        $category    = $s->category ?? 'other';
        $practitioner = $s->practitioner;

        return [
            'id'                    => $s->id,
            'title'                 => $s->title,
            'description'           => $s->description ?? '',
            'category'              => $category,
            'service_type'          => ucfirst(str_replace('_', ' ', $category)),
            'type_icon'             => $this->typeIcon($category),
            'price'                 => $priceData['price'],
            'price_unit'            => $priceData['price_unit'],
            'price_cents'           => $s->price_cents ?? 0,
            'price_type'            => $s->price_type instanceof \App\Enums\ServicePriceType ? $s->price_type->value : ($s->price_type ?? 'inquiry'),
            'duration_min'          => $s->duration_min,
            'format'                => $s->format,
            'availability'          => $s->availability,
            'availability_label'    => $s->availability_label,
            // ── Practitioner info for card ─────────────────────────────────
            'practitioner_id'       => $s->practitioner_id,
            'practitioner_name'     => $practitioner?->display_name ?? 'Unknown',
            'practitioner_slug'     => $practitioner?->slug ?? '',
            'practitioner_avatar'   => $practitioner?->avatar_initials ?? '',
            'practitioner_avatar_url'   => $practitioner?->avatar_url ?? null,
            'practitioner_credentials' => $practitioner?->credentials ?? '',
            'practitioner_connected'=> (bool) ($practitioner?->stripe_connected ?? false),
        ];
    }

    public function shapeRequest(ServiceRequest $r): array
    {
        $inquirer = $r->inquirer;
        $service  = $r->service;
        $status   = $r->status instanceof \App\Enums\ServiceRequestStatus
            ? $r->status->value
            : (string) ($r->status ?? 'new');

        return [
            'id'                   => $r->id,
            'service_id'           => $r->service_id,
            'requester_id'         => $r->inquirer_id,
            'requester_name'       => $inquirer?->display_name ?? $r->inquirer_name ?? 'Unknown',
            'requester_slug'       => $inquirer?->slug ?? '',
            'requester_avatar'     => $inquirer?->avatar_initials ?? '',
            'requester_detail'     => $inquirer?->credentials ?? '',
            'service_title'        => $service?->title ?? '',
            'service_price_cents'  => $service?->price_cents ?? 0,
            'service_price'        => $service?->price_cents ? $this->formatMoney($service->price_cents) : 'Contact for pricing',
            'request_type'         => ucfirst(str_replace('_', ' ', $service?->category ?? '')),
            'requested_date_label' => $r->created_at?->format('M j, Y') ?? '',
            'time_label'           => $r->created_at?->diffForHumans() ?? '',
            'status'               => $status,
            'message'              => $r->message ?? '',
            'preferred_timezone'   => $r->preferred_timezone ?? '',
            'preferred_date'       => $r->preferred_date ?? '',
            'preferred_time'       => $r->preferred_time ?? '',
        ];
    }

    /**
     * Shape a session for the provider's Bookings tab (they are the practitioner).
     */
    public function shapeSession(ServiceSession $s): array
    {
        $client  = $s->client;
        $service = $s->service;
        $status  = $s->status instanceof ServiceSessionStatus ? $s->status->value : (string) ($s->status ?? 'scheduled');
        $tz      = $s->timezone ?? 'America/New_York';

        $paymentStatus = $s->payment_status instanceof ServiceSessionPaymentStatus
            ? $s->payment_status
            : (isset($s->payment_status) ? ServiceSessionPaymentStatus::tryFrom(($s->payment_status instanceof \App\Enums\ServiceSessionPaymentStatus ? $s->payment_status->value : (string) $s->payment_status)) : null);

        $agreedCents  = $s->agreed_amount_cents;
        $depositCents = $s->deposit_cents ?? 0;
        $balanceCents = $s->balance_cents ?? 0;

        // Active refund request if any
        $refundReq = $s->activeRefundRequest;

        return [
            'id'                        => $s->id,
            'invoice_number'            => $s->invoice_number,
            'service_id'                => $s->service_id,
            'service_request_id'        => $s->service_request_id,
            'client_id'                 => $s->client_id,
            'client_name'               => $client?->display_name ?? 'Unknown',
            'client_slug'               => $client?->slug ?? '',
            'client_avatar'             => $client?->avatar_initials ?? '',
            'client_avatar_url'             => $client?->avatar_url ?? null,
            'client_credentials'        => $client?->credentials ?? '',
            'service_title'             => $service?->title ?? '',
            'datetime_label'            => $s->scheduled_at ? $s->scheduled_at->setTimezone($tz)->format('M j, Y g:i A T') : '—',
            'timezone'                  => $tz,
            'duration_label'            => $service?->duration_min ? $service->duration_min . ' min' : '—',
            // ── Pricing ────────────────────────────────────────────────────
            'original_amount_cents'     => $s->original_amount_cents ?? $s->amount_cents ?? 0,
            'agreed_amount_cents'       => $agreedCents,
            'negotiated_amount_cents'   => $s->negotiated_amount_cents,
            'deposit_cents'             => $depositCents,
            'deposit_paid_at'           => $s->deposit_paid_at?->format('M j, Y g:i A'),
            'balance_cents'             => $balanceCents,
            'balance_paid_at'           => $s->balance_paid_at?->format('M j, Y g:i A'),
            'total_refunded_cents'      => $s->total_refunded_cents ?? 0,
            'amount'                    => $this->formatMoney($agreedCents),
            'amount_cents'              => $agreedCents,
            'deposit_label'             => $depositCents > 0 ? $this->formatMoney($depositCents) : null,
            'balance_label'             => $balanceCents > 0 ? $this->formatMoney($balanceCents) : null,
            // ── Lifecycle ──────────────────────────────────────────────────
            'status'                    => $status,
            'payment_status'            => $paymentStatus?->value ?? 'unpaid',
            'payment_status_label'      => $paymentStatus?->label() ?? 'Unpaid',
            'payment_status_variant'    => $paymentStatus?->badgeVariant() ?? 'gold',
            'has_pending_refund_request'=> (bool) $refundReq,
            'pending_refund_request_id' => $refundReq?->id,
            // ── Stripe ─────────────────────────────────────────────────────
            'practitioner_stripe_connected' => (bool) ($s->practitioner?->stripe_connected ?? false),
            'practitioner_stripe_account'   => $s->practitioner?->stripe_account_id ?? null,
            // ── Notes ──────────────────────────────────────────────────────
            'summary'                   => $s->session_summary ?? '',
            'action_items'              => $s->session_action_items ?? '',
            'share_notes_with_client'   => (bool) ($s->share_notes_with_client ?? false),
        ];
    }

    /**
     * Shape a session for the client's "My Booked Sessions" view (they are the payer).
     */
    public function shapeClientSession(ServiceSession $s): array
    {
        $practitioner = $s->practitioner;
        $service      = $s->service;
        $status       = $s->status instanceof ServiceSessionStatus ? $s->status->value : (string) ($s->status ?? 'scheduled');
        $tz           = $s->timezone ?? 'America/New_York';

        $paymentStatus = $s->payment_status instanceof ServiceSessionPaymentStatus
            ? $s->payment_status
            : (isset($s->payment_status) ? ServiceSessionPaymentStatus::tryFrom(($s->payment_status instanceof \App\Enums\ServiceSessionPaymentStatus ? $s->payment_status->value : (string) $s->payment_status)) : ServiceSessionPaymentStatus::Unpaid);

        $agreedCents  = $s->agreed_amount_cents;
        $depositCents = $s->deposit_cents ?? 0;
        $balanceCents = $s->balance_cents ?? 0;
        $expectedDeposit  = $s->expected_deposit_cents;
        $expectedBalance  = $s->expected_balance_cents;

        // Refund requests I (client) submitted
        $myRefundReq = SessionRefundRequest::where('session_id', $s->id)
            ->where('requested_by_id', $s->client_id)
            ->latest()
            ->first();

        return [
            'id'                            => $s->id,
            'invoice_number'                => $s->invoice_number,
            'service_id'                    => $s->service_id,
            'service_request_id'            => $s->service_request_id,
            'practitioner_id'               => $s->practitioner_id,
            'practitioner_name'             => $practitioner?->display_name ?? 'Unknown',
            'practitioner_slug'             => $practitioner?->slug ?? '',
            'practitioner_avatar'           => $practitioner?->avatar_initials ?? '',
            'practitioner_avatar_url'       => $practitioner?->avatar_url ?? null,
            'practitioner_detail'           => $practitioner?->credentials ?? '',
            'service_title'                 => $service?->title ?? 'Session',
            'request_type'                  => ucfirst(str_replace('_', ' ', $service?->category ?? '')),
            'datetime_label'                => $s->scheduled_at?->setTimezone($tz)->format('M j, Y g:i A T') ?? 'TBD',
            'timezone'                      => $tz,
            'duration_label'                => $service?->duration_min ? $service->duration_min . ' min' : 'TBD',
            // ── Pricing breakdown ──────────────────────────────────────────
            'original_amount_cents'         => $s->original_amount_cents ?? $s->amount_cents ?? 0,
            'agreed_amount_cents'           => $agreedCents,
            'negotiated_amount_cents'       => $s->negotiated_amount_cents,
            'amount'                        => $this->formatMoney($agreedCents),
            'amount_cents'                  => $agreedCents,
            // What's been charged so far
            'deposit_cents'                 => $depositCents,
            'deposit_paid_at'               => $s->deposit_paid_at?->format('M j, Y'),
            'deposit_label'                 => $this->formatMoney($depositCents),
            'balance_cents'                 => $balanceCents,
            'balance_paid_at'               => $s->balance_paid_at?->format('M j, Y'),
            'balance_label'                 => $this->formatMoney($balanceCents),
            // What should be charged (for display before payment)
            'expected_deposit_cents'        => $expectedDeposit,
            'expected_deposit_label'        => $this->formatMoney($expectedDeposit),
            'expected_balance_cents'        => $expectedBalance,
            'expected_balance_label'        => $this->formatMoney($expectedBalance),
            // Refund tracking
            'total_refunded_cents'          => $s->total_refunded_cents ?? 0,
            'total_refunded_label'          => $this->formatMoney($s->total_refunded_cents ?? 0),
            // ── Lifecycle ──────────────────────────────────────────────────
            'status'                        => $status,
            'payment_status'                => $paymentStatus?->value ?? 'unpaid',
            'payment_status_label'          => $paymentStatus?->label() ?? 'Deposit Due',
            'payment_status_variant'        => $paymentStatus?->badgeVariant() ?? 'gold',
            'can_pay_deposit'               => $paymentStatus === ServiceSessionPaymentStatus::Unpaid
                                               && $status === ServiceSessionStatus::Scheduled->value,
            'can_pay_balance'               => $paymentStatus === ServiceSessionPaymentStatus::DepositPaid
                                               && $status === ServiceSessionStatus::Scheduled->value,
            'can_request_refund'            => $paymentStatus?->depositCharged() && !$myRefundReq,
            'has_refund_request'            => (bool) $myRefundReq,
            'refund_request_status'         => $myRefundReq
                ? ($myRefundReq->status instanceof \App\Enums\SessionRefundRequestStatus
                    ? $myRefundReq->status->value
                    : (string) $myRefundReq->status)
                : null,
            'refund_request_id'             => $myRefundReq?->id,
            'can_escalate_refund'           => $myRefundReq && $myRefundReq->can_escalate,
            // ── Stripe ─────────────────────────────────────────────────────
            'practitioner_stripe_connected' => (bool) ($practitioner?->stripe_connected ?? false),
        ];
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function statsForPractitioner(User $user): array
    {
        $now        = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd   = $now->copy()->endOfMonth();

        $activeListings = Service::where('practitioner_id', $user->id)
            ->where('status', ServiceStatus::Active->value)->count();

        $pendingRequests = ServiceRequest::where('practitioner_id', $user->id)
            ->where('status', 'new')->count();

        $sessionsThisMonth = ServiceSession::where('practitioner_id', $user->id)
            ->whereBetween('scheduled_at', [$monthStart, $monthEnd])->count();

        // Revenue = sum of deposit + balance charges for sessions completed this month
        $depositRevenue = ServiceSession::where('practitioner_id', $user->id)
            ->where('status', ServiceSessionStatus::Completed->value)
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->sum('deposit_cents');

        $balanceRevenue = ServiceSession::where('practitioner_id', $user->id)
            ->where('status', ServiceSessionStatus::Completed->value)
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->sum('balance_cents');

        $legacyRevenue = ServiceSession::where('practitioner_id', $user->id)
            ->where('status', ServiceSessionStatus::Completed->value)
            ->whereNull('payment_status')  // legacy sessions without new payment tracking
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->sum('amount_cents');

        $revenueThisMonth = $depositRevenue + $balanceRevenue + $legacyRevenue;
        $totalSessions    = ServiceSession::where('practitioner_id', $user->id)->count();

        // Pending refund requests for this provider
        $pendingRefunds = SessionRefundRequest::where('provider_id', $user->id)
            ->where('status', 'pending_review')->count();

        return [
            'active_listings'   => $activeListings,
            'pending_requests'  => $pendingRequests,
            'sessions'          => $sessionsThisMonth,
            'total_sessions'    => $totalSessions,
            'revenue_label'     => '$' . number_format($revenueThisMonth / 100, 0),
            'pending_refunds'   => $pendingRefunds,
        ];
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function create(User $practitioner, array $data): Service
    {
        $tierVal = $practitioner->tier instanceof UserTier ? $practitioner->tier->value : (string) $practitioner->tier;

        if ($tierVal !== 'practice' || !$practitioner->services_mode) {
            throw new RuntimeException('Services Mode requires the Practice tier.');
        }

        $service = Service::create([
            'id'                 => 'ps_' . Str::lower(Str::random(12)),
            'practitioner_id'    => $practitioner->id,
            'title'              => $data['title'],
            'description'        => $data['description'] ?? null,
            'category'           => $data['category'] ?? null,
            'price_cents'        => $data['price_cents'] ?? 0,
            'price_type'         => $data['price_type'] ?? 'inquiry',
            'duration_min'       => $data['duration_min'] ?? null,
            'format'             => $data['format'] ?? null,
            'availability'       => $data['availability'] ?? 'open',
            'availability_label' => $data['availability_label'] ?? null,
            'is_public'          => $data['is_public'] ?? true,
            'status'                     => $data['status'] ?? 'active',
            // Rev 4 — payment terms
            'default_payment_structure'  => $data['default_payment_structure'] ?? 'split',
            'default_upfront_percentage' => isset($data['default_upfront_percentage']) ? (int) $data['default_upfront_percentage'] : 30,
            'default_terms_note'         => $data['default_terms_note'] ?? null,
            'allow_completion_only'      => $data['allow_completion_only'] ?? false,
            'created_at'                 => now(),
        ]);

        $this->activity->log(
            $practitioner->id, 'provider', 'referral', ActivitySeverity::Info,
            'service_created', "Service created: {$service->title}",
            ucfirst(str_replace('_', ' ', $service->category ?? '')),
            'service', $service->id, null, 'log', $practitioner->id
        );

        return $service;
    }

    public function update(Service $service, array $data): Service
    {
        $allowed = ['title','description','category','price_cents','price_type','duration_min','format','availability','availability_label','status','is_public','default_payment_structure','default_upfront_percentage','default_terms_note','allow_completion_only'];
        $service->update(array_intersect_key($data, array_flip($allowed)));
        $actorId = request()->user()?->id ?? $service->practitioner_id;
        $this->activity->log($actorId, 'provider', 'services', ActivitySeverity::Info, 'service_updated', "Service updated: {$service->title}", 'Service listing details updated.', 'service', $service->id, null, 'log', $actorId);
        return $service->fresh();
    }

    public function activate(Service $service): Service { $service->update(['status' => ServiceStatus::Active->value]); return $service->fresh(); }
    public function deactivate(Service $service): Service { $service->update(['status' => ServiceStatus::Inactive->value]); return $service->fresh(); }

    public function archive(Service $service): Service
    {
        $service->update(['status' => ServiceStatus::Archived->value]);
        $actorId = request()->user()?->id ?? $service->practitioner_id;
        $this->activity->log($actorId, 'provider', 'services', ActivitySeverity::Info, 'service_archived', "Service archived: {$service->title}", 'Service removed from public listings.', 'service', $service->id, null, 'log', $actorId);
        return $service->fresh();
    }

    // ── Requests ──────────────────────────────────────────────────────────────

    public function submitRequest(Service $service, User $requester, array $data): ServiceRequest
    {
        $svcStatus = $service->status instanceof ServiceStatus ? $service->status->value : (string) $service->status;

        if ($svcStatus !== 'active') throw new RuntimeException('This service is not currently available.');
        if ($service->practitioner_id === $requester->id) throw new RuntimeException('Cannot request your own service.');

        // Rev 4: guard full_on_completion if provider doesn't allow it
        if (($data['proposed_payment_structure'] ?? null) === 'full_on_completion'
            && !$service->allow_completion_only) {
            throw new RuntimeException('This service does not accept pay-after-session terms.');
        }

        // Rev 4: resolve proposed terms (default to service defaults if client accepted them)
        $proposedStructure = $data['proposed_payment_structure'] ?? $service->default_payment_structure?->value ?? 'split';
        $proposedPct       = isset($data['proposed_upfront_percentage']) ? (int) $data['proposed_upfront_percentage'] : ($service->default_upfront_percentage ?? 30);
        $termsSource       = $data['terms_source'] ?? 'provider_default';

        $req = ServiceRequest::create([
            'id'                          => 'sr_' . Str::lower(Str::random(12)),
            'service_id'                  => $service->id,
            'inquirer_id'                 => $requester->id,
            'practitioner_id'             => $service->practitioner_id,
            'message'                     => $data['message'] ?? null,
            'status'                      => 'new',
            // Rev 4 — proposed payment terms
            'proposed_payment_structure'  => $proposedStructure,
            'proposed_upfront_percentage' => $proposedPct,
            'proposed_terms_note'         => $data['proposed_terms_note'] ?? $service->default_terms_note,
            'terms_source'                => $termsSource,
            'created_at'                  => now(),
        ]);

        $practitionerName = $service->practitioner?->display_name ?? 'the practitioner';
        $this->activity->log($requester->id, 'provider', 'services', ActivitySeverity::Info, 'service_request_sent', "You requested: {$service->title}", "Request sent to {$practitionerName}.", 'service_request', $req->id, $service->practitioner_id, 'log', $requester->id);
        $this->activity->log($service->practitioner_id, 'provider', 'services', ActivitySeverity::Info, 'service_request_received', "{$requester->display_name} requested your service: {$service->title}", $data['message'] ?? 'Tap to review.', 'service_request', $req->id, $requester->id, 'notification', $requester->id);

        event(new \App\Events\Service\ServiceRequestSubmitted($req, $requester));
        return $req;
    }

    /**
     * Provider accepts a service request.
     * - Optionally negotiates a different price via $data['negotiated_amount_cents']
     * - Creates the session record with payment_status = 'unpaid'
     * - Does NOT charge anything — client must separately click "Pay Deposit"
     */
    public function acceptRequest(ServiceRequest $req, array $data = []): ServiceRequest
    {
        $scheduledAt = null;
        if (!empty($data['session_date'])) {
            $time = $data['session_time'] ?? '10:00';
            $tz   = $data['timezone']     ?? 'America/New_York';
            try {
                $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $data['session_date'] . ' ' . $time, $tz)->setTimezone('UTC');
            } catch (\Exception) {
                $scheduledAt = null;
            }
        }

        $req->update(['status' => 'accepted', 'responded_at' => now()]);

        $this->bookSession($req, [
            'scheduled_at'                 => $scheduledAt,
            'timezone'                     => $data['timezone'] ?? 'America/New_York',
            'negotiated_amount_cents'       => isset($data['negotiated_amount_cents'])
                ? (int) $data['negotiated_amount_cents']
                : null,
            // Rev 4 — committed payment terms (provider may counter at accept time)
            'terms_countered'              => $data['terms_countered'] ?? false,
            'committed_payment_structure'  => $data['committed_payment_structure'] ?? null,
            'committed_upfront_percentage' => isset($data['committed_upfront_percentage']) ? (int) $data['committed_upfront_percentage'] : null,
            'committed_terms_note'         => $data['committed_terms_note'] ?? null,
        ]);

        $service = Service::find($req->service_id);

        $this->activity->log($req->practitioner_id, 'provider', 'services', ActivitySeverity::Info, 'service_request_accepted', "You accepted a request for: " . ($service?->title ?? 'your service'), "Session scheduled for " . ($req->inquirer?->display_name ?? 'the requester') . '. Awaiting deposit.', 'service_request', $req->id, $req->inquirer_id, 'log', $req->practitioner_id);
        $this->activity->log($req->inquirer_id, 'provider', 'services', ActivitySeverity::Info, 'service_request_accepted', 'Your service request was accepted', ($service?->title ?? '') . ' — pay your deposit to confirm the session.', 'service_request', $req->id, $req->practitioner_id, 'notification', $req->practitioner_id);

        event(new \App\Events\Service\ServiceRequestResponded($req, 'accepted'));
        return $req->fresh();
    }

    public function declineRequest(ServiceRequest $req, ?string $reason = null): ServiceRequest
    {
        $req->update(['status' => 'declined', 'responded_at' => now(), 'response_note' => $reason]);
        $this->activity->log($req->practitioner_id, 'provider', 'services', ActivitySeverity::Info, 'service_request_declined', 'You declined a service request', $reason ?? 'No reason provided.', 'service_request', $req->id, $req->inquirer_id, 'log', $req->practitioner_id);
        $this->activity->log($req->inquirer_id, 'provider', 'services', ActivitySeverity::Info, 'service_request_declined', 'Your service request was declined', $reason ?? 'No reason given.', 'service_request', $req->id, $req->practitioner_id, 'notification', $req->practitioner_id);
        event(new \App\Events\Service\ServiceRequestResponded($req, 'declined'));
        return $req->fresh();
    }

    // ── Sessions ──────────────────────────────────────────────────────────────

    public function bookSession(ServiceRequest $req, array $data): ServiceSession
    {
        $service        = Service::find($req->service_id);
        $listingPrice   = $service?->price_cents ?? 0;
        $negotiated     = isset($data['negotiated_amount_cents']) && $data['negotiated_amount_cents'] > 0
            ? (int) $data['negotiated_amount_cents']
            : null;
        $agreedAmount   = $negotiated ?? $listingPrice;

        // Rev 4 — compute committed payment terms
        // Priority: provider counter > request proposed > service default
        $termsSrc = 'provider_default';
        if ($data['terms_countered'] ?? false) {
            $structure  = $data['committed_payment_structure']  ?? $req->proposed_payment_structure?->value ?? 'split';
            $pct        = $data['committed_upfront_percentage'] ?? $req->proposed_upfront_percentage ?? 30;
            $termsNote  = $data['committed_terms_note']         ?? $req->proposed_terms_note;
            $termsSrc   = 'provider_countered';
        } else {
            $structure  = $req->proposed_payment_structure?->value ?? 'split';
            $pct        = $req->proposed_upfront_percentage ?? 30;
            $termsNote  = $req->proposed_terms_note;
            $termsSrc   = $req->terms_source ?? 'provider_default';
        }

        $upfrontCents = match ($structure) {
            'full_upfront'       => $agreedAmount,
            'full_on_completion' => 0,
            default              => (int) floor($agreedAmount * ($pct / 100)),
        };
        $completionCents = $agreedAmount - $upfrontCents;

        return ServiceSession::create([
            'id'                      => 'ss_' . Str::lower(Str::random(12)),
            'service_request_id'      => $req->id,
            'service_id'              => $req->service_id,
            'practitioner_id'         => $req->practitioner_id,
            'client_id'               => $req->inquirer_id,
            'scheduled_at'            => $data['session_at'] ?? $data['scheduled_at'] ?? null,
            'timezone'                => $data['timezone'] ?? 'America/New_York',
            'status'                  => 'scheduled',
            // ── Pricing columns ───────────────────────────────────────────
            'amount_cents'            => $agreedAmount,
            'original_amount_cents'   => $listingPrice,
            'negotiated_amount_cents' => $negotiated,
            // ── Legacy deposit/balance (filled on charge) ─────────────────
            'deposit_cents'           => 0,
            'balance_cents'           => 0,
            'total_refunded_cents'    => 0,
            'payment_status'          => ServiceSessionPaymentStatus::Unpaid->value,
            // ── Rev 4: committed payment terms ────────────────────────────
            'payment_structure'       => $structure,
            'upfront_percentage'      => $pct,
            'upfront_cents'           => $upfrontCents,
            'completion_cents'        => $completionCents,
            'terms_note'              => $termsNote ?? null,
            'terms_source'            => $termsSrc,
            'terms_agreed_at'         => now(),
            'created_at'              => now(),
        ]);
    }

    /**
     * Rev 4: Client pays the upfront portion (full_upfront = 100%, split = configured %, full_on_completion = 0%).
     * Validates session is in the right state, then delegates to PayoutService.
     */
    public function payUpfront(ServiceSession $session, User $client): \App\Models\PractitionerPayment
    {
        if ($session->client_id !== $client->id) {
            abort(403, 'Only the booking client can pay the upfront portion.');
        }

        $paymentStatus = ServiceSessionPaymentStatus::tryFrom(($session->payment_status instanceof \App\Enums\ServiceSessionPaymentStatus ? $session->payment_status->value : (string) $session->payment_status))
            ?? ServiceSessionPaymentStatus::Unpaid;

        if ($paymentStatus !== ServiceSessionPaymentStatus::Unpaid) {
            throw new RuntimeException(
                $paymentStatus === ServiceSessionPaymentStatus::DepositPaid
                    ? 'Upfront payment has already been made for this session.'
                    : 'Session is not in a state that allows upfront payment.'
            );
        }

        $structure = $session->payment_structure?->value ?? 'split';
        if ($structure === 'full_on_completion') {
            throw new RuntimeException('This session uses pay-after-session terms. No upfront payment required.');
        }

        if ($session->status !== ServiceSessionStatus::Scheduled && !($session->status instanceof ServiceSessionStatus && $session->status === ServiceSessionStatus::Scheduled)) {
            throw new RuntimeException('Session must be in Scheduled state to accept payment.');
        }

        $provider = User::findOrFail($session->practitioner_id);
        $payment  = $this->payouts->chargeSessionPortion($session, 'upfront');

        // Fire new Rev 4 event + legacy event for BC
        event(new \App\Events\Service\SessionUpfrontPaid(
            $session->fresh(), $client, $provider, $payment, $session->fresh()->upfront_cents ?? 0
        ));
        event(new \App\Events\Service\SessionDepositPaid(
            $session->fresh(), $client, $provider, $payment, $session->fresh()->deposit_cents ?? 0
        ));

        return $payment;
    }

    /** @deprecated Rev 4 — use payUpfront() */
    public function payDeposit(ServiceSession $session, User $client): \App\Models\PractitionerPayment
    {
        return $this->payUpfront($session, $client);
    }

    /**
     * Client confirms session happened and pays the 70% balance.
     * Marks session as Completed.
     */
    public function completeSession(ServiceSession $session, string $actorId): ServiceSession
    {
        if ($session->client_id !== $actorId) {
            abort(403, 'Only the client who booked this session can confirm completion.');
        }

        $sessionStatus = $session->status instanceof ServiceSessionStatus
            ? $session->status
            : ServiceSessionStatus::from((string) $session->status);

        if ($sessionStatus !== ServiceSessionStatus::Scheduled) {
            abort(422, 'Only scheduled sessions can be marked complete.');
        }

        $session->update([
            'status'       => ServiceSessionStatus::Completed->value,
            'completed_at' => now(),
        ]);

        $client       = User::find($session->client_id);
        $service      = Service::find($session->service_id);
        $practitioner = User::find($session->practitioner_id);

        if (!$client || !$practitioner) {
            return $session->fresh();
        }

        $paymentStatus = ServiceSessionPaymentStatus::tryFrom(($session->payment_status instanceof \App\Enums\ServiceSessionPaymentStatus ? $session->payment_status->value : (string) $session->payment_status))
            ?? ServiceSessionPaymentStatus::Unpaid;

        $structure = $session->payment_structure?->value ?? 'split';

        // ── Rev 4: branch on payment_structure ────────────────────────────────
        if ($structure === 'full_upfront' && $paymentStatus === ServiceSessionPaymentStatus::Paid) {
            // Already fully paid at booking — no charge needed at completion
        } elseif ($structure === 'full_on_completion' && $paymentStatus === ServiceSessionPaymentStatus::Unpaid) {
            // Full amount due now at completion
            try {
                $payment = $this->payouts->chargeSessionPortion($session->fresh(), 'completion');
                event(new \App\Events\Service\SessionCompletionPaid(
                    $session->fresh(), $client, $practitioner, $payment, $session->fresh()->completion_cents ?? 0
                ));
                event(new \App\Events\Service\SessionBalancePaid(
                    $session->fresh(), $client, $practitioner, $payment, $session->fresh()->balance_cents ?? 0
                ));
            } catch (\Throwable $e) {
                $this->activity->log(
                    $practitioner->id, 'provider', 'payment', ActivitySeverity::Critical,
                    'session_completion_failed', 'Session completion charge failed',
                    $e->getMessage(),
                    'service_session', $session->id, $client->id, 'notification', $client->id
                );
            }
        } elseif ($paymentStatus === ServiceSessionPaymentStatus::DepositPaid) {
            // split: upfront already paid, now charge completion
            try {
                $payment = $this->payouts->chargeSessionPortion($session->fresh(), 'completion');
                event(new \App\Events\Service\SessionCompletionPaid(
                    $session->fresh(), $client, $practitioner, $payment, $session->fresh()->completion_cents ?? 0
                ));
                event(new \App\Events\Service\SessionBalancePaid(
                    $session->fresh(), $client, $practitioner, $payment, $session->fresh()->balance_cents ?? 0
                ));
            } catch (\Throwable $e) {
                $this->activity->log(
                    $practitioner->id, 'provider', 'payment', ActivitySeverity::Critical,
                    'session_completion_failed', 'Session completion charge failed',
                    $e->getMessage(),
                    'service_session', $session->id, $client->id, 'notification', $client->id
                );
            }
        } elseif ($paymentStatus === ServiceSessionPaymentStatus::Unpaid) {
            // ── Legacy path (single charge for old sessions) ─────────────────
            if ($session->amount_cents > 0) {
                $clientPm  = \App\Models\PractitionerPaymentMethod::where('practitioner_id', $client->id)->where('is_default', 1)->first();
                $payment   = PractitionerPayment::create([
                    'id'                   => 'pp_' . Str::lower(Str::random(12)),
                    'session_id'           => $session->id,
                    'practitioner_id'      => $practitioner->id,
                    'payment_method_id'    => $clientPm?->id,
                    'kind'                 => PractitionerPaymentKind::ServiceSession->value,
                    'amount_cents'         => $session->amount_cents,
                    'currency'             => 'USD',
                    'status'               => PractitionerPaymentStatus::Pending->value,
                    'payment_method_label' => $clientPm?->label ?? ($client->display_name . ' payment method'),
                    'stripe_charge_id'     => null,
                    'paid_at'              => null,
                ]);
                try {
                    $this->payouts->releaseServiceSessionPayout($payment, $practitioner, $client);
                    $refreshed = $payment->fresh();
                    if (!empty($refreshed->stripe_payment_intent_id)) {
                        $session->update(['stripe_payment_intent_id' => $refreshed->stripe_payment_intent_id]);
                    }
                } catch (\Throwable) { /* logged inside PayoutService */ }
            }
        }
        // If already 'paid' (shouldn't normally happen), no charge needed.

        $payoutNote = ($practitioner->stripe_connected && $practitioner->stripe_account_id)
            ? 'Payment initiated to provider Stripe account.'
            : 'Payment pending — provider has not connected Stripe yet.';

        $svcTitle = $service?->title ?? 'Session';
        $this->activity->log($practitioner->id, 'provider', 'services', ActivitySeverity::Info, 'session_completed', ($client->display_name ?? 'A client') . ' confirmed your session complete', $svcTitle . '. ' . $payoutNote, 'service_session', $session->id, $client->id, 'notification', $client->id);
        $this->activity->log($client->id, 'provider', 'services', ActivitySeverity::Info, 'session_payment_sent', 'Session confirmed and payment complete', $this->formatMoney($session->agreed_amount_cents) . ' for ' . $svcTitle . ' with ' . ($practitioner->display_name ?? 'provider') . '.', 'service_session', $session->id, $practitioner->id, 'log', $client->id);

        event(new \App\Events\Service\SessionCompleted($session->fresh(), $client, $practitioner, $session->agreed_amount_cents));

        return $session->fresh();
    }

    public function cancelSession(ServiceSession $session, array $data): ServiceSession
    {
        $actor        = User::find(request()->user()?->id);
        $service      = $session->service;
        $otherPartyId = $actor?->id === $session->practitioner_id ? $session->client_id : $session->practitioner_id;

        $session->update([
            'status'        => ServiceSessionStatus::Cancelled->value,
            'cancel_reason' => $data['reason'] ?? null,
        ]);

        if ($actor) {
            $this->activity->log($actor->id, 'provider', 'services', ActivitySeverity::Info, 'session_cancelled', 'You cancelled a session', ($service?->title ?? 'Session') . ' cancelled. ' . ($data['reason'] ?? ''), 'service_session', $session->id, $otherPartyId, 'log', $actor->id);
        }
        if ($otherPartyId) {
            $this->activity->log($otherPartyId, 'provider', 'services', ActivitySeverity::Warning, 'session_cancelled_by_other', 'Your session was cancelled', ($actor?->display_name ?? 'The other party') . ' cancelled the session for ' . ($service?->title ?? 'your service') . '.', 'service_session', $session->id, $actor?->id, 'notification', $actor?->id);
        }

        if ($actor) {
            event(new \App\Events\Service\SessionCancelled($session->fresh(), $actor, $data['reason'] ?? ''));
        }

        return $session->fresh();
    }

    public function saveSessionNotes(ServiceSession $session, array $data): ServiceSession
    {
        $session->update([
            'session_summary'         => $data['summary'] ?? null,
            'session_action_items'    => $data['action_items'] ?? null,
            'share_notes_with_client' => $data['share_with_supervisee'] ?? false,
        ]);
        $actorId = request()->user()?->id ?? $session->practitioner_id;
        $this->activity->log($actorId, 'provider', 'services', ActivitySeverity::Info, 'session_notes_saved', 'Session notes saved', "Notes updated for: " . ($session->service?->title ?? 'session') . '.', 'service_session', $session->id, null, 'log', $actorId);
        return $session->fresh();
    }

    // ── Queries (paginated) ───────────────────────────────────────────────────

    /**
     * Sessions where I am the PROVIDER — paginated, 10 per page.
     */
    public function getSessionsForPractitioner(string $practitionerId, int $perPage = 10): LengthAwarePaginator
    {
        return ServiceSession::where('practitioner_id', $practitionerId)
            ->with(['service', 'client', 'activeRefundRequest'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Sessions where I am the CLIENT (payer) — paginated, 10 per page.
     */
    public function getSessionsAsClient(string $clientId, int $perPage = 10): LengthAwarePaginator
    {
        return ServiceSession::where('client_id', $clientId)
            ->with(['service', 'practitioner'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Public service explore: search active, public services NOT owned by the current user.
     * Returns 12 per page for infinite scroll.
     */
    public function getForExplore(array $filters, string $excludePractitionerId, int $perPage = 12): LengthAwarePaginator
    {
        $query = Service::where('status', ServiceStatus::Active->value)
            ->where('is_public', 1)
            ->where('practitioner_id', '!=', $excludePractitionerId)
            ->with('practitioner');

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (!empty($filters['format'])) {
            $query->where('format', $filters['format']);
        }
        if (!empty($filters['availability'])) {
            $query->where('availability', $filters['availability']);
        }
        if (!empty($filters['max_price_cents'])) {
            $query->where('price_cents', '<=', (int) $filters['max_price_cents']);
        }
        if (!empty($filters['min_price_cents'])) {
            $query->where('price_cents', '>=', (int) $filters['min_price_cents']);
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'price_asc'  => $query->orderBy('price_cents', 'asc'),
            'price_desc' => $query->orderBy('price_cents', 'desc'),
            'oldest'     => $query->orderBy('created_at', 'asc'),
            default      => $query->orderBy('created_at', 'desc'), // newest
        };

        return $query->paginate($perPage);
    }

    public function getForPractitioner(string $practitionerId, array $filters = []): Collection
    {
        $query = Service::where('practitioner_id', $practitionerId)
            ->where('status', '!=', ServiceStatus::Archived->value);
        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) { $sub->where('title', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%"); });
        }
        if (!empty($filters['category'])) $query->where('category', $filters['category']);
        if (!empty($filters['status']))   $query->where('status', $filters['status']);
        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getRequestsForPractitioner(string $practitionerId): Collection
    {
        return ServiceRequest::where('practitioner_id', $practitionerId)
            ->with(['service', 'inquirer'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRequestsSentByPractitioner(string $inquirerId): Collection
    {
        return ServiceRequest::where('inquirer_id', $inquirerId)
            ->with(['service', 'practitioner'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function shapeOutgoingRequest(ServiceRequest $r): array
    {
        $practitioner = $r->practitioner;
        $service      = $r->service;
        $status       = $r->status instanceof \App\Enums\ServiceRequestStatus ? $r->status->value : (string) ($r->status ?? 'new');
        return ['id' => $r->id, 'service_id' => $r->service_id, 'practitioner_id' => $r->practitioner_id, 'provider_name' => $practitioner?->display_name ?? 'Unknown', 'provider_slug' => $practitioner?->slug ?? '', 'provider_avatar' => $practitioner?->avatar_initials ?? '', 'provider_detail' => $practitioner?->credentials ?? '', 'service_title' => $service?->title ?? 'Appointment Request', 'request_type' => ucfirst(str_replace('_', ' ', $service?->category ?? '')), 'sent_date_label' => $r->created_at?->format('M j, Y') ?? '', 'time_label' => $r->created_at?->diffForHumans() ?? '', 'status' => $status, 'message' => $r->message ?? '', 'response_note' => $r->response_note ?? '', 'responded_at' => $r->responded_at?->format('M j, Y') ?? ''];
    }

    public function withdrawRequest(ServiceRequest $r, string $inquirerId): void
    {
        if ($r->inquirer_id !== $inquirerId) abort(403, 'You cannot withdraw this request.');
        if ($r->status instanceof \App\Enums\ServiceRequestStatus && $r->status !== \App\Enums\ServiceRequestStatus::New) abort(422, 'Only pending requests can be withdrawn.');
        $r->update(['status' => \App\Enums\ServiceRequestStatus::Withdrawn->value]);
        $serviceTitle = $r->service?->title ?? 'a service';
        $this->activity->log($inquirerId, 'provider', 'services', ActivitySeverity::Info, 'service_request_withdrawn', 'You withdrew a service request', "Request for {$serviceTitle} withdrawn.", 'service_request', $r->id, $r->practitioner_id, 'log', $inquirerId);
    }

    /** @deprecated Use getForExplore() instead */
    public function findProviders(array $filters = []): Collection
    {
        $query = Service::where('status', ServiceStatus::Active->value)->where('is_public', 1);
        if (!empty($filters['category'])) $query->where('category', $filters['category']);
        if (!empty($filters['max_price_cents'])) $query->where('price_cents', '<=', $filters['max_price_cents']);
        return $query->limit($filters['limit'] ?? 50)->get();
    }
}
