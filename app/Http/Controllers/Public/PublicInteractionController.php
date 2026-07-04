<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\ActivitySeverity;
use App\Events\Business\EngagementRequested;
use App\Events\Network\ConnectionAccepted;
use App\Events\Network\ConnectionRequestSent;
use App\Events\Service\ServiceRequestSubmitted;
use App\Http\Controllers\Controller;
use App\Models\NetworkConnection;
use App\Models\NetworkRequest;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\NetworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicInteractionController extends Controller
{
    public function __construct(
        private NetworkService  $network,
        private ActivityService $activity,
    ) {}

    /**
     * POST /public/profiles/{user}/endorse
     */
    public function endorse(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();

        abort_if($viewer->id === $user->id, 403, 'Cannot endorse yourself.');

        $data = $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'headline' => 'nullable|string|max:80',
            'body'     => 'required|string|max:600',
            'context'  => 'nullable|string|max:100',
        ]);

        $row      = $user->meta()->where('meta_key', 'peer_reviews')->first();
        $existing = $row ? (json_decode((string) $row->meta_value, true) ?? []) : [];

        $existing[] = [
            'name'       => $viewer->display_name,
            'stars'      => (int) $data['rating'],
            'quote'      => $data['body'],
            'headline'   => $data['headline'] ?? null,
            'context'    => $data['context'] ?? null,
            'meta'       => ($viewer->credentials ? $viewer->credentials . ' · ' : '') . 'Via Aegis · ' . now()->format('M Y'),
            'created_at' => now()->toDateTimeString(),
        ];

        $user->meta()->updateOrCreate(
            ['meta_key' => 'peer_reviews'],
            [
                'id'         => (string) Str::uuid(),
                'meta_value' => json_encode($existing),
                'meta_type'  => 'json',
            ]
        );

        // Activity log for the actor (viewer)
        $this->activity->log(
            $viewer->id,
            $this->portalFor($viewer),
            'account',
            ActivitySeverity::Info,
            'endorsement_given',
            "You endorsed {$user->display_name}",
            'Your endorsement has been submitted to their profile.',
            'user',
            $user->id,
            $viewer->id
        );

        // Notification for the profile owner
        $this->activity->log(
            $user->id,
            $this->portalFor($user),
            'account',
            ActivitySeverity::Info,
            'endorsement_received',
            "{$viewer->display_name} endorsed your profile",
            'A new peer endorsement has been added to your profile.',
            'user',
            $viewer->id,
            $viewer->id,
            'notification'
        );

        return back()->with('success', 'Endorsement submitted.');
    }

    /**
     * POST /public/profiles/{user}/service-request
     */
    public function serviceRequest(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();

        abort_if($viewer->id === $user->id, 403, 'Cannot request your own service.');

        $data = $request->validate([
            'service' => 'required|string|max:191',
            'date'    => 'required|date|after_or_equal:today',
            'time'    => 'nullable|string|max:100',
            'format'  => 'nullable|string|max:100',
            'notes'   => 'nullable|string|max:1000',
        ]);

        // Match to a real Service record by title; fall back to first active service; create stub if none
        $service = \App\Models\Service::where('practitioner_id', $user->id)
            ->where('title', $data['service'])
            ->first()
            ?? \App\Models\Service::where('practitioner_id', $user->id)->first()
            ?? \App\Models\Service::create([
                'id'              => 'svc_' . Str::lower(Str::random(10)),
                'practitioner_id' => $user->id,
                'title'           => $data['service'],
                'description'     => 'Ad-hoc service request submitted via public profile.',
                'price_type'      => 'inquiry',
                'price_cents'     => 0,
                'status'          => 'active',
                'is_public'       => true,
            ]);

        $serviceRequest = ServiceRequest::create([
            'id'              => 'sr_' . Str::lower(Str::random(12)),
            'service_id'      => $service?->id,       // null when no title match — column is nullable
            'practitioner_id' => $user->id,
            'inquirer_id'     => $viewer->id,
            'inquirer_name'   => $viewer->display_name,
            'inquirer_email'  => $viewer->email,
            'message'         => implode("\n", array_filter([
                'Service: ' . $data['service'],
                'Preferred Date: ' . $data['date'],
                'Preferred Time: ' . ($data['time'] ?? 'Flexible'),
                'Format: ' . ($data['format'] ?? 'No preference'),
                $data['notes'] ? 'Notes: ' . $data['notes'] : null,
            ])),
            'status' => 'new',
        ]);

        // Fire event → SendEmailNotificationListener picks up T58/T59
        event(new ServiceRequestSubmitted($serviceRequest, $viewer));

        // Activity log for viewer
        $this->activity->log(
            $viewer->id,
            $this->portalFor($viewer),
            'services',
            ActivitySeverity::Info,
            'service_request_sent',
            "Service request sent to {$user->display_name}",
            'Your request has been sent. You\'ll receive a confirmation once the provider responds.',
            'user',
            $user->id,
            $viewer->id
        );

        // Notification for practitioner
        $this->activity->log(
            $user->id,
            $this->portalFor($user),
            'services',
            ActivitySeverity::Info,
            'service_request_received',
            "{$viewer->display_name} submitted a service request",
            "Service: {$data['service']} — Preferred date: {$data['date']}",
            'user',
            $viewer->id,
            $viewer->id,
            'notification'
        );

        return back()->with('success', 'Service request sent.');
    }

    /**
     * POST /public/profiles/{user}/connect
     */
    public function connect(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();
        abort_if($viewer->id === $user->id, 403);

        $req = $this->network->sendRequest($viewer, $user, $request->input('message'));

        // Fire email T42 — connection-request to recipient
        event(new ConnectionRequestSent($req, $viewer, $user));

        // Log for sender
        $this->activity->log(
            $viewer->id,
            $this->portalFor($viewer),
            'account',
            ActivitySeverity::Info,
            'connection_request_sent',
            "You sent a connection request to {$user->display_name}",
            'Awaiting their response.',
            'user',
            $user->id,
            $viewer->id
        );

        return back()->with('success', 'Connection request sent.');
    }


    /**
     * DELETE /public/profiles/{networkRequest}/cancel-connect
     */
    public function cancelConnect(Request $request, NetworkRequest $networkRequest): RedirectResponse
    {
        $viewer = $request->user();
        abort_if($networkRequest->requester_id !== $viewer->id, 403);
        abort_if($networkRequest->status !== \App\Enums\RequestStatus::Pending, 422, 'Request is no longer pending.');
        $networkRequest->update(['status' => 'cancelled']);
        return back()->with('success', 'Connection request cancelled.');
    }

    /**
     * DELETE /public/profiles/{connection}/disconnect
     */
    public function disconnect(Request $request, NetworkConnection $connection): RedirectResponse
    {
        $viewer = $request->user();
        abort_if(
            $connection->user_id !== $viewer->id && $connection->connected_user_id !== $viewer->id,
            403
        );
        $this->network->disconnect($connection, $viewer);
        return back()->with('success', 'Removed from network.');
    }

    /**
     * POST /public/profiles/{user}/hire-request
     * Direct engagement request to a BP (no prior job posting).
     */
    public function hireRequest(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();
        abort_if($viewer->id === $user->id, 403);

        $data = $request->validate([
            'type'          => 'required|string|max:80',
            'start_date'    => 'required|date|after_or_equal:today',
            'duration'      => 'nullable|string|max:100',
            'budget'        => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:80',
            'notes'         => 'nullable|string|max:1000',
            'include_nda'   => 'boolean',
            'require_baa'   => 'boolean',
        ]);

        event(new EngagementRequested($user, $viewer, 'hire', $data));

        $this->activity->log($viewer->id, $this->portalFor($viewer), 'business',
            ActivitySeverity::Info, 'hire_request_sent',
            "Engagement request sent to {$user->display_name}",
            "Type: {$data['type']} — Start: {$data['start_date']}",
            'user', $user->id, $viewer->id);

        $this->activity->log($user->id, 'business_partner', 'business',
            ActivitySeverity::Info, 'hire_request_received',
            "{$viewer->display_name} sent you an engagement request",
            "Type: {$data['type']} — Start: {$data['start_date']}",
            'user', $viewer->id, $viewer->id, 'notification');

        return back()->with('success', 'Engagement request sent. The partner will be notified.');
    }

    /**
     * POST /public/profiles/{user}/quote-request
     */
    public function quoteRequest(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();
        abort_if($viewer->id === $user->id, 403);

        $data = $request->validate([
            'service'  => 'required|string|max:100',
            'size'     => 'nullable|string|max:100',
            'budget'   => 'nullable|string|max:100',
            'timeline' => 'nullable|string|max:100',
            'notes'    => 'nullable|string|max:1000',
            'urgent'   => 'boolean',
        ]);

        event(new EngagementRequested($user, $viewer, 'quote', $data));

        $this->activity->log($viewer->id, $this->portalFor($viewer), 'business',
            ActivitySeverity::Info, 'quote_request_sent',
            "Quote request sent to {$user->display_name}",
            "Service: {$data['service']}",
            'user', $user->id, $viewer->id);

        $this->activity->log($user->id, 'business_partner', 'business',
            ActivitySeverity::Info, 'quote_request_received',
            "{$viewer->display_name} requested a quote",
            "Service: {$data['service']}",
            'user', $viewer->id, $viewer->id, 'notification');

        return back()->with('success', 'Quote request sent. The partner will respond shortly.');
    }

    /**
     * POST /public/profiles/{user}/consultation
     */
    public function consultation(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();
        abort_if($viewer->id === $user->id, 403);

        $data = $request->validate([
            'type'     => 'required|string|max:80',
            'date'     => 'required|date|after_or_equal:today',
            'time'     => 'nullable|string|max:50',
            'duration' => 'nullable|string|max:50',
            'tz'       => 'nullable|string|max:50',
            'agenda'   => 'nullable|string|max:500',
        ]);

        event(new EngagementRequested($user, $viewer, 'consultation', $data));

        $this->activity->log($viewer->id, $this->portalFor($viewer), 'business',
            ActivitySeverity::Info, 'consultation_requested',
            "Consultation requested with {$user->display_name}",
            "Date: {$data['date']} — Type: {$data['type']}",
            'user', $user->id, $viewer->id);

        $this->activity->log($user->id, 'business_partner', 'business',
            ActivitySeverity::Info, 'consultation_request_received',
            "{$viewer->display_name} requested a consultation",
            "Date: {$data['date']} — Type: {$data['type']}",
            'user', $viewer->id, $viewer->id, 'notification');

        return back()->with('success', 'Consultation request sent. The partner will confirm shortly.');
    }

    /**
     * POST /public/profiles/{user}/bp-review
     * Leave a review on a BP profile (stored in UserMeta peer_reviews).
     */
    public function bpReview(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();
        abort_if($viewer->id === $user->id, 403, 'Cannot review yourself.');

        $data = $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'headline' => 'nullable|string|max:80',
            'body'     => 'required|string|max:600',
            'eng_type' => 'nullable|string|max:80',
            'duration' => 'nullable|string|max:50',
        ]);

        $row      = $user->meta()->where('meta_key', 'peer_reviews')->first();
        $existing = $row ? (json_decode((string) $row->meta_value, true) ?? []) : [];

        $existing[] = [
            'name'       => $viewer->display_name,
            'stars'      => (int) $data['rating'],
            'quote'      => $data['body'],
            'headline'   => $data['headline'] ?? null,
            'eng_type'   => $data['eng_type'] ?? null,
            'duration'   => $data['duration'] ?? null,
            'meta'       => ($viewer->credentials ? $viewer->credentials . ' · ' : '') . 'Via Aegis · ' . now()->format('M Y'),
            'created_at' => now()->toDateTimeString(),
        ];

        $user->meta()->updateOrCreate(
            ['meta_key' => 'peer_reviews'],
            ['id' => (string) Str::uuid(), 'meta_value' => json_encode($existing), 'meta_type' => 'json']
        );

        // Recompute avg rating in meta
        $avgRating = round(collect($existing)->avg('stars'), 1);
        $user->meta()->updateOrCreate(
            ['meta_key' => 'bp_avg_rating'],
            ['id' => (string) Str::uuid(), 'meta_value' => (string) $avgRating, 'meta_type' => 'string']
        );

        $this->activity->log($viewer->id, $this->portalFor($viewer), 'account',
            ActivitySeverity::Info, 'bp_review_submitted',
            "You left a {$data['rating']}-star review for {$user->display_name}",
            $data['body'],
            'user', $user->id, $viewer->id);

        $this->activity->log($user->id, 'business_partner', 'account',
            ActivitySeverity::Info, 'review_received',
            "{$viewer->display_name} left you a {$data['rating']}-star review",
            $data['headline'] ?? $data['body'],
            'user', $viewer->id, $viewer->id, 'notification');

        return back()->with('success', 'Review submitted. Thank you for your feedback.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function portalFor(User|string $userOrRole): string
    {
        $role = $userOrRole instanceof User
            ? ($userOrRole->role instanceof \BackedEnum ? $userOrRole->role->value : (string) $userOrRole->role)
            : (string) $userOrRole;
        return match ($role) {
            'practitioner'       => 'provider',
            'continuity_steward' => 'continuity_steward',
            'support_steward'    => 'support_steward',
            'business_partner'   => 'business_partner',
            default              => 'provider',
        };
    }
}
