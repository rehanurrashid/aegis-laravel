<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\BpContract;
use App\Models\NetworkConnection;
use App\Models\NetworkRequest;
use App\Models\ServiceRequest;
use App\Models\VaultItem;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $profiles) {}


    /**
     * Resolve the effective viewer for public profile pages.
     *
     * A logged-in user who hasn't verified their email, or whose subscription
     * is not yet active, is treated as an anonymous viewer for public profiles.
     * They can see the public page but cannot use any interactive features
     * (connect, refer, request services, etc.) that require a full account.
     *
     * Returns null if the viewer should be treated as anonymous.
     */
    private function effectiveViewer(?\App\Models\User $viewer): ?\App\Models\User
    {
        if (!$viewer) return null;

        // Must have verified email
        if (!$viewer->verified) return null;

        // Free roles (invited CS, SS) — verified is enough
        $role   = $viewer->role instanceof \App\Enums\UserRole ? $viewer->role->value : (string) $viewer->role;
        $csType = $viewer->cs_account_type instanceof \App\Enums\CsAccountType
            ? $viewer->cs_account_type->value
            : (string) ($viewer->cs_account_type ?? '');

        $needsSub = $role === 'practitioner'
            || $role === 'business_partner'
            || ($role === 'continuity_steward' && $csType === 'business');

        if ($needsSub) {
            try {
                $sub = $viewer->subscriptions()->where('type', 'default')->latest()->first();
                $active = $sub && in_array($sub->stripe_status, ['active', 'trialing', 'past_due'], true);
            } catch (\Throwable) {
                $active = true; // fail open
            }
            if (!$active) return null;
        }

        return $viewer;
    }

    public function provider(Request $request, string $slug): Response
    {
        $user         = $this->profiles->getPublicProfile($slug);
        $rawViewer    = Auth::user();
        $viewer       = $this->effectiveViewer($rawViewer);

        $isOwner    = $rawViewer && $viewer && $rawViewer->id === $user->id;
        $isLoggedIn = (bool) $viewer;  // only true if verified + active

        abort_if(
            ! $user
            || $user->role !== UserRole::Practitioner
            || (! $user->practitioner_public && ! $isOwner),
            404
        );
        $profileMeta = $this->profiles->buildProfileMeta($user, $viewer);

        $services = \App\Models\Service::where('practitioner_id', $user->id)
            ->active()
            ->public()
            ->orderBy('title')
            ->get();

        // Strip sensitive contact info for anonymous viewers
        if (! $isLoggedIn) {
            $user->makeHidden(['email', 'phone']);
        }

        if ($isOwner) {
            $profileMeta['private_notes'] = $this->profiles->getPrivateNotes($user);
        }

        // Viewer's vault roster (for ReferralModal "Pull from Client Roster" tab)
        $referralRoster = [];
        $referralNetwork = [];
        if ($viewer && ! $isOwner) {
            $referralRoster = VaultItem::where('practitioner_id', $viewer->id)
                ->where('zone', 'roster')
                ->whereNotNull('client_name')
                ->get()
                ->map(fn ($v) => [
                    'id'              => $v->id,
                    'client_name'     => $v->client_name,
                    'client_service'  => $v->category,
                    'client_location' => null,
                    'client_notes'    => $v->sub_label,
                    'client_status'   => $v->status?->value ?? $v->status,
                ])
                ->sortBy(fn ($r) => $r['client_status'] === 'priority' ? 0 : 1)
                ->values()
                ->toArray();

            $referralNetwork = NetworkConnection::where(function ($q) use ($viewer) {
                    $q->where('user_id', $viewer->id)
                      ->orWhere('connected_user_id', $viewer->id);
                })
                ->where('status', 'active')
                ->with(['owner:id,display_name,credentials,specialty,slug',
                        'target:id,display_name,credentials,specialty,slug'])
                ->get()
                ->map(function ($conn) use ($viewer) {
                    $peer = $conn->user_id === $viewer->id ? $conn->target : $conn->owner;
                    if (!$peer) return null;
                    $initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $peer->display_name ?? ''), 0, 2));
                    return [
                        'id'           => $peer->id,
                        'display_name' => $peer->display_name,
                        'credentials'  => $peer->credentials,
                        'specialty'    => $peer->specialty,
                        'location'     => null,
                        'slug'         => $peer->slug,
                        'accepting'    => true,
                        'is_connected' => true,
                        'initials'     => $initials,
                        'avatar_url'   => null,
                    ];
                })
                ->filter()
                ->values()
                ->toArray();
        }

        // Services profile bio/headline from user_meta
        $serviceBio        = $user->meta()->where('meta_key', 'service_bio')->value('meta_value');
        $serviceHeadline   = $user->meta()->where('meta_key', 'service_headline')->value('meta_value');
        $serviceSpecialties= $user->meta()->where('meta_key', 'service_specialties')->value('meta_value');
        $yearsExperience   = $user->meta()->where('meta_key', 'years_experience')->value('meta_value');

        return Inertia::render('public/ProviderProfile', [
            'user'              => $user,
            'profileMeta'       => $profileMeta,
            'services'          => $services,
            // Defensively decode: values may be plain string or json-encoded string depending on when they were saved
            'serviceBio'        => $serviceBio ? (json_decode($serviceBio, true) ?? $serviceBio) : null,
            'serviceHeadline'   => $serviceHeadline ? (json_decode($serviceHeadline, true) ?? $serviceHeadline) : null,
            'serviceSpecialties'=> $serviceSpecialties ? (is_array($decoded = json_decode($serviceSpecialties, true)) ? $decoded : (is_array($inner = json_decode($decoded ?? '', true)) ? $inner : [])) : [],
            'yearsExperience'   => $yearsExperience ? (int) $yearsExperience : null,
            'viewerRole'       => $viewer?->role?->value ?? null,
            'isOwner'          => $isOwner,
            'isLoggedIn'       => $isLoggedIn,
            'isVerifiedMember' => $isLoggedIn, // true only when viewer is verified + active
            'referralRoster'  => $referralRoster,
            'referralNetwork' => $referralNetwork,
            'myServiceRequests' => $viewer && ! $isOwner
                ? ServiceRequest::where('practitioner_id', $user->id)
                    ->where('inquirer_id', $viewer->id)
                    ->with('service:id,title')
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn ($r) => [
                        'id'            => $r->id,
                        'service_title' => $r->service?->title ?? 'Appointment Request',
                        'message'       => $r->message,
                        'status'        => $r->status instanceof \BackedEnum ? $r->status->value : (string) $r->status,
                        'response_note' => $r->response_note,
                        'responded_at'  => $r->responded_at?->format('M j, Y'),
                        'created_at'    => $r->created_at?->format('M j, Y'),
                    ])
                    ->values()
                    ->toArray()
                : [],
        ]);
    }

    public function continuityStewarded(Request $request, string $slug): Response
    {
        $user         = $this->profiles->getPublicProfile($slug);
        $rawViewer    = Auth::user();
        $viewer       = $this->effectiveViewer($rawViewer);

        $isOwner    = $rawViewer && $viewer && $rawViewer->id === $user->id;
        $isLoggedIn = (bool) $viewer;

        abort_if(
            ! $user
            || $user->role !== UserRole::ContinuitySteward
            || (! $user->cs_public && ! $isOwner),
            404
        );
        $profileMeta = $this->profiles->buildProfileMeta($user, $viewer);

        if (! $isLoggedIn) {
            $user->makeHidden(['email', 'phone']);
        }

        return Inertia::render('public/ContinuityStewardProfile', [
            'user'        => $user,
            'profileMeta' => $profileMeta,
            'viewerRole'       => $viewer?->role?->value ?? null,
            'isOwner'          => $isOwner,
            'isLoggedIn'       => $isLoggedIn,
            'isVerifiedMember' => $isLoggedIn,
        ]);
    }

    public function supportSteward(Request $request, string $slug): Response
    {
        $user         = $this->profiles->getPublicProfile($slug);
        $rawViewer    = Auth::user();
        $viewer       = $this->effectiveViewer($rawViewer);

        abort_if(! $user || $user->role !== UserRole::SupportSteward, 404);

        // SS profiles are relationship-gated: only owner or linked Provider
        // Use rawViewer for ownership/relationship check (so even unverified owner can still see)
        $invitedById = $user->invited_by_id ?? null;
        $viewerIsLinkedProvider = $rawViewer && $invitedById && $rawViewer->id === $invitedById;
        $isOwner = $rawViewer && $rawViewer->id === $user->id;

        abort_if(! $isOwner && ! $viewerIsLinkedProvider, 404);

        $isLoggedIn = (bool) $viewer;
        $profileMeta = $this->profiles->buildProfileMeta($user, $viewer);

        return Inertia::render('public/SupportStewardProfile', [
            'user'        => $user,
            'profileMeta' => $profileMeta,
            'viewerRole'       => $viewer?->role?->value ?? null,
            'isOwner'          => $isOwner,
            'isLoggedIn'       => $isLoggedIn,
            'isVerifiedMember' => $isLoggedIn,
        ]);
    }

    public function business(Request $request, string $slug): Response
    {
        $user         = $this->profiles->getPublicProfile($slug);
        $rawViewer    = Auth::user();
        $viewer       = $this->effectiveViewer($rawViewer);

        $isOwner    = $rawViewer && $viewer && $rawViewer->id === $user->id;
        $isLoggedIn = (bool) $viewer;

        abort_if(
            ! $user
            || $user->role !== UserRole::BusinessPartner
            || (! $user->business_partner_public && ! $isOwner),
            404
        );
        $profileMeta = $this->profiles->buildProfileMeta($user, $viewer);

        if (! $isLoggedIn) {
            $user->makeHidden(['email', 'phone']);
        }

        // ── Connection status for viewer ───────────────────────────────────────
        $connectionStatus = 'not-connected'; // 'connected' | 'pending-sent' | 'pending-received' | 'not-connected'
        $connectionId     = null;
        $pendingRequestId = null;

        if ($viewer && ! $isOwner) {
            $conn = NetworkConnection::where(function ($q) use ($viewer, $user) {
                $q->where('user_id', $viewer->id)->where('connected_user_id', $user->id);
            })->orWhere(function ($q) use ($viewer, $user) {
                $q->where('user_id', $user->id)->where('connected_user_id', $viewer->id);
            })->where('status', 'active')->first();

            if ($conn) {
                $connectionStatus = 'connected';
                $connectionId     = $conn->id;
            } else {
                $outbound = \App\Models\NetworkRequest::where('requester_id', $viewer->id)
                    ->where('recipient_id', $user->id)
                    ->where('status', 'pending')->first();
                $inbound  = \App\Models\NetworkRequest::where('requester_id', $user->id)
                    ->where('recipient_id', $viewer->id)
                    ->where('status', 'pending')->first();

                if ($outbound) { $connectionStatus = 'pending-sent';     $pendingRequestId = $outbound->id; }
                elseif ($inbound) { $connectionStatus = 'pending-received'; $pendingRequestId = $inbound->id; }
            }
        }

        // ── Real stats ─────────────────────────────────────────────────────────
        $completedContracts = \App\Models\BpContract::where('bp_id', $user->id)
            ->where('status', 'completed')->count();
        $activeContracts    = \App\Models\BpContract::where('bp_id', $user->id)
            ->where('status', 'active')->count();

        // ── Reviews from UserMeta ──────────────────────────────────────────────
        $reviewsMeta = $user->meta()->where('meta_key', 'peer_reviews')->first();
        $reviews     = $reviewsMeta ? (json_decode((string) $reviewsMeta->meta_value, true) ?? []) : [];
        $avgRating   = count($reviews) ? round(collect($reviews)->avg('stars'), 1) : null;

        // ── Past engagement requests this viewer sent to this BP ────────────────
        $engagementRequests = [];
        if ($viewer && ! $isOwner) {
            $engagementRequests = \App\Models\BpEngagementRequest::where('bp_id', $user->id)
                ->where('practitioner_id', $viewer->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($r) => [
                    'id'     => $r->id,
                    'type'   => $r->type,
                    'label'  => match ($r->type) {
                        'hire'         => 'Engagement Request — ' . ($r->engagement_type ?? 'Custom'),
                        'quote'        => 'Quote Request — ' . ($r->service ?? 'General'),
                        'consultation' => 'Consultation — ' . ($r->start_date?->format('M j, Y') ?? ''),
                        default        => ucfirst($r->type),
                    },
                    'status' => $r->status,  // lowercase — Vue filters on 'pending'
                    'time'   => $r->created_at->format('M j, g:i A'),
                ])
                ->values()
                ->toArray();
        }

        return Inertia::render('public/BusinessProfile', [
            'user'             => $user,
            'profileMeta'      => $profileMeta,
            'viewerRole'       => $viewer?->role?->value ?? null,
            'isOwner'          => $isOwner,
            'isLoggedIn'       => $isLoggedIn,
            'isVerifiedMember' => $isLoggedIn,
            // Connection
            'connectionStatus' => $connectionStatus,   // 'connected'|'pending-sent'|'pending-received'|'not-connected'
            'connectionId'     => $connectionId,
            'pendingRequestId' => $pendingRequestId,
            // Stats
            'bpStats' => [
                'completed_contracts' => $completedContracts,
                'active_contracts'    => $activeContracts,
                'avg_rating'          => $avgRating,
                'review_count'        => count($reviews),
                'hourly_rate'         => $user->bp_hourly_rate_cents
                    ? '$' . number_format($user->bp_hourly_rate_cents / 100) . '/hr'
                    : null,
            ],
            // Reviews
            'reviews'            => collect($reviews)->sortByDesc('created_at')->take(5)->values()->toArray(),
            // Past engagement requests this viewer sent (persisted in DB, survives refresh)
            'engagementRequests' => $engagementRequests,
        ]);
    }
}
