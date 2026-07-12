<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Enums\ActivitySeverity;
use App\Enums\ContractStatus;
use App\Enums\DisputeStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PayoutStatus;
use App\Enums\PractitionerPaymentKind;
use App\Enums\PractitionerPaymentStatus;
use App\Enums\ServiceSessionPaymentStatus;
use App\Enums\ServiceSessionStatus;
use App\Enums\StewardStatus;
use App\Http\Controllers\Controller;
use App\Models\BpContract;
use App\Models\BpInvoice;
use App\Models\ContinuityPlan;
use App\Models\CsInvoice;
use App\Models\CsPayout;
use App\Models\Dispute;
use App\Models\PlanSteward;
use App\Models\PractitionerPayment;
use App\Models\ServiceSession;
use App\Models\SessionRefundRequest;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\DisputeService;
use App\Services\PayoutService;
use App\Services\ServiceService;
use App\Services\SessionRefundService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Provider Finances Controller.
 *
 * Wave 3 changes:
 *  - clientSessions now uses ServiceService::shapeClientSession() with full payment breakdown
 *  - providerSessions NEW: sessions where I am the practitioner receiving payment
 *  - sessionRefundRequests: both incoming (as provider) + outgoing (as client)
 *  - sessionPendingCount now counts sessions where client owes money (deposit OR balance due)
 *  - Transaction history expanded with session_deposit/session_balance kinds
 *  - Spend breakdown updated to include deposit+balance revenue
 */
