<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Enums\ServiceStatus;
use App\Enums\ServiceSessionStatus;
use App\Enums\UserTier;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceSession;
use App\Models\User;
use App\Models\PractitionerPayment;
use App\Enums\PractitionerPaymentKind;
use App\Enums\PractitionerPaymentStatus;
use Carbon\Carbon;
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

    // ── Helpers ───────────────────────────────────────────────────────────

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

    // ── Shaping for Vue ───────────────────────────────────────────────────

    public function shapeForListing(Service $s): array
    {
        $priceData = $this->formatPrice($s);
        $category  = $s->category ?? 'other';
        $status    = $s->status instanceof ServiceStatus ? $s->status->value : (string) ($s->status ?? 'active');

        $meta = [];
        if ($s->duration_min) {
            $meta[] = ['icon' => 'clock',   'text' => $s->duration_min . ' min'];
        }
        if ($s->format) {
            $meta[] = ['icon' => 'monitor', 'text' => match ($s->format) {
                'telehealth' => 'Virtual', 'in_person' => 'In-person', 'both' => 'Virtual & In-person', default => $s->format,
            }];
        }
        if ($s->availability) {
            $meta[] = ['icon' => 'calendar', 'text' => $s->availability_label ?: ucfirst($s->availability)];
        }

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
            'is_public'    => (bool) $s->is_public,
            'category'     => $category,
            'metrics'      => [
                ['val' => (string) $sessionCount,    'label' => 'Sessions'],
                ['val' => (string) $completedCount,  'label' => 'Completed'],
                ['val' => (string) $pendingRequests, 'label' => 'Requests'],
            ],
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

    public function shapeSession(ServiceSession $s): array
    {
        $client  = $s->client;
        $service = $s->service;
        $status  = $s->status instanceof ServiceSessionStatus ? $s->status->value : (string) ($s->status ?? 'scheduled');
        $tz      = $s->timezone ?? 'America/New_York';

        return [
            'id'                        => $s->id,
            'service_id'                => $s->service_id,
            'service_request_id'        => $s->service_request_id,
            'provider_id'               => $client?->id ?? '',
            'provider_name'             => $client?->display_name ?? 'Unknown',
            'provider_slug'             => $client?->slug ?? '',
            'provider_avatar'           => $client?->avatar_initials ?? '',
            'provider_credentials'      => $client?->credentials ?? '',
            'service_title'             => $service?->title ?? '',
            'datetime_label'            => $s->scheduled_at
                ? $s->scheduled_at->setTimezone($tz)->format('M j, Y g:i A T')
                : '—',
            'timezone'                  => $tz,
            'duration_label'            => $service?->duration_min ? $service->duration_min . ' min' : '—',
            'amount'                    => $s->amount_cents ? '$' . number_format($s->amount_cents / 100, 0) : '—',
            'amount_cents'              => $s->amount_cents ?? 0,
            'practitioner_stripe_connected' => (bool) ($s->practitioner?->stripe_connected ?? false),
            'practitioner_stripe_account'   => $s->practitioner?->stripe_account_id ?? null,
            'status'                    => $status,
            'summary'                   => $s->session_summary ?? '',
            'action_items'              => $s->session_action_items ?? '',
            'share_notes_with_client'   => (bool) ($s->share_notes_with_client ?? false),
        ];
    }

    // ── Stats ─────────────────────────────────────────────────────────────

    public function statsForPractitioner(User $user): array
    {
        $now        = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();

        $activeListings = Service::where('practitioner_id', $user->id)
            ->where('status', ServiceStatus::Active->value)
            ->count();

        $pendingRequests = ServiceRequest::where('practitioner_id', $user->id)
            ->where('status', 'new')
            ->count();

        $monthEnd = $now->copy()->endOfMonth();

        // All sessions scheduled in this calendar month (past + upcoming)
        $sessionsThisMonth = ServiceSession::where('practitioner_id', $user->id)
            ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
            ->count();

        $revenueThisMonth = ServiceSession::where('practitioner_id', $user->id)
            ->where('status', ServiceSessionStatus::Completed->value)
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->sum('amount_cents');

        $totalSessions = ServiceSession::where('practitioner_id', $user->id)->count();

        return [
            'active_listings'  => $activeListings,
            'pending_requests' => $pendingRequests,
            'sessions'         => $sessionsThisMonth,
            'total_sessions'   => $totalSessions,
            'revenue_label'    => '$' . number_format($revenueThisMonth / 100, 0),
        ];
    }

    // ── CRUD ─────────────────────────────────────────────────────────────

    public function create(User $practitioner, array $data): Service
    {
        $tierVal = $practitioner->tier instanceof UserTier
            ? $practitioner->tier->value
            : (string) $practitioner->tier;

        if ($tierVal !== 'practice' || !$practitioner->services_mode) {
            throw new RuntimeException('Services Mode requires the Practice tier.');
        }

        $status = $data['status'] ?? 'active';

        return Service::create([
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
            'status'             => $status,
            'created_at'         => now(),
        ]);
    }

    public function update(Service $service, array $data): Service
    {
        $allowed = [
            'title', 'description', 'category', 'price_cents', 'price_type',
            'duration_min', 'format', 'availability', 'availability_label',
            'status', 'is_public',
        ];
        $service->update(array_intersect_key($data, array_flip($allowed)));
        return $service->fresh();
    }

    public function activate(Service $service): Service
    {
        $service->update(['status' => ServiceStatus::Active->value]);
        return $service->fresh();
    }

    public function deactivate(Service $service): Service
    {
        $service->update(['status' => ServiceStatus::Inactive->value]);
        return $service->fresh();
    }

    public function archive(Service $service): Service
    {
        $service->update(['status' => ServiceStatus::Archived->value]);
        return $service->fresh();
    }

    // ── Requests ──────────────────────────────────────────────────────────

    public function submitRequest(Service $service, User $requester, array $data): ServiceRequest
    {
        $svcStatus = $service->status instanceof ServiceStatus
            ? $service->status->value
            : (string) $service->status;

        if ($svcStatus !== 'active') {
            throw new RuntimeException('This service is not currently available.');
        }
        if ($service->practitioner_id === $requester->id) {
            throw new RuntimeException('Cannot request your own service.');
        }

        $req = ServiceRequest::create([
            'id'              => 'sr_' . Str::lower(Str::random(12)),
            'service_id'      => $service->id,
            'inquirer_id'     => $requester->id,
            'practitioner_id' => $service->practitioner_id,
            'message'         => $data['message'] ?? null,
            'status'          => 'new',
            'created_at'      => now(),
        ]);

        $this->activity->log(
            $service->practitioner_id, 'provider', 'referral', ActivitySeverity::Info,
            'service_request_received',
            "{$requester->display_name} requested your service: {$service->title}",
            $data['message'] ?? 'Tap to review.',
            'service_request', $req->id, $requester->id,
            'notification', $requester->id
        );

        return $req;
    }

    public function acceptRequest(ServiceRequest $req, array $data = []): ServiceRequest
    {
        // Build scheduled_at from separate date + time + timezone fields
        $scheduledAt = null;
        if (!empty($data['session_date'])) {
            $time     = $data['session_time'] ?? '10:00';
            $tz       = $data['timezone']     ?? 'America/New_York';
            try {
                $scheduledAt = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    $data['session_date'] . ' ' . $time,
                    $tz
                )->setTimezone('UTC');
            } catch (\Exception $e) {
                $scheduledAt = null;
            }
        }

        $req->update(['status' => 'accepted', 'responded_at' => now()]);

        // Create the session record with the chosen date/time
        $this->bookSession($req, [
            'scheduled_at' => $scheduledAt,
            'timezone'     => $data['timezone'] ?? 'America/New_York',
        ]);

        $service = Service::find($req->service_id);

        $this->activity->log(
            $req->inquirer_id, 'provider', 'referral', ActivitySeverity::Info,
            'service_request_accepted',
            'Your service request was accepted',
            $service?->title ?? '',
            'service_request', $req->id, $req->practitioner_id,
            'notification', $req->practitioner_id
        );

        return $req->fresh();
    }

    public function declineRequest(ServiceRequest $req, ?string $reason = null): ServiceRequest
    {
        $req->update([
            'status'        => 'declined',
            'responded_at'  => now(),
            'response_note' => $reason,
        ]);

        $this->activity->log(
            $req->inquirer_id, 'provider', 'referral', ActivitySeverity::Info,
            'service_request_declined',
            'Your service request was declined',
            $reason ?? 'No reason given.',
            'service_request', $req->id, $req->practitioner_id,
            'notification', $req->practitioner_id
        );

        return $req->fresh();
    }

    // ── Sessions ──────────────────────────────────────────────────────────

    public function bookSession(ServiceRequest $req, array $data): ServiceSession
    {
        return ServiceSession::create([
            'id'                 => 'ss_' . Str::lower(Str::random(12)),
            'service_request_id' => $req->id,
            'service_id'         => $req->service_id,
            'practitioner_id'    => $req->practitioner_id,
            'client_id'          => $req->inquirer_id,
            'scheduled_at'       => $data['session_at'] ?? $data['scheduled_at'] ?? null,
            'timezone'           => $data['timezone'] ?? 'America/New_York',
            'status'             => 'scheduled',
            'amount_cents'       => $req->service?->price_cents ?? 0,
            'created_at'         => now(),
        ]);
    }

    public function cancelSession(ServiceSession $session, array $data): ServiceSession
    {
        $session->update([
            'status'        => ServiceSessionStatus::Cancelled->value,
            'cancel_reason' => $data['reason'] ?? null,
        ]);
        return $session->fresh();
    }

    public function saveSessionNotes(ServiceSession $session, array $data): ServiceSession
    {
        $session->update([
            'session_summary'         => $data['summary'] ?? null,
            'session_action_items'    => $data['action_items'] ?? null,
            'share_notes_with_client' => $data['share_with_supervisee'] ?? false,
        ]);
        return $session->fresh();
    }

    /**
     * Mark a session completed (practitioner-only).
     * Records a PractitionerPayment row for the inquirer (client) side.
     * Stub: actual Stripe Connect transfer is triggered via PayoutService
     * once Connect is fully configured; for now we record the row as pending.
     */
    /**
     * Called by the CLIENT (inquirer) to confirm the session happened and release payment.
     * Charges the client's default payment method, transfers to practitioner's Stripe Connect account.
     */
    public function completeSession(ServiceSession $session, string $actorId): ServiceSession
    {
        if ($session->client_id !== $actorId) {
            abort(403, 'Only the client who booked this session can confirm completion.');
        }

        if ($session->status instanceof ServiceSessionStatus && $session->status !== ServiceSessionStatus::Scheduled) {
            abort(422, 'Only scheduled sessions can be marked complete.');
        }

        $session->update([
            'status'       => ServiceSessionStatus::Completed->value,
            'completed_at' => now(),
        ]);

        $client       = User::find($session->client_id);
        $service      = Service::find($session->service_id);
        $practitioner = User::find($session->practitioner_id);

        // Charge client -> transfer to practitioner via Stripe Connect
        if ($session->amount_cents > 0 && $practitioner) {
            $clientPm = \App\Models\PractitionerPaymentMethod::where('practitioner_id', $session->client_id)
                ->where('is_default', 1)->first();

            $payment = PractitionerPayment::create([
                'id'                   => 'pp_' . Str::lower(Str::random(12)),
                'practitioner_id'      => $session->practitioner_id,
                'payment_method_id'    => $clientPm?->id,
                'kind'                 => PractitionerPaymentKind::ServiceSession->value,
                'amount_cents'         => $session->amount_cents,
                'currency'             => 'USD',
                'status'               => PractitionerPaymentStatus::Pending->value,
                'payment_method_label' => $clientPm?->label ?? (($client?->display_name ?? 'Client') . ' payment method'),
                'stripe_charge_id'     => null,
                'paid_at'              => null,
            ]);

            try {
                $this->payouts->releaseServiceSessionPayout($payment, $practitioner);
            } catch (\Throwable) {
                // Failure logged inside PayoutService; session still completes
            }
        }

        $payoutStatus = ($practitioner?->stripe_connected && $practitioner?->stripe_account_id)
            ? 'Payment initiated to provider Stripe account.'
            : 'Payment pending - provider has not connected Stripe account yet.';

        // Notify practitioner
        $this->activity->log(
            $session->practitioner_id, 'provider', 'payment', ActivitySeverity::Info,
            'session_completed',
            ($client?->display_name ?? 'A client') . ' confirmed your session complete',
            ($service?->title ?? 'Session') . '. ' . $payoutStatus,
            'service_session', $session->id, $session->client_id,
            'notification', $session->practitioner_id
        );

        // Notify client
        $this->activity->log(
            $session->client_id, 'provider', 'payment', ActivitySeverity::Info,
            'session_payment_sent',
            'Session confirmed and payment sent',
            '$' . number_format($session->amount_cents / 100, 2) . ' for ' . ($service?->title ?? 'session') . ' with ' . ($practitioner?->display_name ?? 'provider') . '.',
            'service_session', $session->id, $session->practitioner_id,
            'notification', $session->client_id
        );

        return $session->fresh();
    }

    public function getSessionsAsClient(string $clientId): Collection
    {
        return ServiceSession::where('client_id', $clientId)
            ->with(['service', 'practitioner'])
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }

    public function shapeClientSession(ServiceSession $s): array
    {
        $practitioner = $s->practitioner;
        $service      = $s->service;
        $status       = $s->status instanceof ServiceSessionStatus
            ? $s->status->value
            : (string) ($s->status ?? 'scheduled');
        $tz = $s->timezone ?? 'America/New_York';

        return [
            'id'                            => $s->id,
            'service_id'                    => $s->service_id,
            'service_request_id'            => $s->service_request_id,
            'practitioner_id'               => $s->practitioner_id,
            'practitioner_name'             => $practitioner?->display_name ?? 'Unknown',
            'practitioner_slug'             => $practitioner?->slug ?? '',
            'practitioner_avatar'           => $practitioner?->avatar_initials ?? '',
            'practitioner_detail'           => $practitioner?->credentials ?? '',
            'service_title'                 => $service?->title ?? 'Session',
            'request_type'                  => ucfirst(str_replace('_', ' ', $service?->category ?? '')),
            'datetime_label'                => $s->scheduled_at?->setTimezone($tz)->format('M j, Y g:i A T') ?? 'TBD',
            'duration_label'                => isset($service->duration_min) ? $service->duration_min . ' min' : 'TBD',
            'amount'                        => $s->amount_cents ? '$' . number_format($s->amount_cents / 100, 2) : 'Free',
            'amount_cents'                  => $s->amount_cents ?? 0,
            'status'                        => $status,
            'practitioner_stripe_connected' => (bool) ($practitioner?->stripe_connected ?? false),
        ];
    }

    public function getForPractitioner(string $practitionerId, array $filters = []): Collection
    {
        $query = Service::where('practitioner_id', $practitionerId)
            ->where('status', '!=', ServiceStatus::Archived->value);

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
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

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
        $status       = $r->status instanceof \App\Enums\ServiceRequestStatus
            ? $r->status->value
            : (string) ($r->status ?? 'new');

        return [
            'id'               => $r->id,
            'service_id'       => $r->service_id,
            'practitioner_id'  => $r->practitioner_id,
            'provider_name'    => $practitioner?->display_name ?? 'Unknown',
            'provider_slug'    => $practitioner?->slug ?? '',
            'provider_avatar'  => $practitioner?->avatar_initials ?? '',
            'provider_detail'  => $practitioner?->credentials ?? '',
            'service_title'    => $service?->title ?? 'Appointment Request',
            'request_type'     => ucfirst(str_replace('_', ' ', $service?->category ?? '')),
            'sent_date_label'  => $r->created_at?->format('M j, Y') ?? '',
            'time_label'       => $r->created_at?->diffForHumans() ?? '',
            'status'           => $status,
            'message'          => $r->message ?? '',
            'response_note'    => $r->response_note ?? '',
            'responded_at'     => $r->responded_at?->format('M j, Y') ?? '',
        ];
    }

    public function withdrawRequest(ServiceRequest $r, string $inquirerId): void
    {
        if ($r->inquirer_id !== $inquirerId) {
            abort(403, 'You cannot withdraw this request.');
        }
        if ($r->status instanceof \App\Enums\ServiceRequestStatus && $r->status !== \App\Enums\ServiceRequestStatus::New) {
            abort(422, 'Only pending requests can be withdrawn.');
        }
        $r->update(['status' => \App\Enums\ServiceRequestStatus::Withdrawn->value]);
    }

    public function getSessionsForPractitioner(string $practitionerId): Collection
    {
        return ServiceSession::where('practitioner_id', $practitionerId)
            ->with(['service', 'client'])
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }

    public function findProviders(array $filters = []): Collection
    {
        $query = Service::where('status', ServiceStatus::Active->value)->where('is_public', 1);
        if (!empty($filters['category'])) $query->where('category', $filters['category']);
        if (!empty($filters['max_price_cents'])) $query->where('price_cents', '<=', $filters['max_price_cents']);
        return $query->limit($filters['limit'] ?? 50)->get();
    }
}
