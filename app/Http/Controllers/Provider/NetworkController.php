<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\BpContract;
use App\Models\NetworkConnection;
use App\Models\NetworkRequest as NetworkRequestModel;
use App\Models\Referral;
use App\Models\User;
use App\Models\VaultItem;
use App\Services\NetworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NetworkController extends Controller
{
    public function __construct(private NetworkService $network) {}

    public function index(Request $request): Response
    {
        $user        = $request->user();
        $connections = $this->network->getConnections($user->id);

        $serializeConn = function (NetworkConnection $nc) use ($user) {
            $partner = ($nc->user_id === $user->id) ? $nc->target : $nc->owner;
            if (!$partner) return null;
            $type = $nc->connection_type instanceof \BackedEnum
                ? $nc->connection_type->value
                : (string) $nc->connection_type;
            return [
                'id'                  => $nc->id,
                'connection_type'     => $type,
                'connected_at'        => $nc->connected_at?->toISOString(),
                'partner_id'          => $partner->id,
                'partner_name'        => $partner->display_name
                    . ($partner->credentials ? ', ' . $partner->credentials : ''),
                'partner_initials'    => $partner->avatar_initials
                    ?? strtoupper(substr($partner->display_name, 0, 2)),
                'partner_role'        => $partner->title ?? '',
                'partner_location'    => $partner->location ?? '',
                'partner_slug'        => $partner->slug ?? '',
                'partner_specialty'   => $partner->specialty ?? '',
                'partner_has_services'=> (bool) $partner->services_mode,
                'partner_telehealth'  => false,
                'partner_categories'  => $partner->organization ?? $partner->title ?? '',
                'partner_type'        => $partner->bp_type instanceof \BackedEnum ? $partner->bp_type->value : (string) ($partner->bp_type ?? ''),
                'partner_rate_cents'  => (int) ($partner->bp_hourly_rate_cents ?? 0),
                'peer_rating'         => 0,
            ];
        };

        $connType = fn($c) => $c->connection_type instanceof \BackedEnum
            ? $c->connection_type->value : (string)$c->connection_type;

        $clinical = $connections
            ->filter(fn($c) => in_array($connType($c), ['practitioner', 'clinical']))
            ->values()->map($serializeConn)->filter()->values();

        $business = $connections
            ->filter(fn($c) => in_array($connType($c), ['business_partner']))
            ->values()->map($serializeConn)->filter()->values();

        $pending = $this->network->getPendingRequests($user->id)
            ->map(function (NetworkRequestModel $nr) {
                $req = $nr->requester;
                if (!$req) return null;
                $reqRole = $req->role instanceof \BackedEnum ? $req->role->value : (string) $req->role;
                $isBusiness = $reqRole === 'business_partner';
                return [
                    'id'                 => $nr->id,
                    'request_type'       => $isBusiness ? 'business' : 'clinical',
                    'message'            => $nr->message,
                    'created_at'         => $nr->created_at?->toISOString(),
                    'requester_id'       => $req->id,
                    'requester_name'     => $req->display_name
                        . ($req->credentials ? ', ' . $req->credentials : ''),
                    'requester_initials' => $req->avatar_initials
                        ?? strtoupper(substr($req->display_name, 0, 2)),
                    'requester_role'     => $isBusiness
                        ? ($req->bp_business_name ?? $req->display_name)
                        : ($req->title ?? ''),
                    'requester_location' => $req->location ?? '',
                    'requester_slug'     => $req->slug ?? '',
                ];
            })->filter()->values();

        $shadows = $this->network->getShadowConnections($user->id)
            ->map(function ($sc) {
                $shadow = $sc->shadowUser;
                return [
                    'id'                  => $sc->id,
                    'shadow_name'         => $shadow?->display_name ?? $sc->shadow_name ?? '',
                    'shadow_initials'     => $shadow?->avatar_initials
                        ?? strtoupper(substr($sc->shadow_name ?? 'SC', 0, 2)),
                    'shadow_role'         => $shadow?->title ?? '',
                    'shadow_location'     => $shadow?->location ?? '',
                    'shadow_specialty'    => $shadow?->specialty ?? '',
                    'shadow_user_id'      => $sc->shadow_user_id,
                    'shadow_slug'         => $shadow?->slug ?? '',
                    // Stats — pulled from UserMeta where seeded, else zero
                    'match_score'         => 0,
                    'peer_rating'         => 0,
                    'referral_count'      => 0,
                    'response_time_hours' => 0,
                ];
            })->values();

        // Serialized for ReferralModal :network prop
        $referralNetwork = $clinical->map(fn($c) => [
            'id'           => $c['partner_id'],
            'display_name' => $c['partner_name'],
            'credentials'  => '',
            'slug'         => $c['partner_slug'],
            'specialty'    => $c['partner_specialty'],
            'location'     => $c['partner_location'],
        ])->values();

        // Dynamic recommendation feeds — see NetworkService::getRecommended*.
        // If no per-user rows are seeded, the service falls back to globals.
        $recommendedPartnerCategories = $this->network->getRecommendedPartnerCategories($user->id);

        // Directory-wide search results for the Search Providers tab. Uses the
        // same public-flag scope as the public directory. Excludes the current
        // user and anyone already connected/pending so the network-status pill
        // renders coherently. IDs and slugs are real — every card action wires
        // through to the message / referral / connect / profile routes.
        // Collect all user IDs this user is connected to (accepted connections)
        // NetworkConnection columns: user_id / connected_user_id
        $connectedIds = $connections
            ->flatMap(fn ($c) => [$c->user_id, $c->connected_user_id])
            ->filter()
            ->reject(fn ($id) => $id === $user->id)
            ->unique()->values()->all();

        // Also track pending outbound requests so the button shows "Pending" not "Connect"
        $pendingOutboundIds = \App\Models\NetworkRequest::where('requester_id', $user->id)
            ->where('status', 'pending')
            ->pluck('recipient_id')
            ->filter()
            ->all();

        // Track inbound pending requests so the button shows "Accept" instead of "Connect"
        $pendingInboundIds = \App\Models\NetworkRequest::where('recipient_id', $user->id)
            ->where('status', 'pending')
            ->pluck('requester_id')
            ->filter()
            ->all();

        // Build recommended shadow providers AFTER ID arrays so networkStatus is accurate
        $recommendedShadowProviders = $this->network->getRecommendedShadowProviders($user->id)
            ->map(function (array $p) use ($connectedIds, $pendingOutboundIds, $pendingInboundIds) {
                $id = $p['id'];
                $p['networkStatus'] = in_array($id, $connectedIds, true)
                    ? 'in-network'
                    : (in_array($id, $pendingInboundIds, true)  ? 'pending-received'
                    : (in_array($id, $pendingOutboundIds, true) ? 'pending' : 'not-connected'));
                $p['connected'] = $p['networkStatus'] === 'in-network';
                return $p;
            })->values();

        $searchProviders = User::query()
            ->where('practitioner_public', 1)
            ->where('role', 'practitioner')
            ->where('id', '!=', $user->id)
            ->orderBy('display_name')
            ->limit(60)
            ->get()
            ->map(function (User $u) use ($connectedIds, $pendingOutboundIds, $pendingInboundIds) {
                $tags = collect(explode(',', (string) ($u->specialty ?? '')))
                    ->map(fn ($t) => trim($t))->filter()->values()->all();
                return [
                    'id'            => $u->id,
                    'slug'          => $u->slug ?? '',
                    'name'          => $u->display_name
                        . ($u->credentials ? ', ' . $u->credentials : ''),
                    'initials'      => $u->avatar_initials
                        ?? strtoupper(substr($u->display_name, 0, 2)),
                    'role'          => $u->title ?? '',
                    'location'      => $u->location ?? '',
                    'tags'          => $tags,
                    'rating'        => 0.0,
                    'reviews'       => 0,
                    'refs'          => '— refs',
                    'acc'           => '— acc',
                    'resp'          => '— resp',
                    'telehealth'    => false,
                    'has_services'  => (bool) $u->services_mode,
                    'networkStatus' => in_array($u->id, $connectedIds, true)
                        ? 'in-network'
                        : (in_array($u->id, $pendingInboundIds, true) ? 'pending-received'
                            : (in_array($u->id, $pendingOutboundIds, true) ? 'pending' : 'not-connected')),
                ];
            })->values();

        // Roster — parity with DashboardController so the centralized
        // ReferralModal shows the same client picker on both pages.
        $referralRoster = VaultItem::where('practitioner_id', $user->id)
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

        // ── Business Partner directory (Search Business Partners tab) ────────
        $bpDirectory = User::query()
            ->where('business_partner_public', 1)
            ->where('role', 'business_partner')
            ->where('id', '!=', $user->id)
            ->orderBy('display_name')
            ->limit(60)
            ->get()
            ->map(function (User $u) use ($connectedIds, $pendingOutboundIds, $pendingInboundIds) {
                $rateDollars = $u->bp_hourly_rate_cents
                    ? '$' . number_format($u->bp_hourly_rate_cents / 100) . '/hr'
                    : null;
                // Jobs completed = count of active+completed contracts for this BP
                $jobsCompleted = BpContract::where('bp_id', $u->id)
                    ->whereIn('status', ['active', 'completed'])
                    ->count();
                $bpTypeVal = $u->bp_type instanceof \BackedEnum
                    ? $u->bp_type->value
                    : (string) ($u->bp_type ?? '');
                return [
                    'id'          => $u->id,
                    'slug'        => $u->slug ?? '',
                    'name'        => $u->display_name . ($u->credentials ? ', ' . $u->credentials : ''),
                    'initials'    => $u->avatar_initials ?? strtoupper(substr($u->display_name, 0, 2)),
                    'role'        => $u->title ?? '',
                    'location'    => $u->location ?? '',
                    'tags'        => array_values(array_filter(array_map('trim', explode(',', (string) ($u->specialty ?? ''))))),
                    'rating'      => 0.0,   // Phase 4: peer-review aggregation
                    'reviews'     => 0,
                    'jobs'        => $jobsCompleted,
                    'rate'        => $rateDollars ?? '—',
                    'rate_cents'  => (int) ($u->bp_hourly_rate_cents ?? 0),
                    'partnerType' => $bpTypeVal ? strtoupper($bpTypeVal) : '',
                    'kind'        => 'business',
                    'category'    => strtolower($u->title ?? ''),
                    'has_services'=> (bool) $u->services_mode,
                    'networkStatus' => in_array($u->id, $connectedIds, true)
                        ? 'in-network'
                        : (in_array($u->id, $pendingInboundIds, true) ? 'pending-received'
                            : (in_array($u->id, $pendingOutboundIds, true) ? 'pending' : 'not-connected')),
                ];
            })->values();

        // Real BP stats for My Partners chips
        $bpActiveCount = $business->count();
        $activeContracts = BpContract::where('practitioner_id', $user->id)
            ->where('status', 'active')
            ->count();
        $myReferrals = Referral::where('sender_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->get();

        $totalRefs = $myReferrals->count();

        // Acceptance rate = referrals RECEIVED by this user that they accepted
        // (mirrors the public-profile metric: "how often does this practitioner
        // accept incoming referrals?")
        $receivedRefs    = $myReferrals->where('recipient_id', $user->id);
        $receivedCount   = $receivedRefs->count();
        $acceptedReceived = $receivedRefs->where('status', 'accepted');

        $avgAcc = $receivedCount > 0
            ? (int) round($acceptedReceived->count() / $receivedCount * 100)
            : 0;

        // Avg response time in hours — time between referral creation and
        // when this user (recipient) responded. Use abs() so seeded data
        // with inverted timestamps never produces a negative display value.
        $respondedRefs = $acceptedReceived->filter(fn ($r) => $r->responded_at !== null);
        if ($respondedRefs->count() > 0) {
            $totalMinutes = $respondedRefs->sum(
                fn ($r) => abs($r->created_at->diffInMinutes($r->responded_at))
            );
            $avgResp = round($totalMinutes / $respondedRefs->count() / 60, 1);
        } else {
            $avgResp = 0;
        }

        return Inertia::render('provider/Network', [
            'clinicalConnections'          => $clinical,
            'bpConnections'                => $business,
            'pendingRequests'              => $pending,
            'shadowConnections'            => $shadows,
            'referralNetwork'              => $referralNetwork,
            'recommendedPartnerCategories' => $recommendedPartnerCategories,
            'recommendedShadowProviders'   => $recommendedShadowProviders,
            'searchProviders'              => $searchProviders,
            'referralRoster'               => $referralRoster,
            'roster'                       => $referralRoster,
            'bpDirectory'                  => $bpDirectory,
            'stats'                        => [
                'clinical'         => $clinical->count(),
                'bp_count'         => $activeContracts,
                // All outbound pending connection requests (sent by this user, not yet responded to)
                'bp_pending'       => NetworkRequestModel::where('requester_id', $user->id)
                    ->where('status', 'pending')
                    ->count(),
                'total_refs'       => $totalRefs,
                'avg_acc'          => $avgAcc,
                'avg_resp'         => $avgResp,
                // Incoming requests awaiting this user's response
                'pending_requests' => $pending->count(),
                'active_shadows'   => $shadows->count(),
            ],
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'note'       => 'nullable|string|max:500',
        ]);
        $this->network->sendRequest(
            $request->user(),
            User::findOrFail($data['to_user_id']),
            $data['note'] ?? null
        );
        return back()->with('success', 'Connection request sent.');
    }

    public function accept(Request $request, NetworkRequestModel $networkRequest): RedirectResponse
    {
        $this->network->acceptRequest($networkRequest, $request->user());
        return back()->with('success', 'Connection accepted.');
    }

    public function decline(Request $request, NetworkRequestModel $networkRequest): RedirectResponse
    {
        $this->network->declineRequest($networkRequest, $request->user(), $request->input('reason'));
        return back()->with('success', 'Request declined.');
    }

    public function disconnect(Request $request, NetworkConnection $connection): RedirectResponse
    {
        abort_if(
            $connection->user_id !== $request->user()->id
                && $connection->connected_user_id !== $request->user()->id,
            403
        );
        $this->network->disconnect($connection, $request->user());
        return back()->with('success', 'Disconnected.');
    }

    public function invite(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'        => 'required|email',
            'display_name' => 'required|string|max:100',
            'note'         => 'nullable|string|max:500',
        ]);
        $this->network->inviteExternal(
            $request->user(),
            $data['email'],
            $data['display_name'],
            $data['note'] ?? null
        );
        return back()->with('success', 'Invitation sent.');
    }

    /**
     * Add a provider to the practitioner's referral (shadow) list manually.
     * Powers the "Add to Referral List" modal on the Referrals & Tools tab.
     */
    public function addShadow(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shadow_user_id' => 'nullable|exists:users,id',
            'display_name'   => 'nullable|string|max:191',
            'note'           => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        if (!empty($data['shadow_user_id'])) {
            // Direct add by user ID (from search/RSP card)
            $existing = \App\Models\ShadowConnection::where('user_id', $user->id)
                ->where('shadow_user_id', $data['shadow_user_id'])
                ->whereNull('deleted_at')
                ->first();
            if ($existing) {
                return back()->with('info', 'Already in your shadow network.');
            }
            \App\Models\ShadowConnection::create([
                'id'             => 'sc_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)),
                'user_id'        => $user->id,
                'shadow_user_id' => $data['shadow_user_id'],
                'source'         => 'direct',
            ]);
        } else {
            // Manual add by name (from the manual entry modal)
            $this->network->addShadowManual(
                $user,
                $data['display_name'] ?? 'Unknown',
                $data['note'] ?? null
            );
        }

        return back()->with('success', 'Added to your shadow network.');
    }

    /**
     * Remove a shadow connection.
     */
    public function removeShadow(Request $request, \App\Models\ShadowConnection $shadowConnection): RedirectResponse
    {
        abort_if($shadowConnection->user_id !== $request->user()->id, 403);
        $shadowConnection->delete();
        return back()->with('success', 'Shadow removed.');
    }
}