class FinancesController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptions,
        private PayoutService       $payouts,
        private ActivityService     $activity,
        private DisputeService      $disputes,
        private ServiceService      $services,
        private SessionRefundService $sessionRefunds,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // ── 1. CS INVOICES ───────────────────────────────────────────────────
        $csInvCollection = CsInvoice::where('practitioner_id', $user->id)
            ->with(['cs:id,display_name,slug,stripe_connected'])
            ->orderByDesc('created_at')->limit(100)->get();

        $csInvIds     = $csInvCollection->pluck('id')->all();
        $csDisputeMap = Dispute::whereIn('subject_id', $csInvIds)
            ->where('subject_type', 'cs_invoice')
            ->whereNull('resolved_at')
            ->pluck('id', 'subject_id');

        $csInvoices = $csInvCollection->map(function (CsInvoice $inv) use ($csDisputeMap) {
            $status = $inv->status instanceof InvoiceStatus ? $inv->status->value : (string) $inv->status;
            return [
                'id'               => $inv->id,
                'invoice_number'   => $inv->invoice_number ?? substr($inv->id, 0, 10),
                'cs_name'          => $inv->cs?->display_name ?? '—',
                'cs_slug'          => $inv->cs?->slug,
                'cs_connected'     => (bool) ($inv->cs?->stripe_connected ?? false),
                'total_cents'      => (int) $inv->total_cents,
                'status'           => $status,
                'issued_at'        => $inv->issued_at?->toDateString(),
                'issued_month'     => $inv->issued_at?->format('F Y'),
                'due_at'           => $inv->due_at?->format('M j, Y'),
                'payable'          => in_array($status, [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value], true),
                'active_dispute_id'=> $csDisputeMap[$inv->id] ?? null,
                'kind'             => 'cs_invoice',
            ];
        })->values();

        // ── 2. BP INVOICES ───────────────────────────────────────────────────
        $bpInvCollection = BpInvoice::where('practitioner_id', $user->id)
            ->with(['bp:id,display_name,slug,stripe_connected', 'contract:id,title'])
            ->orderByDesc('created_at')->limit(100)->get();

        $bpInvIds     = $bpInvCollection->pluck('id')->all();
        $bpDisputeMap = Dispute::whereIn('subject_id', $bpInvIds)
            ->where('subject_type', 'bp_invoice')
            ->whereNull('resolved_at')
            ->pluck('id', 'subject_id');

        $bpInvoices = $bpInvCollection->map(function (BpInvoice $inv) use ($bpDisputeMap) {
            $status = $inv->status instanceof InvoiceStatus ? $inv->status->value : (string) $inv->status;
            return [
                'id'               => $inv->id,
                'invoice_number'   => $inv->invoice_number ?? substr($inv->id, 0, 10),
                'bp_name'          => $inv->bp?->display_name ?? '—',
                'bp_slug'          => $inv->bp?->slug,
                'bp_connected'     => (bool) ($inv->bp?->stripe_connected ?? false),
                'contract_title'   => $inv->contract?->title ?? $inv->notes ?? '—',
                'total_cents'      => (int) $inv->total_cents,
                'status'           => $status,
                'issued_at'        => $inv->issued_at?->toDateString(),
                'issued_month'     => $inv->issued_at?->format('F Y'),
                'due_at'           => $inv->due_at?->format('M j, Y'),
                'notes_short'      => $inv->notes ? mb_strimwidth($inv->notes, 0, 60, '…') : null,
                'payable'          => in_array($status, [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value], true),
                'active_dispute_id'=> $bpDisputeMap[$inv->id] ?? null,
                'paid_at'          => $inv->paid_at?->toDateString(),
                'kind'             => 'bp_invoice',
            ];
        })->values();

        // ── 3. CLIENT SESSIONS — I am the CLIENT (paying other practitioners) ─
        $clientSessionsPaginator = ServiceSession::where('client_id', $user->id)
            ->with(['practitioner:id,display_name,slug,stripe_connected,stripe_account_id', 'service:id,title'])
            ->orderByDesc('scheduled_at')
            ->paginate(20);

        $clientSessions = collect($clientSessionsPaginator->items())
            ->map(fn(ServiceSession $s) => $this->services->shapeClientSession($s))
            ->values();

        // ── 4. PROVIDER SESSIONS — I am the PROVIDER (receiving payment) ──────
        // NEW in Wave 3: show revenue-side sessions separately
        $providerSessionsPaginator = ServiceSession::where('practitioner_id', $user->id)
            ->with(['client:id,display_name,slug', 'service:id,title'])
            ->whereNotIn('status', [ServiceSessionStatus::Cancelled->value])
            ->orderByDesc('scheduled_at')
            ->paginate(20);

        $providerSessions = collect($providerSessionsPaginator->items())
            ->map(fn(ServiceSession $s) => $this->services->shapeSession($s))
            ->values();

        // ── 5. SESSION REFUND REQUESTS ────────────────────────────────────────
        // Incoming: I am the provider being asked for a refund
        $incomingRefundRequests = SessionRefundRequest::where('provider_id', $user->id)
            ->with(['session.service', 'requester'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(SessionRefundRequest $r) => $this->shapeRefundRequest($r, 'provider'))
            ->values();

        // Outgoing: I am the client who submitted a refund request
        $outgoingRefundRequests = SessionRefundRequest::where('requested_by_id', $user->id)
            ->with(['session.service', 'provider'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(SessionRefundRequest $r) => $this->shapeRefundRequest($r, 'client'))
            ->values();

        // ── 6. Merged invoices ledger ─────────────────────────────────────────
        $clientSessionsForLedger = $clientSessions->map(fn ($s) => array_merge($s, [
            'kind'          => 'session',
            'invoice_number'=> $s['invoice_number'] ?? 'SES-' . strtoupper(substr($s['id'], 0, 8)),
            'payable'       => in_array($s['payment_status'] ?? '', ['unpaid', 'deposit_paid'], true)
                               && ($s['status'] ?? '') === 'scheduled',
        ]));

        $allInvoices = collect()
            ->concat($csInvoices)
            ->concat($bpInvoices)
            ->concat($clientSessionsForLedger)
            ->values();

        // ── 7. BP Contracts (Wave 8: include pending states + escrow cols + milestones) ─
        $activeBpContracts = BpContract::where('practitioner_id', $user->id)
            ->whereIn('status', [
                \App\Enums\ContractStatus::PendingSignature->value,
                \App\Enums\ContractStatus::PendingFunding->value,
                ContractStatus::Active->value,
            ])
            ->with([
                'bp:id,display_name,slug,stripe_connected,stripe_account_id',
                'milestones',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (BpContract $con) {
                $statusVal = $con->status instanceof ContractStatus ? $con->status->value : (string) $con->status;
                $lastPaid  = BpInvoice::where('contract_id', $con->id)
                    ->where('status', InvoiceStatus::Paid->value)
                    ->orderByDesc('paid_at')->value('paid_at');

                $escrowFunded   = (int) ($con->escrow_funded_cents ?? 0);
                $escrowReleased = (int) ($con->escrow_released_cents ?? 0);
                $escrowRefunded = (int) ($con->escrow_refunded_cents ?? 0);
                $escrowHeld     = max(0, $escrowFunded - $escrowReleased - $escrowRefunded);
                $unfundedCents  = max(0, (int) $con->total_value_cents - $escrowFunded);

                // Per-milestone funding status
                $milestones = $con->milestones->sortBy('sort_order')->map(fn (\App\Models\BpMilestone $m) => [
                    'id'               => $m->id,
                    'title'            => $m->title,
                    'amount_cents'     => (int) $m->amount_cents,
                    'funded_cents'     => (int) ($m->funded_cents ?? 0),
                    'status'           => $m->status instanceof \BackedEnum ? $m->status->value : (string) $m->status,
                    'due_at'           => $m->due_at?->toDateString(),
                    'auto_release_at'  => $m->auto_release_at?->toDateString(),
                    // Fields needed for Finances milestone review UX
                    'submitted_at'     => $m->submitted_at?->toDateString(),
                    'revision_notes'   => $m->revision_notes,
                    'revision_count'   => (int) ($m->revision_count ?? 0),
                    'latest_submission'=> null, // MilestoneReviewModal reads this for submission detail
                ])->values();

                return [
                    'id'                   => $con->id,
                    'title'                => $con->title,
                    'bp_name'              => $con->bp?->display_name ?? '—',
                    'bp_slug'              => $con->bp?->slug,
                    'bp_connected'         => (bool) ($con->bp?->stripe_connected ?? false),
                    'bp_stripe_account_id' => $con->bp?->stripe_account_id,
                    'total_cents'          => (int) $con->total_value_cents,
                    'billing_type'         => $con->payment_type ?? 'one_time',
                    'billing_type_label'   => match($con->payment_type ?? 'one_time') {
                        'milestone' => 'Milestone-based',
                        'retainer'  => 'Monthly retainer',
                        default     => 'One-time'
                    },
                    'term'                 => ($con->started_at?->format('M Y') ?? '—') . ' – ' . ($con->completed_at?->format('M Y') ?? 'Ongoing'),
                    'last_paid'            => $lastPaid ? Carbon::parse($lastPaid)->format('M j, Y') : null,
                    'autopay_enabled'      => false,
                    'status'               => $statusVal,
                    'kind'                 => 'bp',
                    // Escrow fields (Wave 8)
                    'escrow_funded_cents'  => $escrowFunded,
                    'escrow_released_cents'=> $escrowReleased,
                    'escrow_refunded_cents'=> $escrowRefunded,
                    'escrow_held_cents'    => $escrowHeld,
                    'unfunded_cents'       => $unfundedCents,
                    'funding_mode'         => $con->funding_mode ?? 'per_milestone',
                    'provider_has_signed'  => (bool) $con->practitioner_signed_at,
                    'bp_has_signed'        => (bool) $con->bp_signed_at,
                    'milestones'           => $milestones,
                ];
            })->values();

        // ── 8. CS Stewards / wallet ───────────────────────────────────────────
        $plan       = ContinuityPlan::where('practitioner_id', $user->id)->first();
        $csStewards = collect();
        if ($plan) {
            $csStewards = PlanSteward::where('plan_id', $plan->id)
                ->where('steward_category', 'continuity_steward')
                ->where('status', StewardStatus::Active->value)
                ->with(['steward:id,display_name,slug,stripe_connected,stripe_account_id'])
                ->get()
                ->map(function (PlanSteward $ps) {
                    $steward     = $ps->steward;
                    $role        = $ps->role instanceof \App\Enums\StewardRole ? $ps->role->value : (string) $ps->role;
                    $displayName = $steward?->display_name ?? '—';
                    return [
                        'id'              => $ps->id,
                        'steward_id'      => $ps->steward_id,
                        'display_name'    => $displayName,
                        'slug'            => $steward?->slug,
                        'initials'        => strtoupper(mb_substr($displayName, 0, 2)),
                        'role'            => $role,
                        'role_label'      => $role === 'primary' ? 'Primary Continuity Steward' : ucfirst($role) . ' Continuity Steward',
                        'fee_cents'       => (int) ($ps->fee_cents ?? 0),
                        'payment_model'   => $ps->payment_terms ?? 'on_close',
                        'payment_terms'   => $ps->payment_terms ?? 'on_close',
                        'auto_charge'     => (bool) ($ps->auto_charge ?? false),
                        'stripe_connected'=> (bool) ($steward?->stripe_connected ?? false),
                    ];
                })->values();
        }
        $activeCsAgreements = $csStewards->where('fee_cents', '>', 0)->count();
        $csAgreedTotal      = $csStewards->sum('fee_cents');

        // ── 9. Transaction history ────────────────────────────────────────────
        $recentTxRaw = PractitionerPayment::where('practitioner_id', $user->id)
            ->orderByDesc('created_at')->limit(100)->get();

        $csInvIndex = $csInvCollection->pluck('id', 'invoice_number');
        $bpInvIndex = $bpInvCollection->pluck('id', 'invoice_number');

        $recentTransactions = $recentTxRaw->map(function (PractitionerPayment $p) use ($csInvIndex, $bpInvIndex) {
            $kind = $p->kind instanceof PractitionerPaymentKind ? $p->kind->value : (string) $p->kind;
            $catMap = [
                'cs_fee'                   => ['label' => 'CS Fee',              'color' => 'var(--gold-dark)', 'cat' => 'cs',           'modal' => 'cs_invoice'],
                'bp_invoice'               => ['label' => 'Business Partner',    'color' => 'var(--green)',     'cat' => 'bp',           'modal' => 'bp_invoice'],
                'subscription'             => ['label' => 'Aegis Subscription',  'color' => 'var(--blue)',      'cat' => 'subscription', 'modal' => 'subscription'],
                'maat_addon'               => ['label' => 'MAAT Add-on',         'color' => 'var(--purple)',    'cat' => 'subscription', 'modal' => 'subscription'],
                'refund'                   => ['label' => 'Refund',              'color' => 'var(--green)',     'cat' => 'refund',       'modal' => 'subscription'],
                'service_session'          => ['label' => 'Clinical Session',    'color' => 'var(--teal)',      'cat' => 'session',      'modal' => 'session'],
                'service_session_deposit'  => ['label' => 'Session Deposit (30%)', 'color' => 'var(--teal)',   'cat' => 'session',      'modal' => 'session'],
                'service_session_balance'  => ['label' => 'Session Balance (70%)', 'color' => 'var(--teal-dark)', 'cat' => 'session',  'modal' => 'session'],
                'service_session_refund'   => ['label' => 'Session Refund',      'color' => 'var(--amber)',     'cat' => 'refund',       'modal' => 'session'],
            ];
            $cat    = $catMap[$kind] ?? ['label' => ucfirst($kind), 'color' => 'var(--text-3)', 'cat' => 'other', 'modal' => 'subscription'];
            $status = $p->status instanceof PractitionerPaymentStatus ? $p->status->value : (string) $p->status;
            $direction = in_array($kind, ['refund', 'service_session_refund'], true) ? 1 : -1;

            return [
                'id'             => $p->id,
                'date'           => optional($p->paid_at ?? $p->created_at)->format('M j, Y') ?? '—',
                'payee'          => $p->payment_method_label ?? ($kind === 'subscription' ? 'Aegis Platform' : 'Recipient'),
                'description'    => $p->payment_method_label ?? ucfirst(str_replace('_', ' ', $kind)),
                'category_label' => $cat['label'],
                'cat'            => $cat['cat'],
                'cat_color'      => $cat['color'],
                'method'         => 'Card',
                'amount'         => $direction * (int) $p->amount_cents,
                'amount_cents'   => (int) $p->amount_cents,
                'status'         => $status,
                'kind'           => $kind,
                'modal_type'     => $cat['modal'],
                'session_id'     => $p->session_id ?? null,
                'stripe_invoice_id' => in_array($kind, ['subscription', 'maat_addon', 'refund'], true) ? $p->stripe_charge_id : null,
                'sort_ts'        => $p->paid_at?->toIso8601String() ?? $p->created_at->toIso8601String(),
                'subject_id'     => (function () use ($p, $kind, $csInvIndex, $bpInvIndex) {
                    $label = (string) ($p->payment_method_label ?? '');
                    if ($kind === 'cs_fee') {
                        foreach ($csInvIndex as $num => $id) { if (str_contains($label, (string) $num)) return $id; }
                    }
                    if ($kind === 'bp_invoice') {
                        foreach ($bpInvIndex as $num => $id) { if (str_contains($label, (string) $num)) return $id; }
                    }
                    if (in_array($kind, ['service_session', 'service_session_deposit', 'service_session_balance', 'service_session_refund'], true)) {
                        return $p->session_id;
                    }
                    return null;
                })(),
            ];
        })->sortByDesc('sort_ts')->values();

        // ── 10. Spend breakdown ───────────────────────────────────────────────
        $paidPayments = PractitionerPayment::where('practitioner_id', $user->id)
            ->where('status', PractitionerPaymentStatus::Paid->value)->get();

        $buckets = ['cs_fees' => 0, 'sessions' => 0, 'bp' => 0, 'subscription' => 0];
        foreach ($paidPayments as $p) {
            $kind = $p->kind instanceof PractitionerPaymentKind ? $p->kind->value : (string) $p->kind;
            match ($kind) {
                'cs_fee'                                      => $buckets['cs_fees']     += (int) $p->amount_cents,
                'bp_invoice'                                  => $buckets['bp']           += (int) $p->amount_cents,
                'service_session', 'service_session_deposit',
                'service_session_balance'                     => $buckets['sessions']     += (int) $p->amount_cents,
                'subscription', 'maat_addon'                  => $buckets['subscription'] += (int) $p->amount_cents,
                default                                        => null,
            };
        }
        $totalSpendCents = array_sum($buckets);
        $spendBreakdown  = collect([
            ['key' => 'cs_fees',      'label' => 'CS Fees',            'color' => 'var(--gold-dark)',  'cents' => $buckets['cs_fees']],
            ['key' => 'sessions',     'label' => 'Clinical Sessions',  'color' => 'var(--teal-dark)',  'cents' => $buckets['sessions']],
            ['key' => 'bp',           'label' => 'Business Partners',  'color' => 'var(--green-dark)', 'cents' => $buckets['bp']],
            ['key' => 'subscription', 'label' => 'Aegis Subscription', 'color' => 'var(--blue-dark)',  'cents' => $buckets['subscription']],
        ])->map(function ($b) use ($totalSpendCents) {
            $pct = $totalSpendCents > 0 ? (int) round($b['cents'] / $totalSpendCents * 100) : 0;
            return ['label' => $b['label'], 'amount' => (int) round($b['cents'] / 100), 'color' => $b['color'], 'pct' => $pct];
        })->values();

        // ── 11. Upcoming payments (Wave 8: + unfunded milestone escrow) ────────
        $upcomingBp       = $bpInvoices->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value])->map(fn ($i) => array_merge($i, ['payment_type' => 'bp',  'recipient' => $i['bp_name']]));
        $upcomingCs       = $csInvoices->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value])->map(fn ($i) => array_merge($i, ['payment_type' => 'cs',  'recipient' => $i['cs_name']]));
        $upcomingSessions = $clientSessions
            ->filter(fn ($s) => in_array($s['payment_status'] ?? '', ['unpaid', 'deposit_paid'], true) && ($s['status'] ?? '') === 'scheduled')
            ->map(fn ($s) => array_merge($s, ['payment_type' => 'session', 'recipient' => $s['practitioner_name']]));

        // Unfunded milestones on active/pending contracts → surface in upcoming
        $upcomingEscrow = $activeBpContracts
            ->filter(fn ($c) => in_array($c['status'], ['pending_funding', 'active'], true))
            ->flatMap(function ($c) {
                return collect($c['milestones'] ?? [])->filter(function ($m) {
                    return in_array($m['status'], ['pending', 'pending_funding'], true)
                        && (int) ($m['funded_cents'] ?? 0) === 0;
                })->map(fn ($m) => [
                    'id'             => $m['id'],
                    'payment_type'   => 'escrow',
                    'recipient'      => $c['bp_name'],
                    'contract_title' => $c['title'],
                    'invoice_number' => null,
                    'total_cents'    => (int) $m['amount_cents'],
                    'due_at'         => $m['due_at'],
                    'is_urgent'      => $m['due_at'] && \Illuminate\Support\Carbon::parse($m['due_at'])->isPast(),
                    'contract_id'    => $c['id'],
                    'milestone_id'   => $m['id'],
                    'milestone_title'=> $m['title'],
                ]);
            });

        $upcomingPayments = $upcomingBp->concat($upcomingCs)->concat($upcomingSessions)->concat($upcomingEscrow)
            ->sortBy(fn ($i) => $i['due_at'] ?? $i['datetime_label'] ?? '9999-12-31')
            ->map(function ($i) {
                $dueRaw = $i['due_at'] ?? null;
                $dueTs  = $dueRaw ? Carbon::parse($dueRaw) : null;
                return array_merge($i, ['due_month' => $dueTs ? strtoupper($dueTs->format('M')) : '—', 'due_day' => $dueTs ? $dueTs->format('j') : '—', 'is_urgent' => $dueTs && $dueTs->isPast()]);
            })->values();

        // ── 12. Aggregates / pending counts ──────────────────────────────────
        $bpPendingCents   = $bpInvoices->where('payable', true)->sum('total_cents');
        $csPendingCents   = $csInvoices->where('payable', true)->sum('total_cents');

        // Session pending: sum of what's owed on sessions where I am the client
        // unpaid = expected_deposit, deposit_paid = expected_balance
        $sessionPendingCents = $clientSessions->sum(function ($s) {
            return match ($s['payment_status'] ?? 'unpaid') {
                'unpaid'       => (int) ($s['expected_deposit_cents'] ?? 0),
                'deposit_paid' => (int) ($s['expected_balance_cents'] ?? 0),
                default        => 0,
            };
        });

        $bpPendingCount      = $bpInvoices->where('payable', true)->count();
        $csPendingCount      = $csInvoices->where('payable', true)->count();
        $sessionPendingCount = $clientSessions->filter(fn ($s) => in_array($s['payment_status'] ?? '', ['unpaid', 'deposit_paid'], true) && ($s['status'] ?? '') === 'scheduled')->count();

        $pendingInvoiceCount = $bpPendingCount + $csPendingCount + $sessionPendingCount;
        $pendingInvoiceTotal = $bpPendingCents + $csPendingCents + $sessionPendingCents;

        $activeSessionsAsProvider = ServiceSession::where('practitioner_id', $user->id)->where('status', ServiceSessionStatus::Scheduled->value)->count();
        $activeSessionsAsClient   = ServiceSession::where('client_id', $user->id)->where('status', ServiceSessionStatus::Scheduled->value)->count();
        $activeContractCount      = $activeBpContracts->count() + $activeCsAgreements + $activeSessionsAsClient;

        // ── 13. Subscription invoices ─────────────────────────────────────────
        $subscriptionData     = $this->subscriptions->getFullSubscriptionData($user);
        $subscriptionInvoices = $subscriptionData['invoices'] ?? [];

        // ── 14. Payment methods ───────────────────────────────────────────────
        $paymentMethods = [];
        if ($user->hasStripeId()) {
            try {
                $stripe      = $user->stripe();
                $pmList      = $stripe->paymentMethods->all(['customer' => $user->stripe_id, 'type' => 'card']);
                $defaultPmId = $user->stripe_payment_method_id
                    ?? ($stripe->customers->retrieve($user->stripe_id)->invoice_settings->default_payment_method ?? null);
                $paymentMethods = collect($pmList->data)->map(fn ($pm) => [
                    'id' => $pm->id, 'brand' => $pm->card->brand ?? 'card', 'last4' => $pm->card->last4 ?? '••••',
                    'exp_month' => $pm->card->exp_month ?? null, 'exp_year' => $pm->card->exp_year ?? null,
                    'is_default' => $pm->id === $defaultPmId, 'method_type' => 'card',
                ])->sortByDesc('is_default')->values()->toArray();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[FinancesController] Stripe PM fetch failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            }
        }
        if (empty($paymentMethods)) {
            $paymentMethods = DB::table('practitioner_payment_methods')
                ->where('practitioner_id', $user->id)->whereNull('deleted_at')->orderByDesc('is_default')->get()
                ->map(fn ($pm) => ['id' => $pm->id, 'brand' => $pm->brand ?? 'card', 'last4' => $pm->last4 ?? '••••', 'exp_month' => null, 'exp_year' => null, 'is_default' => (bool) $pm->is_default, 'method_type' => 'card'])->toArray();
        }

        // ── 15. Disputes ──────────────────────────────────────────────────────
        $disputesList = $this->disputes->listForUser($user->id)
            ->map(function (Dispute $d) use ($user) {
                $status = $d->status instanceof DisputeStatus ? $d->status : DisputeStatus::from($d->status);
                $reason = $d->reason instanceof \App\Enums\DisputeReason ? $d->reason : \App\Enums\DisputeReason::tryFrom((string) $d->reason);
                return [
                    'id'                    => $d->id,
                    'subject_type'          => $d->subject_type,
                    'subject_id'            => $d->subject_id,
                    'subject_label'         => $this->resolveDisputeSubjectLabel($d),
                    'reason_label'          => $reason?->label() ?? (string) $d->reason,
                    'status'                => $status->value,
                    'status_label'          => $status->label(),
                    'status_color'          => $status->color(),
                    'role'                  => $d->disputer_id === $user->id ? 'disputer' : 'respondent',
                    'amount_disputed_cents' => (int) $d->amount_disputed_cents,
                    'opened_at'             => $d->opened_at?->format('M j, Y') ?? $d->created_at->format('M j, Y'),
                    'resolved_at'           => $d->resolved_at?->format('M j, Y'),
                ];
            })->values();

        // ── 16. Spending controls ─────────────────────────────────────────────
        $prefs = DB::table('user_meta')
            ->where('user_id', $user->id)
            ->whereIn('meta_key', ['fin_autopay_enabled', 'fin_approval_threshold_cents', 'fin_monthly_limit_cents'])
            ->pluck('meta_value', 'meta_key');
        $spendingControls = ['auto_pay' => (bool) ($prefs['fin_autopay_enabled'] ?? false), 'approval_threshold' => (int) (($prefs['fin_approval_threshold_cents'] ?? 50000) / 100), 'monthly_limit' => (int) (($prefs['fin_monthly_limit_cents'] ?? 500000) / 100)];

        return Inertia::render('provider/Finances', [
            // ── Subscription ──────────────────────────────────────────────────
            'subscription'             => $this->subscriptions->getStatus($user),
            'subscriptionInvoices'     => $subscriptionInvoices,
            // ── Payment methods ───────────────────────────────────────────────
            'paymentMethods'           => $paymentMethods,
            'has_valid_default_pm'     => (bool) $user->stripe_payment_method_id || !empty(array_filter($paymentMethods, fn ($pm) => $pm['is_default'])),
            // ── Invoice / session lists ───────────────────────────────────────
            'csInvoices'               => $csInvoices,
            'bpInvoices'               => $bpInvoices,
            'clientSessions'           => $clientSessions,         // sessions I booked (I pay)
            'providerSessions'         => $providerSessions,       // sessions I run (I receive)
            'allInvoices'              => $allInvoices,
            'activeContracts'          => $activeBpContracts,
            'escrowSummary'            => [
                'total_held_cents'     => $activeBpContracts->sum('escrow_held_cents'),
                'total_unfunded_cents' => $activeBpContracts->sum('unfunded_cents'),
                'funded_count'         => $activeBpContracts->filter(fn ($c) => ($c['escrow_held_cents'] ?? 0) > 0)->count(),
                'contracts_needing_funding' => $activeBpContracts->filter(fn ($c) => ($c['unfunded_cents'] ?? 0) > 0)->count(),
            ],
            // ── Refund requests ───────────────────────────────────────────────
            'incomingRefundRequests'   => $incomingRefundRequests, // I am the provider
            'outgoingRefundRequests'   => $outgoingRefundRequests, // I am the client
            // ── CS Wallet ─────────────────────────────────────────────────────
            'csStewards'               => $csStewards,
            'csAgreedTotal'            => (int) $csAgreedTotal,
            // ── Overview ──────────────────────────────────────────────────────
            'spendBreakdown'           => $spendBreakdown,
            'recentTransactions'       => $recentTransactions,
            'upcomingPayments'         => $upcomingPayments,
            // ── Aggregates ────────────────────────────────────────────────────
            'totalSpendCents'          => (int) $totalSpendCents,
            'outstandingCents'         => (int) $pendingInvoiceTotal,
            'pendingInvoiceTotal'      => (int) $pendingInvoiceTotal,
            'pendingInvoiceCount'      => (int) $pendingInvoiceCount,
            'pendingBreakdown'         => [
                'bp'      => ['count' => $bpPendingCount,      'total_cents' => (int) $bpPendingCents],
                'cs'      => ['count' => $csPendingCount,      'total_cents' => (int) $csPendingCents],
                'session' => ['count' => $sessionPendingCount, 'total_cents' => (int) $sessionPendingCents],
            ],
            'activeContractCount'      => (int) $activeContractCount,
            'activeContractBreakdown'  => ['bp' => $activeBpContracts->count(), 'cs' => $activeCsAgreements, 'session' => $activeSessionsAsClient],
            'activeSessionsAsProvider' => $activeSessionsAsProvider,
            // ── Disputes ──────────────────────────────────────────────────────
            'disputes'                 => $disputesList,
            // ── Spending controls ─────────────────────────────────────────────
            'spendingControls'         => $spendingControls,
            // ── Meta ──────────────────────────────────────────────────────────
            'stripeConnected'          => (bool) $user->stripe_connected
                                          && !str_starts_with((string) ($user->stripe_account_id ?? ''), 'acct_demo'),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // WRITE ACTIONS — all unchanged from original
    // ═══════════════════════════════════════════════════════════════════════

    public function payCSInvoice(Request $request, CsInvoice $invoice): RedirectResponse
    {
        $provider = $request->user();
        if ($invoice->practitioner_id !== $provider->id) abort(403);
        $status = $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status;
        if ($status === InvoiceStatus::Paid->value)     return back()->withErrors(['invoice' => 'Already paid.']);
        if ($status === InvoiceStatus::Void->value)     return back()->withErrors(['invoice' => 'This invoice was voided.']);
        if ($status === InvoiceStatus::Draft->value)    return back()->withErrors(['invoice' => 'Not yet sent.']);
        if ($status === InvoiceStatus::Disputed->value) return back()->withErrors(['invoice' => 'This invoice is under dispute.']);
        $cs = User::find($invoice->cs_id);
        if (!$cs) return back()->withErrors(['invoice' => 'Continuity Steward not found.']);
        try {
            $result = $this->payouts->chargeProviderToCs(provider: $provider, cs: $cs, amountCents: (int) $invoice->total_cents, meta: ['cs_invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number], description: 'CS invoice ' . $invoice->invoice_number);
            $invoice->update(['status' => $result['status'] === 'paid' ? InvoiceStatus::Paid->value : $status, 'stripe_payment_intent_id' => $result['stripe_payment_intent_id'], 'stripe_transfer_id' => $result['stripe_transfer_id'] ?? null, 'paid_at' => $result['status'] === 'paid' ? now() : null]);
            PractitionerPayment::create(['id' => (string) Str::uuid(), 'practitioner_id' => $provider->id, 'kind' => PractitionerPaymentKind::CsFee->value, 'amount_cents' => (int) $invoice->total_cents, 'currency' => 'USD', 'status' => $result['status'] === 'paid' ? PractitionerPaymentStatus::Paid->value : PractitionerPaymentStatus::Pending->value, 'payment_method_label' => 'CS Invoice ' . $invoice->invoice_number, 'stripe_charge_id' => $result['stripe_payment_intent_id'], 'stripe_transfer_id' => $result['stripe_transfer_id'] ?? null, 'paid_at' => $result['status'] === 'paid' ? now() : null]);
            CsPayout::create(['id' => (string) Str::uuid(), 'cs_id' => $cs->id, 'amount_cents' => (int) $invoice->total_cents, 'currency' => 'USD', 'status' => $result['status'] === 'paid' ? PayoutStatus::Paid->value : PayoutStatus::Pending->value, 'description' => 'CS invoice ' . $invoice->invoice_number, 'stripe_payout_id' => $result['stripe_transfer_id'] ?? $result['stripe_payment_intent_id'], 'paid_at' => $result['status'] === 'paid' ? now() : null]);
            $this->activity->log($provider->id, 'provider', 'finances', ActivitySeverity::Info, 'cs_invoice_paid', 'CS invoice paid', 'You paid invoice ' . $invoice->invoice_number . ' for $' . number_format($invoice->total_cents / 100, 2) . '.', CsInvoice::class, $invoice->id, $cs->id, 'log', $provider->id);
            $this->activity->log($cs->id, 'continuity_steward', 'finances', ActivitySeverity::Info, 'cs_payout_received', 'Payment received', 'You received $' . number_format($invoice->total_cents / 100, 2) . ' for invoice ' . $invoice->invoice_number . '.', CsInvoice::class, $invoice->id, $provider->id, 'notification', $provider->id);
            return back()->with('success', 'Invoice ' . $invoice->invoice_number . ' paid.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }
    }

    public function rejectBpInvoice(Request $request, BpInvoice $invoice): RedirectResponse
    {
        $provider = $request->user();
        if ($invoice->practitioner_id !== $provider->id) abort(403);
        $data = $request->validate(['reason' => 'required|string|max:120', 'message' => 'nullable|string|max:1000']);
        $status = $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status;
        if (!in_array($status, [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value], true)) return back()->withErrors(['invoice' => 'This invoice cannot be rejected in its current state.']);
        $invoice->update(['status' => InvoiceStatus::Void->value, 'voided_at' => now()]);
        $bp = User::find($invoice->bp_id);
        $this->activity->log($provider->id, 'provider', 'finances', ActivitySeverity::Info, 'bp_invoice_rejected', 'Invoice rejected', 'You rejected invoice ' . ($invoice->invoice_number ?? $invoice->id) . '. Reason: ' . $data['reason'], BpInvoice::class, $invoice->id, $invoice->bp_id, 'log', $provider->id);
        if ($bp) $this->activity->log($bp->id, 'business_partner', 'finances', ActivitySeverity::Warning, 'invoice_rejected_by_provider', 'Invoice rejected', 'Your invoice ' . ($invoice->invoice_number ?? $invoice->id) . ' was rejected. Reason: ' . $data['reason'], BpInvoice::class, $invoice->id, $provider->id, 'notification', $provider->id);
        return back()->with('success', 'Invoice rejected — Business Partner notified.');
    }

    public function cancelBpContract(Request $request, BpContract $contract): RedirectResponse
    {
        $provider = $request->user();
        if ($contract->practitioner_id !== $provider->id) abort(403);
        $data = $request->validate(['reason' => 'required|string|max:500', 'feedback' => 'nullable|string|max:1000']);
        $contract->update(['status' => ContractStatus::Cancelled->value, 'cancelled_at' => now(), 'cancel_reason' => $data['reason']]);
        $bp = User::find($contract->bp_id);
        $this->activity->log($provider->id, 'provider', 'finances', ActivitySeverity::Info, 'bp_contract_cancelled', 'Contract cancelled', 'Cancelled contract with ' . ($bp?->display_name ?? 'BP') . '.', BpContract::class, $contract->id, $contract->bp_id, 'log', $provider->id);
        if ($bp) $this->activity->log($bp->id, 'business_partner', 'finances', ActivitySeverity::Warning, 'contract_cancelled_by_provider', 'Contract cancelled', 'Your contract with ' . ($provider->display_name ?? 'Practitioner') . ' has been cancelled.', BpContract::class, $contract->id, $provider->id, 'notification', $provider->id);
        return back()->with('success', 'Contract cancelled.');
    }

    public function cancelCsAgreement(Request $request, PlanSteward $steward): RedirectResponse
    {
        $provider = $request->user();
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)->firstOrFail();
        if ($steward->plan_id !== $plan->id) abort(403);
        $data = $request->validate(['reason' => 'required|string|max:500']);
        $steward->update(['status' => StewardStatus::Archived->value, 'declined_reason' => $data['reason']]);
        $cs = User::find($steward->steward_id);
        $this->activity->log($provider->id, 'provider', 'steward', ActivitySeverity::Info, 'cs_agreement_cancelled', 'CS agreement cancelled', 'Cancelled the Continuity Steward agreement with ' . ($cs?->display_name ?? 'CS') . '.', PlanSteward::class, $steward->id, $steward->steward_id, 'log', $provider->id);
        return back()->with('success', 'Continuity Steward agreement cancelled.');
    }

    public function updateCsPayModel(Request $request, PlanSteward $steward): RedirectResponse
    {
        $provider = $request->user();
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)->firstOrFail();
        if ($steward->plan_id !== $plan->id) abort(403);
        $data = $request->validate(['payment_model' => 'required|string|in:on_close,net_30,net_60', 'fee_cents' => 'nullable|integer|min:0']);
        $steward->update(['payment_terms' => $data['payment_model'], 'fee_cents' => $data['fee_cents'] ?? $steward->fee_cents]);
        $this->activity->log($provider->id, 'provider', 'finances', ActivitySeverity::Info, 'cs_payment_model_updated', 'CS payment model updated', 'Updated payment terms for CS to ' . $data['payment_model'] . '.', PlanSteward::class, $steward->id, $steward->steward_id, 'log', $provider->id);
        return back()->with('success', 'Payment model updated.');
    }

    public function saveSpendingControls(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['auto_pay' => 'required|boolean', 'approval_threshold' => 'required|integer|min:0|max:1000000', 'monthly_limit' => 'required|integer|min:0|max:10000000']);
        $prefs = ['fin_autopay_enabled' => $data['auto_pay'] ? '1' : '0', 'fin_approval_threshold_cents' => (string) ($data['approval_threshold'] * 100), 'fin_monthly_limit_cents' => (string) ($data['monthly_limit'] * 100)];
        foreach ($prefs as $key => $value) {
            $existing = DB::table('user_meta')->where('user_id', $user->id)->where('meta_key', $key)->first();
            if ($existing) { DB::table('user_meta')->where('id', $existing->id)->update(['meta_value' => $value, 'updated_at' => now()]); }
            else { DB::table('user_meta')->insert(['id' => 'um_' . Str::lower(Str::random(12)), 'user_id' => $user->id, 'meta_key' => $key, 'meta_value' => $value, 'meta_type' => 'string', 'created_at' => now(), 'updated_at' => now()]); }
        }
        $this->activity->log($user->id, 'provider', 'finances', ActivitySeverity::Info, 'spending_controls_updated', 'Spending controls updated', 'Updated autopay + approval + monthly-limit settings.', User::class, $user->id, null, 'log', $user->id);
        return back()->with('success', 'Spending controls saved.');
    }

    public function saveAutopay(Request $request, BpContract $contract): RedirectResponse
    {
        $provider = $request->user();
        if ($contract->practitioner_id !== $provider->id) abort(403);
        $data = $request->validate(['enabled' => 'required|boolean', 'day' => 'required|string|in:1st,15th,last,due', 'method_id' => 'nullable|string', 'notify' => 'required|string|in:3_days,1_day,same_day,none', 'limit' => 'nullable|numeric|min:0']);
        $key   = 'contract_autopay_' . $contract->id;
        $value = json_encode($data);
        $existing = DB::table('user_meta')->where('user_id', $provider->id)->where('meta_key', $key)->first();
        if ($existing) { DB::table('user_meta')->where('id', $existing->id)->update(['meta_value' => $value, 'updated_at' => now()]); }
        else { DB::table('user_meta')->insert(['id' => 'um_' . Str::lower(Str::random(12)), 'user_id' => $provider->id, 'meta_key' => $key, 'meta_value' => $value, 'meta_type' => 'json', 'created_at' => now(), 'updated_at' => now()]); }
        return back()->with('success', $data['enabled'] ? 'Auto-pay enabled.' : 'Auto-pay turned off.');
    }

    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate(['payment_method_id' => 'required|string|starts_with:pm_', 'set_default' => 'nullable|boolean']);
        $user = $request->user();
        try {
            if (!$user->hasStripeId()) $user->createAsStripeCustomer(['name' => $user->display_name, 'email' => $user->email]);
            $user->addPaymentMethod($data['payment_method_id']);
            if (!empty($data['set_default'])) {
                $user->updateDefaultPaymentMethod($data['payment_method_id']);
                $user->update(['stripe_payment_method_id' => $data['payment_method_id']]);
                // Cache card display info for fund modals
                try {
                    $pm = $user->stripe()->paymentMethods->retrieve($data['payment_method_id']);
                    $this->cachePmMeta($user, $pm->card->last4 ?? null, $pm->card->brand ?? null);
                } catch (\Throwable) {}
            }
            return redirect()->route('provider.finances.index', ['tab' => 'methods'])->with('success', 'Payment method saved.');
        } catch (\Throwable $e) {
            return redirect()->route('provider.finances.index', ['tab' => 'methods'])->withErrors(['payment' => 'Could not save payment method. ' . $e->getMessage()]);
        }
    }

    private function cachePmMeta(\App\Models\User $user, ?string $last4, ?string $brand): void
    {
        foreach (['pm_last4' => $last4, 'pm_brand' => $brand] as $key => $value) {
            if ($value === null) continue;
            $existing = \App\Models\UserMeta::where('user_id', $user->id)->where('meta_key', $key)->first();
            if ($existing) {
                $existing->update(['meta_value' => $value]);
            } else {
                \App\Models\UserMeta::create([
                    'id'         => 'um_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)),
                    'user_id'    => $user->id,
                    'meta_key'   => $key,
                    'meta_value' => $value,
                    'meta_type'  => 'string',
                ]);
            }
        }
    }

    public function exportReport(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['from' => 'required|date', 'to' => 'required|date|after_or_equal:from', 'includes' => 'required|array|min:1', 'includes.*' => 'string|in:cs,bp,sessions,subscription', 'format' => 'required|string|in:csv,pdf,xlsx']);
        $csv  = "Date,Type,Payee,Description,Amount,Status\n";
        $from = Carbon::parse($data['from'])->startOfDay();
        $to   = Carbon::parse($data['to'])->endOfDay();
        $kindFilter = collect($data['includes'])->flatMap(fn ($k) => match ($k) { 'cs' => ['cs_fee'], 'bp' => ['bp_invoice'], 'sessions' => ['service_session', 'service_session_deposit', 'service_session_balance', 'service_session_refund'], 'subscription' => ['subscription', 'maat_addon'], default => [] })->all();
        $rows = PractitionerPayment::where('practitioner_id', $user->id)->whereBetween('created_at', [$from, $to])->whereIn('kind', $kindFilter)->orderBy('created_at')->get();
        foreach ($rows as $r) {
            $date = optional($r->paid_at ?? $r->created_at)->format('Y-m-d');
            $kind = $r->kind instanceof PractitionerPaymentKind ? $r->kind->value : (string) $r->kind;
            $type = ucfirst(str_replace('_', ' ', $kind));
            $payee = str_replace(',', ' ', $r->payment_method_label ?? '');
            $amount = number_format($r->amount_cents / 100, 2);
            $status = $r->status instanceof PractitionerPaymentStatus ? $r->status->value : (string) $r->status;
            $csv .= "{$date},{$type},{$payee},{$payee},{$amount},{$status}\n";
        }
        $this->activity->log($user->id, 'provider', 'finances', ActivitySeverity::Info, 'finances_exported', 'Finances exported', 'Exported financial report from ' . $data['from'] . ' to ' . $data['to'] . ' (' . count($rows) . ' rows).', User::class, $user->id, null, 'log', $user->id);
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="aegis-finances-' . $data['from'] . '-to-' . $data['to'] . '.csv"']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    private function shapeRefundRequest(SessionRefundRequest $r, string $viewpoint): array
    {
        $status = $r->status instanceof \App\Enums\SessionRefundRequestStatus
            ? $r->status
            : \App\Enums\SessionRefundRequestStatus::from((string) $r->status);
        $refundType = $r->refund_type instanceof \App\Enums\SessionRefundType
            ? $r->refund_type
            : \App\Enums\SessionRefundType::tryFrom((string) $r->refund_type);

        return [
            'id'                     => $r->id,
            'viewpoint'              => $viewpoint,
            'session_id'             => $r->session_id,
            'service_title'          => $r->session?->service?->title ?? '—',
            'session_date'           => $r->session?->scheduled_at?->format('M j, Y') ?? '—',
            'requested_by_name'      => $r->requester?->display_name ?? '—',
            'requested_by_avatar'    => $r->requester?->avatar_initials ?? '',
            'provider_name'          => $r->provider?->display_name ?? '—',
            'reason'                 => $r->reason,
            'reason_detail'          => $r->reason_detail,
            'refund_type'            => $r->refund_type instanceof \App\Enums\SessionRefundType ? $r->refund_type->value : (string) $r->refund_type,
            'refund_type_label'      => $refundType?->label() ?? (string) $r->refund_type,
            'amount_requested_cents' => (int) $r->amount_requested_cents,
            'amount_requested'       => '$' . number_format($r->amount_requested_cents / 100, 2),
            'status'                 => $status->value,
            'status_label'           => $status->label(),
            'status_variant'         => $status->badgeVariant(),
            'provider_response'      => $r->provider_response,
            'responded_at'           => $r->responded_at?->format('M j, Y'),
            'provider_deadline_at'   => $r->provider_deadline_at?->format('M j, Y g:i A'),
            'is_overdue'             => $r->is_overdue,
            'can_escalate'           => $r->can_escalate,
            'is_actionable'          => $r->is_actionable,
            'refunded_cents'         => (int) $r->refunded_cents,
            'stripe_refund_id'       => $r->stripe_refund_id,
            'created_at'             => $r->created_at->format('M j, Y'),
        ];
    }

    private function resolveDisputeSubjectLabel(Dispute $d): string
    {
        $subject = $d->resolveSubject();
        if (!$subject) return ucfirst(str_replace('_', ' ', $d->subject_type)) . ' (deleted)';
        return match ($d->subject_type) {
            'cs_invoice' => 'CS Invoice ' . ($subject->invoice_number ?? $d->subject_id),
            'bp_invoice' => 'BP Invoice ' . ($subject->invoice_number ?? $d->subject_id),
            'session'    => 'Session — ' . ($subject->service?->title ?? substr($d->subject_id, 0, 8)),
            default      => ucfirst(str_replace('_', ' ', $d->subject_type)),
        };
    }
}
