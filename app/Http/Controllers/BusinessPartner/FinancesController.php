<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Models\BpContract;
use App\Models\BpEscrowLedger;
use App\Models\BpMilestone;
use App\Models\BpPayout;
use App\Enums\MilestoneStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Wave 8: Full BP Finances rebuild.
 *
 * Props returned:
 *   summary         — lifetime_cents, year_cents, pending_cents, escrow_held_cents,
 *                     released_this_year, payout_method, stripe_connected
 *   payouts         — full payout history (status, amount, dates)
 *   activeContracts — contracts with escrow breakdown per milestone
 *   pendingMilestones — milestones awaiting funding or review (surfaced in upcoming section)
 *   escrowLedger    — recent 20 escrow events for transparency
 */
class FinancesController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $bp   = $user;

        // ── Lifetime earned (released payouts) ────────────────────────────────
        $lifetimeCents = BpPayout::where('bp_id', $bp->id)
            ->where('status', 'paid')
            ->sum('amount_cents');

        // ── YTD earned ────────────────────────────────────────────────────────
        $yearCents = BpPayout::where('bp_id', $bp->id)
            ->where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->sum('amount_cents');

        // ── In-escrow (funded milestones not yet released) ────────────────────
        // = sum of funded_cents on milestone statuses that hold escrow
        $escrowHeldCents = BpMilestone::whereHas('contract', fn ($q) => $q->where('bp_id', $bp->id))
            ->whereIn('status', [
                MilestoneStatus::Funded->value,
                MilestoneStatus::InProgress->value,
                MilestoneStatus::Submitted->value,
                MilestoneStatus::RevisionRequested->value,
                MilestoneStatus::Approved->value,
                MilestoneStatus::Disputed->value,
            ])
            ->sum('funded_cents');

        // ── Pending (submitted, awaiting approval) ────────────────────────────
        $pendingCents = BpMilestone::whereHas('contract', fn ($q) => $q->where('bp_id', $bp->id))
            ->where('status', MilestoneStatus::Submitted->value)
            ->sum('funded_cents');

        // ── Payout method ─────────────────────────────────────────────────────
        $stripeConnected = (bool) $bp->stripe_connected;
        $payoutMethod    = $stripeConnected ? 'Stripe Connect' : 'Not set';

        // ── Payout history ────────────────────────────────────────────────────
        $payouts = BpPayout::where('bp_id', $bp->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function (BpPayout $p) {
                $status = $p->status ?? 'pending';
                return [
                    'id'            => $p->id,
                    'amount_cents'  => (int) $p->amount_cents,
                    'status'        => $status,
                    'description'   => $p->description,
                    'milestone_id'  => $p->milestone_id,
                    'contract_id'   => $p->contract_id,
                    'created_at'    => $p->created_at?->format('M j, Y'),
                    'paid_at'       => $p->paid_at?->format('M j, Y'),
                    'released_at'   => $p->released_at?->format('M j, Y'),
                    // Refund info
                    'refunded_cents'=> (int) ($p->refunded_cents ?? 0),
                    'refunded_at'   => $p->refunded_at?->format('M j, Y'),
                ];
            });

        // ── Active contracts with milestone escrow breakdown ──────────────────
        $activeContracts = BpContract::where('bp_id', $bp->id)
            ->whereIn('status', ['pending_signature', 'pending_funding', 'active'])
            ->with(['practitioner:id,display_name', 'milestones'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (BpContract $c) {
                $statusVal      = $c->status instanceof \BackedEnum ? $c->status->value : (string) $c->status;
                $escrowFunded   = (int) ($c->escrow_funded_cents ?? 0);
                $escrowReleased = (int) ($c->escrow_released_cents ?? 0);
                $escrowRefunded = (int) ($c->escrow_refunded_cents ?? 0);
                $escrowHeld     = max(0, $escrowFunded - $escrowReleased - $escrowRefunded);

                $milestones = $c->milestones->sortBy('sort_order')->map(fn (\App\Models\BpMilestone $m) => [
                    'id'              => $m->id,
                    'title'           => $m->title,
                    'amount_cents'    => (int) $m->amount_cents,
                    'funded_cents'    => (int) ($m->funded_cents ?? 0),
                    'released_cents'  => (int) ($m->released_cents ?? 0),
                    'status'          => $m->status instanceof \BackedEnum ? $m->status->value : (string) $m->status,
                    'due_at'          => $m->due_at?->toDateString(),
                    'auto_release_at' => $m->auto_release_at?->toDateString(),
                    'auto_release_iso'=> $m->auto_release_at?->toISOString(),
                    'revision_count'  => (int) ($m->revision_count ?? 0),
                ])->values();

                return [
                    'id'                    => $c->id,
                    'title'                 => $c->title,
                    'client_name'           => $c->practitioner?->display_name ?? '—',
                    'practitioner_id'       => $c->practitioner_id,
                    'status'                => $statusVal,
                    'payment_type'          => $c->payment_type ?? 'one_time',
                    'total_value_cents'     => (int) $c->total_value_cents,
                    'escrow_funded_cents'   => $escrowFunded,
                    'escrow_released_cents' => $escrowReleased,
                    'escrow_refunded_cents' => $escrowRefunded,
                    'escrow_held_cents'     => $escrowHeld,
                    'provider_has_signed'   => (bool) $c->practitioner_signed_at,
                    'bp_has_signed'         => (bool) $c->bp_signed_at,
                    'created_at'            => $c->created_at?->toDateString(),
                    'milestones'            => $milestones,
                ];
            })->values();

        // ── Pending milestones (awaiting fund or approval — surface in upcoming) ─
        $pendingMilestones = BpMilestone::whereHas('contract', fn ($q) => $q->where('bp_id', $bp->id))
            ->whereIn('status', [
                MilestoneStatus::Funded->value,
                MilestoneStatus::InProgress->value,
                MilestoneStatus::Submitted->value,
                MilestoneStatus::RevisionRequested->value,
            ])
            ->with('contract:id,title,practitioner_id')
            ->orderBy('due_at')
            ->limit(20)
            ->get()
            ->map(fn (\App\Models\BpMilestone $m) => [
                'id'              => $m->id,
                'title'           => $m->title,
                'amount_cents'    => (int) $m->amount_cents,
                'funded_cents'    => (int) ($m->funded_cents ?? 0),
                'status'          => $m->status instanceof \BackedEnum ? $m->status->value : (string) $m->status,
                'due_at'          => $m->due_at?->toDateString(),
                'auto_release_at' => $m->auto_release_at?->toDateString(),
                'contract_id'     => $m->contract_id,
                'contract_title'  => $m->contract?->title ?? '—',
                'practitioner_id' => $m->contract?->practitioner_id,
            ]);

        // ── Recent escrow ledger (transparency) ───────────────────────────────
        $escrowLedger = BpEscrowLedger::where('bp_id', $bp->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (\App\Models\BpEscrowLedger $l) => [
                'id'          => $l->id,
                'kind'        => $l->kind,       // fund | release | refund | split_refund
                'amount_cents'=> (int) $l->amount_cents,
                'description' => $l->description,
                'created_at'  => $l->created_at?->format('M j, Y g:i A'),
            ]);

        // ── Stripe Connect status ─────────────────────────────────────────────
        $stripeAccountId = $bp->stripe_account_id;
        $isDemo          = $stripeAccountId && str_starts_with($stripeAccountId, 'acct_demo_');

        return Inertia::render('business-partner/Finances', [
            'summary' => [
                'lifetime_cents'   => (int) $lifetimeCents,
                'year_cents'       => (int) $yearCents,
                'pending_cents'    => (int) $pendingCents,
                'escrow_held_cents'=> (int) $escrowHeldCents,
                'payout_method'    => $payoutMethod,
            ],
            'payouts'           => $payouts,
            'activeContracts'   => $activeContracts,
            'pendingMilestones' => $pendingMilestones,
            'escrowLedger'      => $escrowLedger,
            'stripe_connected'  => $stripeConnected,
            'stripe_demo'       => $isDemo,
            'stripe_account_id' => $stripeAccountId,
        ]);
    }
}
