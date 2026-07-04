<?php
declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Enums\ActivitySeverity;
use App\Events\Business\EngagementRequested;
use App\Http\Controllers\Controller;
use App\Models\BpEngagementRequest;
use App\Models\BpJob;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EngagementRequestsController extends Controller
{
    public function __construct(private ActivityService $activity) {}

    /**
     * GET /business/engagement-requests
     */
    public function index(Request $request): Response
    {
        $bp = $request->user();

        $requests = BpEngagementRequest::with('practitioner:id,display_name,avatar_initials,slug,bp_type')
            ->where('bp_id', $bp->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'               => $r->id,
                'type'             => $r->type,
                'engagement_type'  => $r->engagement_type,
                'service'          => $r->service,
                'meeting_type'     => $r->meeting_type,
                'start_date'       => $r->start_date?->format('M j, Y'),
                'duration'         => $r->duration,
                'budget'           => $r->budget,
                'payment_terms'    => $r->payment_terms,
                'notes'            => $r->notes,
                'agenda'           => $r->agenda,
                'size'             => $r->size,
                'timeline'         => $r->timeline,
                'preferred_time'   => $r->preferred_time,
                'meeting_duration' => $r->meeting_duration,
                'timezone'         => $r->timezone,
                'include_nda'      => $r->include_nda,
                'require_baa'      => $r->require_baa,
                'urgent'           => $r->urgent,
                'status'           => $r->status,
                'response_note'    => $r->response_note,
                'created_at'       => $r->created_at->format('M j, Y g:i A'),
                'practitioner'     => $r->practitioner ? [
                    'id'             => $r->practitioner->id,
                    'display_name'   => $r->practitioner->display_name,
                    'avatar_initials'=> $r->practitioner->avatar_initials,
                    'slug'           => $r->practitioner->slug,
                ] : null,
            ])
            ->values()
            ->toArray();

        $stats = [
            'pending'      => collect($requests)->where('status', 'pending')->count(),
            'accepted'     => collect($requests)->where('status', 'accepted')->count(),
            'declined'     => collect($requests)->where('status', 'declined')->count(),
            'total'        => count($requests),
        ];

        return Inertia::render('business-partner/EngagementRequests', [
            'requests' => $requests,
            'stats'    => $stats,
        ]);
    }

    /**
     * POST /business/engagement-requests/{request}/accept
     *
     * For 'hire' type: creates a BpJob (direct engagement), status 'filled'.
     * For 'quote' / 'consultation': marks accepted + logs reply.
     */
    public function accept(Request $request, BpEngagementRequest $engagementRequest): RedirectResponse
    {
        $bp = $request->user();
        abort_if($engagementRequest->bp_id !== $bp->id, 403);
        abort_if($engagementRequest->status !== 'pending', 422, 'Request is no longer pending.');

        $data = $request->validate([
            'response_note' => 'nullable|string|max:500',
        ]);

        $engagementRequest->update([
            'status'        => 'accepted',
            'response_note' => $data['response_note'] ?? null,
            'responded_at'  => now(),
        ]);

        // For hire requests — create a direct BpJob so the contract pipeline kicks in
        if ($engagementRequest->type === 'hire') {
            BpJob::create([
                'id'              => 'job_' . Str::lower(Str::random(12)),
                'practitioner_id' => $engagementRequest->practitioner_id,
                'title'           => ($engagementRequest->engagement_type ?? 'Engagement') . ' — Direct Hire',
                'description'     => $engagementRequest->notes ?? 'Direct engagement request accepted via Aegis.',
                'status'          => 'filled',
                'budget_type'     => match ($engagementRequest->engagement_type) {
                    'Hourly / Time-Based' => 'hourly',
                    'Monthly Retainer'    => 'monthly',
                    default               => 'fixed',
                },
                'posted_at'       => now(),
            ]);
        }

        // Log for BP (actor)
        $this->activity->log(
            $bp->id, 'business_partner', 'business', ActivitySeverity::Info,
            'engagement_request_accepted',
            'You accepted an engagement request',
            ucfirst($engagementRequest->type) . ' request from ' . ($engagementRequest->practitioner?->display_name ?? 'a practitioner'),
            'bp_engagement_request', $engagementRequest->id, $bp->id
        );

        // Notification for practitioner
        $this->activity->log(
            $engagementRequest->practitioner_id, 'provider', 'business', ActivitySeverity::Info,
            'engagement_request_accepted',
            $bp->display_name . ' accepted your ' . $engagementRequest->type . ' request',
            $data['response_note'] ?? 'Your request has been accepted. Check your messages to continue.',
            'bp_engagement_request', $engagementRequest->id, $bp->id,
            'notification'
        );

        return back()->with('success', 'Request accepted. The practitioner has been notified.');
    }

    /**
     * POST /business/engagement-requests/{request}/decline
     */
    public function decline(Request $request, BpEngagementRequest $engagementRequest): RedirectResponse
    {
        $bp = $request->user();
        abort_if($engagementRequest->bp_id !== $bp->id, 403);
        abort_if($engagementRequest->status !== 'pending', 422, 'Request is no longer pending.');

        $data = $request->validate([
            'response_note' => 'nullable|string|max:500',
        ]);

        $engagementRequest->update([
            'status'        => 'declined',
            'response_note' => $data['response_note'] ?? null,
            'responded_at'  => now(),
        ]);

        // Log for BP
        $this->activity->log(
            $bp->id, 'business_partner', 'business', ActivitySeverity::Info,
            'engagement_request_declined',
            'You declined an engagement request',
            ucfirst($engagementRequest->type) . ' request from ' . ($engagementRequest->practitioner?->display_name ?? 'a practitioner'),
            'bp_engagement_request', $engagementRequest->id, $bp->id
        );

        // Notification for practitioner
        $this->activity->log(
            $engagementRequest->practitioner_id, 'provider', 'business', ActivitySeverity::Info,
            'engagement_request_declined',
            $bp->display_name . ' declined your ' . $engagementRequest->type . ' request',
            $data['response_note'] ?? 'The partner is not available at this time.',
            'bp_engagement_request', $engagementRequest->id, $bp->id,
            'notification'
        );

        return back()->with('success', 'Request declined. The practitioner has been notified.');
    }

    /**
     * POST /business/engagement-requests/{request}/reply
     * Send a response note without changing status (e.g. ask for more info).
     */
    public function reply(Request $request, BpEngagementRequest $engagementRequest): RedirectResponse
    {
        $bp = $request->user();
        abort_if($engagementRequest->bp_id !== $bp->id, 403);

        $data = $request->validate([
            'response_note' => 'required|string|max:1000',
        ]);

        $engagementRequest->update(['response_note' => $data['response_note']]);

        $this->activity->log(
            $engagementRequest->practitioner_id, 'provider', 'business', ActivitySeverity::Info,
            'engagement_request_replied',
            $bp->display_name . ' responded to your ' . $engagementRequest->type . ' request',
            $data['response_note'],
            'bp_engagement_request', $engagementRequest->id, $bp->id,
            'notification'
        );

        return back()->with('success', 'Reply sent.');
    }
}
