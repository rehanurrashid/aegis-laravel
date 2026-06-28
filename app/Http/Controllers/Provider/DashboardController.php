<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\CriticalIncident;
use App\Models\PlanSteward;
use App\Models\CeuEntry;
use App\Services\ActivityService;
use App\Services\CeuService;
use App\Services\PlanService;
use App\Services\NetworkService;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private PlanService     $plans,
        private CeuService      $ceus,
        private ActivityService $activity,
        private NetworkService  $network,
        private ReferralService $referrals,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);

        // ── Stewards ──────────────────────────────────────────────────────
        $stewards = $plan
            ? PlanSteward::where('plan_id', $plan->id)
                ->whereIn('status', ['active', 'pending'])
                ->with('steward')
                ->get()
            : collect();

        $continuityStewards = $stewards->where('steward_type', 'continuity_steward')->values();
        $supportStewards    = $stewards->where('steward_type', 'support_steward')->values();

        // Primary CS / SS for the continuity hero card
        $primaryCs = $continuityStewards->first();
        $primarySs = $supportStewards->first();

        // Active steward count (max 5)
        $activeStewardCount = min(5, $stewards->where('status', 'active')->count());

        // ── Active incident ───────────────────────────────────────────────
        $activeIncident = CriticalIncident::where('practitioner_id', $user->id)
            ->where('status', 'active')
            ->first();

        // ── CEUs ──────────────────────────────────────────────────────────
        $progress    = $this->ceus->getProgress($user->id);
        $upcomingCEUs = CeuEntry::where('practitioner_id', $user->id)
            ->orderByDesc('completed_on')->limit(5)->get();

        // ── Attestation state for the 3 chips ─────────────────────────────
        $attest = $this->getAttestationState($plan, $continuityStewards, $supportStewards);

        // ── Network ───────────────────────────────────────────────────────
        $connections  = $this->network->getConnections($user->id);
        $netClinical  = $connections->filter(fn($c) => ($c->target->portal ?? '') === 'provider')
                            ->take(6)->values();
        $netBusiness  = $connections->filter(fn($c) => ($c->target->portal ?? '') === 'business_partner')
                            ->take(4)->values();

        // ── Referrals ─────────────────────────────────────────────────────
        $pendingRefs  = $this->referrals->inbox($user, 'pending')->count();
        $totalRefs    = $this->referrals->sent($user)->count() + $pendingRefs;

        // Avg response time (hours) — simple heuristic from accepted referrals
        $avgResponseH = 0;

        // ── MAAT add-on flag ──────────────────────────────────────────────
        $maatActive = (bool) ($user->maat_addon ?? false);

        // ── Review days remaining ─────────────────────────────────────────
        $reviewDays = 0;
        if ($plan?->annual_review_date) {
            $reviewDays = (int) ceil(
                ($plan->annual_review_date->timestamp - now()->timestamp) / 86400
            );
        }

        return Inertia::render('Provider/Dashboard', [
            'user'               => $user,
            'planStatus'         => $plan?->status?->value ?? 'none',
            'plan'               => $plan,
            'attest'             => $attest,
            'stats'              => [
                'active_plans'     => $plan && in_array($plan->status?->value, ['active', 'annual_review_due']) ? 1 : 0,
                'ceus_total'       => $progress['total'],
                'ceus_count'       => $progress['count'],
                'active_incidents' => $activeIncident ? 1 : 0,
                'pending_refs'     => $pendingRefs,
                'total_refs'       => $totalRefs,
                'avg_response_h'   => $avgResponseH,
                'net_clinical'     => $netClinical->count(),
                'net_business'     => $netBusiness->count(),
            ],
            'activeStewardCount' => $activeStewardCount,
            'maatActive'         => $maatActive,
            'reviewDays'         => $reviewDays,
            'activeIncident'     => $activeIncident,
            'continuityStewards' => $continuityStewards,
            'supportStewards'    => $supportStewards,
            'primaryCs'          => $primaryCs,
            'primarySs'          => $primarySs,
            'netClinical'        => $netClinical,
            'netBusiness'        => $netBusiness,
            'recentActivity'     => $this->activity->getForUser($user->id, [], 10),
            'upcomingCEUs'       => $upcomingCEUs,
        ]);
    }

    // ── Attestation helpers ───────────────────────────────────────────────
    private function getAttestationState($plan, $continuityStewards, $supportStewards): array
    {
        if (!$plan) {
            return [
                'plan_active'       => false,
                'plan_signed_at'    => null,
                'ss_certified'      => false,
                'ss_certified_count'=> 0,
                'ss_total'          => 0,
                'ss_latest'         => null,
                'cs_certified'      => false,
                'cs_certified_count'=> 0,
                'cs_total'          => 0,
                'cs_latest'         => null,
            ];
        }

        $ssSigned = $supportStewards->where('signed_at', '!=', null);
        $csSigned = $continuityStewards->where('signed_at', '!=', null);

        return [
            'plan_active'        => $plan->signed_at !== null,
            'plan_signed_at'     => $plan->signed_at?->toDateTimeString(),
            'ss_certified'       => $ssSigned->isNotEmpty(),
            'ss_certified_count' => $ssSigned->count(),
            'ss_total'           => max(1, $supportStewards->count()),
            'ss_latest'          => $ssSigned->max('signed_at'),
            'cs_certified'       => $csSigned->isNotEmpty(),
            'cs_certified_count' => $csSigned->count(),
            'cs_total'           => max(1, $continuityStewards->count()),
            'cs_latest'          => $csSigned->max('signed_at'),
        ];
    }
}
