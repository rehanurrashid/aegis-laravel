<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Models\BpInvoice;
use App\Models\BpMilestone;
use App\Models\BpPayout;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancesController extends Controller
{
    public function index(Request $request): Response
    {
        $user  = $request->user();
        $bpId  = $user->id;
        $year  = now()->year;

        // --- Summary stats ---
        $lifetimeCents = BpPayout::where('bp_id', $bpId)
            ->where('status', 'paid')
            ->sum('amount_cents');

        $yearCents = BpPayout::where('bp_id', $bpId)
            ->where('status', 'paid')
            ->whereYear('paid_at', $year)
            ->sum('amount_cents');

        // Pending = milestones that are submitted/approved but not yet released
        $pendingCents = BpMilestone::whereHas('contract', fn ($q) => $q->where('bp_id', $bpId))
            ->whereIn('status', ['submitted', 'approved', 'funded', 'in_progress'])
            ->sum('amount_cents');

        // Payout method — Stripe Connect onboarded?
        $payoutMethod = null;
        if ($user->stripe_account_id && !str_starts_with((string) $user->stripe_account_id, 'acct_demo_')) {
            $payoutMethod = 'Stripe Connect';
        }

        // Stripe Connect connected status
        $stripeConnected = $user->stripe_connected ?? false;

        // --- Payout history (last 50) ---
        $payouts = BpPayout::where('bp_id', $bpId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'amount_cents'  => (int) $p->amount_cents,
                'status'        => $p->status instanceof \BackedEnum ? $p->status->value : (string) $p->status,
                'description'   => $p->description,
                'created_at'    => $p->created_at?->toDateString(),
                'paid_at'       => $p->paid_at?->toDateString(),
                'refunded_cents'=> (int) ($p->refunded_cents ?? 0),
                'refunded_at'   => $p->refunded_at?->toDateString(),
            ]);

        return Inertia::render('BusinessPartner/Finances', [
            'summary' => [
                'lifetime_cents' => (int) $lifetimeCents,
                'year_cents'     => (int) $yearCents,
                'pending_cents'  => (int) $pendingCents,
                'payout_method'  => $payoutMethod,
            ],
            'stripe_connected' => (bool) $stripeConnected,
            'payouts'          => $payouts,
        ]);
    }
}
