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

        // Pull match scores from network_recommendations for this user's shadows
        $shadowMatchScores = \App\Models\NetworkRecommendation::where('user_id', $user->id)
            ->where('kind', 'shadow_provider')
            ->whereNotNull('match_score')
            ->pluck('match_score', 'provider_user_id');

        // Realistic demo referral stats per nd_* shadow user
        $demoShadowStats = [
            'nd_rachel_moore'  => ['referral_count' => 14, 'acceptance_rate' => 92, 'response_time_hours' => 1.2, 'peer_rating' => 4.9],
            'nd_sarah_nguyen'  => ['referral_count' => 9,  'acceptance_rate' => 89, 'response_time_hours' => 2.4, 'peer_rating' => 4.7],
            'nd_nina_park'     => ['referral_count' => 6,  'acceptance_rate' => 85, 'response_time_hours' => 3.1, 'peer_rating' => 4.6],
            'nd_james_okafor'  => ['referral_count' => 11, 'acceptance_rate' => 91, 'response_time_hours' => 1.8, 'peer_rating' => 4.8],
            'nd_maya_torres'   => ['referral_count' => 7,  'acceptance_rate' => 88, 'response_time_hours' => 2.0, 'peer_rating' => 4.7],
            'nd_alicia_reeves' => ['referral_count' => 4,  'acceptance_rate' => 80, 'response_time_hours' => 4.5, 'peer_rating' => 4.5],
            'nd_danielle_fox'  => ['referral_count' => 3,  'acceptance_rate' => 78, 'response_time_hours' => 5.0, 'peer_rating' => 4.4],
            'nd_amara_osei'    => ['referral_count' => 5,  'acceptance_rate' => 83, 'response_time_hours' => 3.8, 'peer_rating' => 4.6],
            'nd_diana_vasquez' => ['referral_count' => 2,  'acceptance_rate' => 75, 'response_time_hours' => 6.2, 'peer_rating' => 4.3],
            'nd_aisha_patel'   => ['referral_count' => 8,  'acceptance_rate' => 87, 'response_time_hours' => 2.2, 'peer_rating' => 4.7],
            'nd_devon_hall'    => ['referral_count' => 1,  'acceptance_rate' => 70, 'response_time_hours' => 8.0, 'peer_rating' => 4.2],
            'nd_jordan_lee'    => ['referral_count' => 3,  'acceptance_rate' => 76, 'response_time_hours' => 5.5, 'peer_rating' => 4.3],
        ];

        $shadows = $this->network->getShadowConnections($user->id)
            ->map(function ($sc) use ($shadowMatchScores, $demoShadowStats) {
                $shadow  = $sc->shadowUser;
                $uid     = $sc->shadow_user_id ?? '';
                $stats   = $demoShadowStats[$uid] ?? [];
                return [
                    'id'                  => $sc->id,
                    'shadow_name'         => $shadow?->display_name ?? $sc->shadow_name ?? '',
                    'shadow_initials'     => $shadow?->avatar_initials
                        ?? strtoupper(substr($sc->shadow_name ?? 'SC', 0, 2)),
                    'shadow_role'         => $shadow?->title ?? '',
                    'shadow_location'     => $shadow?->location ?? '',
                    'shadow_specialty'    => $shadow?->specialty ?? '',
                    'shadow_user_id'      => $uid,
                    'shadow_slug'         => $shadow?->slug ?? '',
                    'match_score'         => (int) ($shadowMatchScores[$uid] ?? 0),
                    'peer_rating'         => $stats['peer_rating']         ?? 0,
                    'referral_count'      => $stats['referral_count']      ?? 0,
                    'response_time_hours' => $stats['response_time_hours'] ?? 0,
                    'acceptance_rate'     => $stats['acceptance_rate']     ?? 0,
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

        // Referral List AI candidates — real practitioners only (role=practitioner),
        // excluding the viewer, already-connected, and already-shadowed users.
        // Sorted by practitioner_public desc (verified/public first), then display_name.
        $shadowedIds = \App\Models\ShadowConnection::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->pluck('shadow_user_id')
            ->filter()
            ->all();

        $referralCandidates = User::query()
            ->where('role', 'practitioner')
            ->where('practitioner_public', 1)
            ->where('id', '!=', $user->id)
            ->whereNotIn('id', array_merge($connectedIds, $shadowedIds))
            ->orderByDesc('verified')
            ->orderBy('display_name')
            ->limit(40)
            ->get()
            ->map(function (User $u) {
                $tags = collect(explode(',', (string) ($u->specialty ?? '')))
                    ->map(fn ($t) => trim($t))->filter()->values()->all();
                return [
                    'id'       => $u->id,
                    'name'     => $u->display_name . ($u->credentials ? ', ' . $u->credentials : ''),
                    'slug'     => $u->slug ?? '',
                    'initials' => $u->avatar_initials ?? strtoupper(substr($u->display_name, 0, 2)),
                    'role'     => $u->title ?? '',
                    'location' => $u->location ?? '',
                    'tags'     => array_slice($tags, 0, 3),
                    'match'    => 0,   // AI scoring is Phase 4
                    'rating'   => 0,
                    'networkStatus' => 'not-connected',
                ];
            })->values();

        return Inertia::render('provider/Network', [
            'clinicalConnections'          => $clinical,
            'bpConnections'                => $business,
            'pendingRequests'              => $pending,
            'shadowConnections'            => $shadows,
            'referralCandidates'           => $referralCandidates,
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
                'bp_pending'       => NetworkRequestModel::where('requester_id', $user->id)
                    ->where('status', 'pending')
                    ->count(),
                'total_refs'       => $totalRefs,
                'avg_acc'          => $avgAcc,
                'avg_resp'         => $avgResp,
                'pending_requests' => $pending->count(),
                'active_shadows'   => $shadows->count(),
            ],
            'networkConfig'                => $this->loadNetworkConfig($user),
        ]);
    }

    // ── Network config helpers ────────────────────────────────────────────────

    private function loadNetworkConfig(User $user): array
    {
        // Force fresh load — cached relation may be stale after saves
        $user->load('meta');

        // Build a flat key→typed_value map using the model's own cast logic
        $meta = [];
        foreach ($user->meta as $row) {
            $meta[$row->meta_key] = $row->typed_value;
        }

        // Raw string values (for scalars stored as 'string' type)
        $rawMeta = $user->meta->pluck('meta_value', 'meta_key')->all();

        $jsonMeta = function (string $key, array $default = []) use ($meta): array {
            if (!array_key_exists($key, $meta)) return $default;
            $v = $meta[$key];
            return is_array($v) ? $v : $default;
        };

        $boolMeta = function (string $key, bool $default = false) use ($meta): bool {
            return array_key_exists($key, $meta) ? (bool)$meta[$key] : $default;
        };

        $profileMeta = $user->profile_meta ? (json_decode($user->profile_meta, true) ?: []) : [];

        // Specialties — stored as JSON or comma-string in users.specialty
        $specialty = [];
        if ($user->specialty) {
            $dec = json_decode($user->specialty, true);
            $specialty = is_array($dec)
                ? $dec
                : array_values(array_filter(array_map('trim', explode(',', $user->specialty))));
        }

        $aiSettings    = $jsonMeta('ai_shadow_settings');
        $notifications = $jsonMeta('cfg_notifications');
        $privacy       = $jsonMeta('cfg_privacy');

        return [
            // Selections
            'team'         => $jsonMeta('network_partners'),
            'specialties'  => $specialty,
            'approaches'   => $profileMeta['approaches'] ?? [],
            'insurance'    => is_array($ins = json_decode($user->network_insurance ?? '[]', true)) ? $ins : [],
            'credentials'  => $jsonMeta('cfg_credentials'),
            'services'     => $profileMeta['services']   ?? [],
            'states'       => $jsonMeta('licensed_states'),
            'demographics' => $jsonMeta('cfg_demographics'),
            'languages'    => $jsonMeta('languages'),
            'identity'     => $jsonMeta('cfg_identity'),
            'rates'        => $jsonMeta('cfg_rates'),
            // Scalar fields — use raw string values for these
            'license_number'     => $rawMeta['license_number']     ?? '',
            'primary_state'      => $rawMeta['primary_state']      ?? '',
            'years_in_practice'  => $rawMeta['years_in_practice']  ?? '',
            'session_length'     => $rawMeta['session_length']      ?? '',
            'rate_per_session'   => (int)($rawMeta['rate_per_session']  ?? 0),
            'sliding_scale_min'  => (int)($rawMeta['sliding_scale_min'] ?? 0),
            'sliding_scale_max'  => (int)($rawMeta['sliding_scale_max'] ?? 0),
            'max_partners'       => $rawMeta['max_partners']       ?? '',
            'geographic_radius'  => $rawMeta['geographic_radius']  ?? '',
            'referral_urgency'   => $rawMeta['referral_urgency']   ?? '',
            'ai_match_frequency' => $aiSettings['frequency']       ?? '',
            'sex_assigned'       => $rawMeta['sex_assigned']       ?? '',
            // Notifications
            'notifications' => $notifications ?: [
                'connection_requests' => $boolMeta('notify_connection', true),
                'referral_activity'   => $boolMeta('notify_referral',   true),
                'shadow_suggestions'  => $boolMeta('notify_shadow',     true),
                'member_news'         => $boolMeta('notify_news',       false),
                'read_receipts'       => $boolMeta('notify_read',       true),
                'weekly_digest'       => $boolMeta('notify_digest',     true),
                'feature_updates'     => $boolMeta('notify_features',   false),
            ],
            // Privacy
            'privacy' => $privacy ?: [
                'searchable'        => (bool)($user->practitioner_public ?? true),
                'share_stats'       => $boolMeta('privacy_share_stats',    true),
                'ai_matching'       => $boolMeta('privacy_ai_matching',    true),
                'manual_approval'   => $boolMeta('privacy_manual_approval',false),
                'hide_business'     => $boolMeta('privacy_hide_business',  false),
                'ai_data_use'       => $boolMeta('privacy_ai_data_use',    true),
                'show_demographics' => $boolMeta('privacy_show_demographics',true),
            ],
        ];
    }

    /**
     * Save all network config fields in one atomic request.
     */
    public function saveNetworkConfig(Request $request): RedirectResponse
    {
        $user = $request->user();
        $d    = $request->validate([
            'team'              => 'nullable|array',
            'specialties'       => 'nullable|array',
            'approaches'        => 'nullable|array',
            'insurance'         => 'nullable|array',
            'credentials'       => 'nullable|array',
            'services'          => 'nullable|array',
            'states'            => 'nullable|array',
            'demographics'      => 'nullable|array',
            'languages'         => 'nullable|array',
            'identity'          => 'nullable|array',
            'rates'             => 'nullable|array',
            'license_number'    => 'nullable|string|max:200',
            'primary_state'     => 'nullable|string|max:50',
            'years_in_practice' => 'nullable|string|max:50',
            'session_length'    => 'nullable|string|max:50',
            'rate_per_session'  => 'nullable|integer|min:0',
            'sliding_scale_min' => 'nullable|integer|min:0',
            'sliding_scale_max' => 'nullable|integer|min:0',
            'max_partners'      => 'nullable|string|max:50',
            'geographic_radius' => 'nullable|string|max:50',
            'referral_urgency'  => 'nullable|string|max:50',
            'ai_match_frequency'=> 'nullable|string|max:50',
            'sex_assigned'      => 'nullable|string|max:50',
            'notifications'     => 'nullable|array',
            'privacy'           => 'nullable|array',
        ]);

        /** @var \App\Services\ProfileService $ps */
        $ps = app(\App\Services\ProfileService::class);

        // Users table columns
        $userUpdate = [];
        if (isset($d['specialties'])) {
            $userUpdate['specialty'] = json_encode($d['specialties']);
        }
        if (isset($d['insurance'])) {
            $userUpdate['network_insurance'] = json_encode($d['insurance']);
        }
        // profile_meta JSON blob (approaches + services)
        $pm = $user->profile_meta ? (json_decode($user->profile_meta, true) ?: []) : [];
        if (isset($d['approaches'])) $pm['approaches'] = $d['approaches'];
        if (isset($d['services']))   $pm['services']   = $d['services'];
        $userUpdate['profile_meta'] = json_encode($pm);

        if ($userUpdate) $user->update($userUpdate);

        // Languages via dedicated method
        if (isset($d['languages'])) {
            $ps->updateLanguagesAndWebsite($user, $d['languages'], null);
        }

        // user_meta arrays
        foreach (['team'=>'network_partners','states'=>'licensed_states','credentials'=>'cfg_credentials',
                  'demographics'=>'cfg_demographics','identity'=>'cfg_identity','rates'=>'cfg_rates'] as $key=>$mk) {
            if (isset($d[$key])) $ps->setMetaPublic($user, $mk, $d[$key]);
        }

        // user_meta scalars
        foreach (['license_number','primary_state','years_in_practice','session_length',
                  'rate_per_session','sliding_scale_min','sliding_scale_max',
                  'max_partners','geographic_radius','referral_urgency','sex_assigned'] as $k) {
            if (array_key_exists($k, $d)) {
                $ps->setMetaPublic($user, $k, (string)($d[$k] ?? ''), 'string');
            }
        }

        // AI match frequency inside ai_shadow_settings
        if (isset($d['ai_match_frequency'])) {
            $row = \App\Models\UserMeta::where('user_id', $user->id)->where('meta_key', 'ai_shadow_settings')->first();
            $ai  = $row ? (json_decode($row->meta_value, true) ?: []) : [];
            $ai['frequency'] = $d['ai_match_frequency'];
            $ps->setMetaPublic($user, 'ai_shadow_settings', $ai);
        }

        if (isset($d['notifications'])) {
            $ps->setMetaPublic($user, 'cfg_notifications', $d['notifications']);
        }
        if (isset($d['privacy'])) {
            $ps->setMetaPublic($user, 'cfg_privacy', $d['privacy']);
            if (isset($d['privacy']['searchable'])) {
                $user->update(['practitioner_public' => (int)$d['privacy']['searchable']]);
            }
        }

        return back()->with('success', 'Network configuration saved.');
    }

    /**
     * Reset all network configuration to empty defaults.
     */
    public function resetNetworkConfig(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var \App\Services\ProfileService $ps */
        $ps = app(\App\Services\ProfileService::class);

        foreach (['network_partners','licensed_states','cfg_credentials','cfg_demographics',
                  'cfg_identity','cfg_rates','languages','cfg_notifications','cfg_privacy',
                  'ai_shadow_settings'] as $k) {
            $ps->setMetaPublic($user, $k, []);
        }
        foreach (['license_number','primary_state','years_in_practice','session_length',
                  'rate_per_session','sliding_scale_min','sliding_scale_max',
                  'max_partners','geographic_radius','referral_urgency','sex_assigned'] as $k) {
            $ps->setMetaPublic($user, $k, '', 'string');
        }

        $pm = $user->profile_meta ? (json_decode($user->profile_meta, true) ?: []) : [];
        $pm['approaches'] = [];
        $pm['services']   = [];
        $user->update([
            'specialty'         => json_encode([]),
            'network_insurance' => json_encode([]),
            'profile_meta'      => json_encode($pm),
        ]);

        return back()->with('success', 'Configuration reset to defaults.');
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
