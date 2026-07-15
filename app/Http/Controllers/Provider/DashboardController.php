<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\CriticalIncident;
use App\Models\NetworkConnection;
use App\Models\PlanSteward;
use App\Models\CeuEntry;
use App\Models\CeuRequirement;
use App\Models\ProviderCredential;
use App\Models\Referral;
use App\Models\User;
use App\Models\VaultItem;
use App\Services\ActivityService;
use App\Services\CeuService;
use App\Services\IncidentService;
use App\Services\PlanService;
use App\Enums\StewardStatus;
use App\Enums\StewardRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private PlanService $plans,
        private IncidentService $incidents,
        private CeuService $ceus,
        private ActivityService $activity,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);

        $stewards = $plan
            ? PlanSteward::where('plan_id', $plan->id)
                ->whereIn('status', ['active', 'pending'])
                ->with('steward')
                ->get()
            : collect();

        // status and role are enum-cast — filter on .value for collection where()
        $activeCs = $stewards->filter(fn ($s) => $s->steward_category === 'continuity_steward'
            && $s->status instanceof \App\Enums\StewardStatus && $s->status === StewardStatus::Active);
        $activeSs = $stewards->filter(fn ($s) => $s->steward_category === 'support_steward'
            && $s->status instanceof \App\Enums\StewardStatus && $s->status === StewardStatus::Active);

        $primaryCs = $activeCs->first(fn ($s) => $s->role === StewardRole::Primary) ?? $activeCs->first();
        $primarySs = $activeSs->first(fn ($s) => $s->role === StewardRole::Primary) ?? $activeSs->first();

        $activeIncident = CriticalIncident::where('practitioner_id', $user->id)
            ->whereIn('status', ['reported', 'verified', 'active'])
            ->orderByDesc('reported_at')
            ->first();

        $progress = $this->ceus->getProgress($user->id);

        // Network connections (clinical = practitioner, business = bp)
        $connections = NetworkConnection::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('target')
            ->get();
        $netClinical = $connections->filter(fn ($c) => $c->connection_type?->value === 'practitioner')->values();
        $netBusiness = $connections->filter(fn ($c) => $c->connection_type?->value === 'business_partner')->values();

        // Incoming referrals pending response
        $incomingReferrals = Referral::where('recipient_id', $user->id)
            ->where('status', 'sent')
            ->with('sender')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // ── ReferralModal data: roster (vault clients) + clinical network ─────
        $referralRoster = VaultItem::where('practitioner_id', $user->id)
            ->where('zone', 'roster')
            ->whereNotNull('client_name')
            ->get()
            ->map(function ($v) {
                return [
                    'id'              => $v->id,
                    'client_name'     => $v->client_name,
                    'client_service'  => $v->category,
                    'client_location' => null,
                    'client_notes'    => $v->sub_label,
                    'client_status'   => $v->status?->value ?? $v->status,
                ];
            })
            ->sortBy(fn ($r) => $r['client_status'] === 'priority' ? 0 : 1)
            ->values()
            ->toArray();

        $referralNetwork = NetworkConnection::where('user_id', $user->id)
            ->where('connection_type', 'practitioner')
            ->where('status', 'active')
            ->with('target')
            ->get()
            ->filter(fn ($c) => $c->target && $c->target->slug)
            ->map(function ($c) {
                $t = $c->target;
                $initials = $t->avatar_initials ?: strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $t->display_name ?? ''), 0, 2));
                return [
                    'id'           => $t->id,
                    'display_name' => $t->display_name,
                    'credentials'  => $t->credentials,
                    'specialty'    => $t->specialty,
                    'location'     => $t->location,
                    'slug'         => $t->slug,
                    'accepting'    => true,
                    'initials'     => $initials,
                ];
            })
            ->values()
            ->toArray();

        // Credentials — pull from provider_credentials table, compute days_remaining
        $credentials = ProviderCredential::where('user_id', $user->id)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($c) {
                return [
                    'id'             => $c->id,
                    'cred_type'      => $c->cred_type,
                    'icon'           => $c->icon,
                    'name'           => $c->name,
                    'subtitle'       => $c->subtitle,
                    'issuer'         => $c->issuer,
                    'number'         => $c->number,
                    'issued_on'      => $c->issued_on?->toDateString(),
                    'expires_on'     => $c->expires_on?->toDateString(),
                    'days_remaining' => $c->days_remaining,
                    'is_insurance'   => $c->is_insurance,
                    'document_paths' => $c->document_path ? (json_decode($c->document_path, true) ?? [$c->document_path]) : [],
                ];
            })
            ->toArray();

        // Attest summary for plan status chips
        // Use enum-safe closures — status/role are cast to StewardStatus/StewardRole enums
        $planStatusValue = $plan?->status?->value ?? 'none';

        $csStewards = $stewards->filter(fn ($s) => $s->steward_category === 'continuity_steward');
        $ssStewards = $stewards->filter(fn ($s) => $s->steward_category === 'support_steward');
        $csActive   = $csStewards->filter(fn ($s) => $s->status === StewardStatus::Active);
        $ssActive   = $ssStewards->filter(fn ($s) => $s->status === StewardStatus::Active);

        $attest = [
            'plan_active'       => $plan && in_array($planStatusValue, ['active', 'annual_review_due']),
            'plan_review_due'   => $plan && $planStatusValue === 'annual_review_due',
            'plan_status'       => $planStatusValue,
            'plan_signed_at'    => $plan?->signed_at?->toDateString(),
            'annual_review_date'=> $plan?->annual_review_date?->toDateString(),
            'ss_certified'      => $ssActive->count() > 0,
            'ss_assigned'       => $ssStewards->count() > 0,
            'ss_certified_count'=> $ssActive->count(),
            'ss_total'          => $ssStewards->count(),
            'ss_latest'         => $primarySs?->updated_at,
            'cs_certified'      => $csActive->count() > 0,
            'cs_assigned'       => $csStewards->count() > 0,
            'cs_certified_count'=> $csActive->count(),
            'cs_total'          => $csStewards->count(),
            'cs_latest'         => $primaryCs?->updated_at,
        ];

        $reviewDue = $plan?->annual_review_date;
        $reviewDays = $reviewDue ? (int) now()->diffInDays($reviewDue, false) : 0;

        return Inertia::render('Provider/Dashboard', [
            'user'               => $user,
            'planStatus'         => $plan?->status ?? 'none',
            'plan'               => $plan,
            'attest'             => $attest,
            'activeStewardCount' => $stewards->filter(fn ($s) => $s->status === StewardStatus::Active)->count(),
            'maatActive'         => (bool) ($user->meta['maat_cs_active'] ?? false),
            'reviewDays'         => $reviewDays,
            'stats'              => [
                'active_plans'     => $attest['plan_active'] ? 1 : 0,
                'ceus_total'       => $progress['total'],
                'ceus_count'       => $progress['count'],
                'active_incidents' => CriticalIncident::where('practitioner_id', $user->id)->whereIn('status', ['reported','verified','active'])->count(),
                'pending_refs'     => $incomingReferrals->count(),
                'total_refs'       => Referral::where('recipient_id', $user->id)->count(),
                'avg_response_h'   => 0,
                'net_clinical'     => $netClinical->count(),
                'net_business'     => $netBusiness->count(),
            ],
            'activeIncident'     => $activeIncident,
            'annualReviewDate'   => $plan?->annual_review_date?->toISOString() ?? null,
            'lastAttestedAt'     => $plan?->vault_attested_at?->toISOString() ?? null,
            'signedAt'           => $plan?->signed_at?->toISOString() ?? null,
            'planVersion'        => $plan?->plan_version ?? null,
            'hasDraftInProgress' => \App\Models\ContinuityPlan::where('practitioner_id', $user->id)->where('status', 'draft')->exists(),
            'planSections'       => $plan ? app(\App\Services\PlanService::class)->computeSections($plan) : [],
            'continuityStewards' => $csStewards->values(),
            'supportStewards'    => $ssStewards->values(),
            'primaryCs'          => $primaryCs,
            'primarySs'          => $primarySs,
            'netClinical'        => $netClinical,
            'netBusiness'        => $netBusiness,
            'incomingReferrals'  => $incomingReferrals,
            'referralRoster'     => $referralRoster,
            'referralNetwork'    => $referralNetwork,
            'credentials'        => $credentials,
            '_debug'             => [
                'plan_id'     => $plan?->id,
                'plan_status' => $planStatusValue,
                'steward_rows'=> $stewards->map(fn ($s) => [
                    'id'       => $s->id,
                    'category' => $s->steward_category,
                    'status'   => $s->status?->value ?? $s->status,
                    'role'     => $s->role?->value ?? $s->role,
                ])->toArray(),
                'cs_active_count' => $csActive->count(),
                'ss_active_count' => $ssActive->count(),
                'cs_certified'    => $attest['cs_certified'],
                'ss_certified'    => $attest['ss_certified'],
                'cs_assigned'     => $attest['cs_assigned'],
                'ss_assigned'     => $attest['ss_assigned'],
                'primaryCs_status'=> $primaryCs?->status?->value,
                'primarySs_status'=> $primarySs?->status?->value,
            ],
            'recentActivity'     => $this->activity->getForUser($user->id, [], 10),
            'upcomingCEUs'       => CeuEntry::where('practitioner_id', $user->id)
                                        ->orderByDesc('completed_on')->limit(5)->get(),
            'ceuRequirements'    => CeuRequirement::where('user_id', $user->id)
                                        ->orderBy('due_date')
                                        ->get(),
        ]);
    }
}
