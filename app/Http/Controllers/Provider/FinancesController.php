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
use App\Models\User;
use App\Services\ActivityService;
use App\Services\DisputeService;
use App\Services\PayoutService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Provider Finances Controller — the money hub.
 *
 * Handles all 4 flows visible to the practitioner:
 *   1. Subscription (Provider → Aegis)                         [Cashier]
 *   2. Peer: Provider → Business Partner (Support Services)    [Stripe Connect]
 *   3. Peer: Provider → Continuity Steward                     [Stripe Connect]
 *   4. Peer: Provider → other Provider (Clinical sessions bought as a client)
 *      Also: Provider RECEIVING money as a service session recipient
 */
class FinancesController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptions,
        private PayoutService $payouts,
        private ActivityService $activity,
        private DisputeService $disputes,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // ────────────────────────────────────────────────────────────
        // 1. CS INVOICES (with active dispute map)
        // ────────────────────────────────────────────────────────────
        $csInvCollection = CsInvoice::where('practitioner_id', $user->id)
            ->with(['cs:id,display_name,slug,stripe_connected'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $csInvIds  = $csInvCollection->pluck('id')->all();
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
                'kind'             => 'cs_invoice',  // for tx receipt modal routing
            ];
        })->values();

        // ────────────────────────────────────────────────────────────
        // 2. BP INVOICES (with active dispute map)
        // ────────────────────────────────────────────────────────────
        $bpInvCollection = BpInvoice::where('practitioner_id', $user->id)
            ->with(['bp:id,display_name,slug,stripe_connected', 'contract:id,title'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $bpInvIds  = $bpInvCollection->pluck('id')->all();
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
                'kind'             => 'bp_invoice',
            ];
        })->values();

        // ────────────────────────────────────────────────────────────
        // 3. CLIENT SESSIONS — Provider as CLIENT (paying other practitioners)
        //    These are clinical service sessions the Provider bought.
        // ────────────────────────────────────────────────────────────
        $clientSessions = ServiceSession::where('client_id', $user->id)
            ->with(['practitioner:id,display_name,slug,stripe_connected', 'service:id,title'])
            ->orderByDesc('scheduled_at')
            ->limit(50)
            ->get()
            ->map(function (ServiceSession $s) {
                $status = $s->status instanceof ServiceSessionStatus ? $s->status->value : (string) $s->status;
                return [
                    'id'                => $s->id,
                    'invoice_number'    => 'SES-' . strtoupper(substr($s->id, 0, 8)),
                    'practitioner_name' => $s->practitioner?->display_name ?? '—',
                    'practitioner_slug' => $s->practitioner?->slug,
                    'practitioner_connected' => (bool) ($s->practitioner?->stripe_connected ?? false),
                    'service_title'     => $s->service?->title ?? 'Clinical session',
                    'total_cents'       => (int) $s->amount_cents,
                    'status'            => $status,
                    'scheduled_at'      => $s->scheduled_at?->format('M j, Y g:i A'),
                    'issued_month'      => $s->scheduled_at?->format('F Y'),
                    // "Payable" here means: session is scheduled/completed and client hasn't confirmed yet.
                    // Actual payment fires via ServiceSessionsController::completeSession.
                    'payable'           => $status === ServiceSessionStatus::Scheduled->value,
                    'kind'              => 'session',
                ];
            })->values();

        // ────────────────────────────────────────────────────────────
        // 4. Merged invoices — the "everything you owe or paid" ledger
        // ────────────────────────────────────────────────────────────
        $allInvoices = collect()
            ->concat($csInvoices)
            ->concat($bpInvoices)
            ->concat($clientSessions)
            ->values();

        // ────────────────────────────────────────────────────────────
        // 5. Active BP Contracts
        // ────────────────────────────────────────────────────────────
        $activeBpContracts = BpContract::where('practitioner_id', $user->id)
            ->where('status', ContractStatus::Active->value)
            ->with(['bp:id,display_name,slug,stripe_connected'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (BpContract $con) {
                $lastPaid = BpInvoice::where('contract_id', $con->id)
                    ->where('status', InvoiceStatus::Paid->value)
                    ->orderByDesc('paid_at')
                    ->value('paid_at');

                return [
                    'id'             => $con->id,
                    'title'          => $con->title,
                    'bp_name'        => $con->bp?->display_name ?? '—',
                    'bp_slug'        => $con->bp?->slug,
                    'bp_connected'   => (bool) ($con->bp?->stripe_connected ?? false),
                    'total_cents'    => (int) $con->total_value_cents,
                    'billing_type'   => $con->payment_type ?? 'one_time',
                    'billing_type_label' => match($con->payment_type ?? 'one_time') {
                        'milestone' => 'Milestone-based',
                        'retainer'  => 'Monthly retainer',
                        default     => 'One-time',
                    },
                    'term'           => ($con->started_at?->format('M Y') ?? '—') . ' – ' . ($con->completed_at?->format('M Y') ?? 'Ongoing'),
                    'last_paid'      => $lastPaid ? Carbon::parse($lastPaid)->format('M j, Y') : null,
                    'autopay_enabled'=> false,
                    'status'         => $con->status instanceof ContractStatus ? $con->status->value : (string) $con->status,
                    'kind'           => 'bp',
                ];
            })->values();

        // Active sessions (as CLIENT — Provider paying other Providers)
        $activeSessionsAsClient = ServiceSession::where('client_id', $user->id)
            ->where('status', ServiceSessionStatus::Scheduled->value)
            ->with(['practitioner:id,display_name,slug', 'service:id,title'])
            ->count();

        // ────────────────────────────────────────────────────────────
        // 6. CS Stewards (for CS Wallet tab)
        // ────────────────────────────────────────────────────────────
        $plan = ContinuityPlan::where('practitioner_id', $user->id)->first();
        $csStewards = collect();
        if ($plan) {
            $csStewards = PlanSteward::where('plan_id', $plan->id)
                ->where('steward_category', 'continuity_steward')
                ->where('status', StewardStatus::Active->value)
                ->with(['steward:id,display_name,slug,stripe_connected,stripe_account_id'])
                ->get()
                ->map(function (PlanSteward $ps) {
                    $steward = $ps->steward;
                    $role = $ps->role instanceof \App\Enums\StewardRole ? $ps->role->value : (string) $ps->role;
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

        // Active CS agreements count (with fee_cents > 0 = paid arrangement)
        $activeCsAgreements = $csStewards->where('fee_cents', '>', 0)->count();
        $csAgreedTotal = $csStewards->sum('fee_cents');

        // ────────────────────────────────────────────────────────────
        // 7. TRANSACTION HISTORY — all payment kinds merged
        // ────────────────────────────────────────────────────────────
        $recentTxRaw = PractitionerPayment::where('practitioner_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $recentTransactions = $recentTxRaw->map(function (PractitionerPayment $p) {
            $kind = $p->kind instanceof PractitionerPaymentKind ? $p->kind->value : (string) $p->kind;
            $catMap = [
                'cs_fee'          => ['label' => 'CS Fee',           'color' => 'var(--gold-dark)', 'cat' => 'cs',           'modal' => 'cs_invoice'],
                'bp_invoice'      => ['label' => 'Business Partner', 'color' => 'var(--green)',     'cat' => 'bp',           'modal' => 'bp_invoice'],
                'subscription'    => ['label' => 'Aegis Subscription','color' => 'var(--blue)',    'cat' => 'subscription', 'modal' => 'subscription'],
                'maat_addon'      => ['label' => 'MAAT Add-on',      'color' => 'var(--purple)',    'cat' => 'subscription', 'modal' => 'subscription'],
                'refund'          => ['label' => 'Refund',            'color' => 'var(--green)',    'cat' => 'refund',       'modal' => 'subscription'],
                'service_session' => ['label' => 'Clinical Session', 'color' => 'var(--teal)',      'cat' => 'session',      'modal' => 'session'],
            ];
            $cat    = $catMap[$kind] ?? ['label' => ucfirst($kind), 'color' => 'var(--text-3)', 'cat' => 'other', 'modal' => 'subscription'];
            $status = $p->status instanceof PractitionerPaymentStatus ? $p->status->value : (string) $p->status;

            // Direction: refunds and outgoing payments are negative; anything received is positive.
            $direction = in_array($kind, ['refund'], true) ? 1 : -1;

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
                'stripe_invoice_id' => in_array($kind, ['subscription', 'maat_addon', 'refund'], true) ? $p->stripe_charge_id : null,
                'sort_ts'        => $p->paid_at?->toIso8601String() ?? $p->created_at->toIso8601String(),
            ];
        })->sortByDesc('sort_ts')->values();

        // ────────────────────────────────────────────────────────────
        // 8. SPEND BREAKDOWN — always show 3 fixed buckets
        // ────────────────────────────────────────────────────────────
        $paidPayments = PractitionerPayment::where('practitioner_id', $user->id)
            ->where('status', PractitionerPaymentStatus::Paid->value)
            ->get();

        $buckets = ['cs_fees' => 0, 'sessions' => 0, 'bp' => 0, 'subscription' => 0];
        foreach ($paidPayments as $p) {
            $kind = $p->kind instanceof PractitionerPaymentKind ? $p->kind->value : (string) $p->kind;
            match ($kind) {
                'cs_fee'          => $buckets['cs_fees']     += (int) $p->amount_cents,
                'bp_invoice'      => $buckets['bp']           += (int) $p->amount_cents,
                'service_session' => $buckets['sessions']     += (int) $p->amount_cents,
                'subscription',
                'maat_addon'      => $buckets['subscription'] += (int) $p->amount_cents,
                default           => null,
            };
        }

        $totalSpendCents = array_sum($buckets);

        $spendBreakdown = collect([
            ['key' => 'cs_fees',      'label' => 'CS Fees',            'color' => 'var(--gold-dark)', 'cents' => $buckets['cs_fees']],
            ['key' => 'sessions',     'label' => 'Clinical Sessions',  'color' => 'var(--teal-dark)', 'cents' => $buckets['sessions']],
            ['key' => 'bp',           'label' => 'Business Partners',  'color' => 'var(--green-dark)', 'cents' => $buckets['bp']],
            ['key' => 'subscription', 'label' => 'Aegis Subscription', 'color' => 'var(--blue-dark)', 'cents' => $buckets['subscription']],
        ])->map(function ($b) use ($totalSpendCents) {
            $pct = $totalSpendCents > 0 ? (int) round($b['cents'] / $totalSpendCents * 100) : 0;
            return [
                'label'  => $b['label'],
                'amount' => (int) round($b['cents'] / 100),
                'color'  => $b['color'],
                'pct'    => $pct,
            ];
        })->values();

        // ────────────────────────────────────────────────────────────
        // 9. UPCOMING PAYMENTS — all 3 peer types merged, sorted by due
        // ────────────────────────────────────────────────────────────
        $upcomingBp = $bpInvoices
            ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value])
            ->map(fn ($i) => array_merge($i, ['payment_type' => 'bp', 'recipient' => $i['bp_name']]));

        $upcomingCs = $csInvoices
            ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value])
            ->map(fn ($i) => array_merge($i, ['payment_type' => 'cs', 'recipient' => $i['cs_name']]));

        $upcomingSessions = $clientSessions
            ->where('status', ServiceSessionStatus::Scheduled->value)
            ->map(fn ($s) => array_merge($s, ['payment_type' => 'session', 'recipient' => $s['practitioner_name']]));

        $upcomingPayments = $upcomingBp
            ->concat($upcomingCs)
            ->concat($upcomingSessions)
            ->sortBy(fn ($i) => $i['due_at'] ?? $i['scheduled_at'] ?? '9999-12-31')
            ->map(function ($i) {
                $dueRaw = $i['due_at'] ?? $i['scheduled_at'] ?? null;
                $dueTs  = $dueRaw ? Carbon::parse($dueRaw) : null;
                return array_merge($i, [
                    'due_month' => $dueTs ? strtoupper($dueTs->format('M')) : '—',
                    'due_day'   => $dueTs ? $dueTs->format('j') : '—',
                    'is_urgent' => $dueTs && $dueTs->isPast(),
                ]);
            })
            ->values();

        // ────────────────────────────────────────────────────────────
        // 10. STAT AGGREGATES — with breakdown for tooltips
        // ────────────────────────────────────────────────────────────
        $bpPendingCents = $bpInvoices->where('payable', true)->sum('total_cents');
        $csPendingCents = $csInvoices->where('payable', true)->sum('total_cents');
        $sessionPendingCents = $clientSessions->where('payable', true)->sum('total_cents');

        $bpPendingCount = $bpInvoices->where('payable', true)->count();
        $csPendingCount = $csInvoices->where('payable', true)->count();
        $sessionPendingCount = $clientSessions->where('payable', true)->count();

        $pendingInvoiceCount = $bpPendingCount + $csPendingCount + $sessionPendingCount;
        $pendingInvoiceTotal = $bpPendingCents + $csPendingCents + $sessionPendingCents;

        $bpContractCount = $activeBpContracts->count();
        $csAgreementCount = $activeCsAgreements;
        $activeContractCount = $bpContractCount + $csAgreementCount + $activeSessionsAsClient;

        // ────────────────────────────────────────────────────────────
        // 11. SUBSCRIPTION INVOICES (from Stripe / Cashier)
        // ────────────────────────────────────────────────────────────
        $subscriptionData = $this->subscriptions->getFullSubscriptionData($user);
        $subscriptionInvoices = $subscriptionData['invoices'] ?? [];

        // ────────────────────────────────────────────────────────────
        // 12. PAYMENT METHODS — Cashier + fallback table
        // ────────────────────────────────────────────────────────────
        $paymentMethods = [];
        if (method_exists($user, 'paymentMethods')) {
            try {
                $paymentMethods = collect($user->paymentMethods())->map(fn ($pm) => [
                    'id'          => $pm->id,
                    'brand'       => $pm->card->brand ?? 'card',
                    'last4'       => $pm->card->last4 ?? '••••',
                    'exp_month'   => $pm->card->exp_month ?? null,
                    'exp_year'    => $pm->card->exp_year ?? null,
                    'is_default'  => $user->stripe_payment_method_id === $pm->id,
                    'method_type' => 'card',
                ])->values()->toArray();
            } catch (\Throwable) {}
        }
        if (empty($paymentMethods)) {
            $paymentMethods = DB::table('practitioner_payment_methods')
                ->where('practitioner_id', $user->id)
                ->whereNull('deleted_at')
                ->orderByDesc('is_default')
                ->get()
                ->map(fn ($pm) => [
                    'id'          => $pm->id,
                    'brand'       => $pm->brand ?? 'card',
                    'last4'       => $pm->last4 ?? '••••',
                    'exp_month'   => null,
                    'exp_year'    => null,
                    'is_default'  => (bool) $pm->is_default,
                    'method_type' => 'card',
                ])->toArray();
        }

        // ────────────────────────────────────────────────────────────
        // 13. DISPUTES
        // ────────────────────────────────────────────────────────────
        $disputesList = $this->disputes->listForUser($user->id)
            ->map(function (Dispute $d) use ($user) {
                $status = $d->status instanceof DisputeStatus ? $d->status : DisputeStatus::from($d->status);
                $reason = $d->reason instanceof \App\Enums\DisputeReason
                    ? $d->reason
                    : \App\Enums\DisputeReason::tryFrom((string) $d->reason);
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

        // ────────────────────────────────────────────────────────────
        // 14. SPENDING CONTROLS — pull from user_meta
        // ────────────────────────────────────────────────────────────
        $prefs = DB::table('user_meta')
            ->where('user_id', $user->id)
            ->whereIn('meta_key', ['fin_autopay_enabled', 'fin_approval_threshold_cents', 'fin_monthly_limit_cents'])
            ->pluck('meta_value', 'meta_key');

        $spendingControls = [
            'auto_pay'           => (bool) ($prefs['fin_autopay_enabled'] ?? false),
            'approval_threshold' => (int) (($prefs['fin_approval_threshold_cents'] ?? 50000) / 100),
            'monthly_limit'      => (int) (($prefs['fin_monthly_limit_cents']      ?? 500000) / 100),
        ];

        return Inertia::render('provider/Finances', [
            // ── Subscription ─────────────────────────────────────────
            'subscription'         => $this->subscriptions->getStatus($user),
            'subscriptionInvoices' => $subscriptionInvoices,

            // ── Payment methods ───────────────────────────────────────
            'paymentMethods'       => $paymentMethods,
            'has_valid_default_pm' => (bool) $user->stripe_payment_method_id || !empty(array_filter($paymentMethods, fn ($pm) => $pm['is_default'])),

            // ── Invoice lists ─────────────────────────────────────────
            'csInvoices'           => $csInvoices,
            'bpInvoices'           => $bpInvoices,
            'clientSessions'       => $clientSessions,
            'allInvoices'          => $allInvoices,
            'activeContracts'      => $activeBpContracts,

            // ── CS Wallet ─────────────────────────────────────────────
            'csStewards'           => $csStewards,
            'csAgreedTotal'        => (int) $csAgreedTotal,

            // ── Overview ──────────────────────────────────────────────
            'spendBreakdown'       => $spendBreakdown,
            'recentTransactions'   => $recentTransactions,
            'upcomingPayments'     => $upcomingPayments,

            // ── Aggregates ────────────────────────────────────────────
            'totalSpendCents'      => (int) $totalSpendCents,
            'outstandingCents'     => (int) $pendingInvoiceTotal,
            'pendingInvoiceTotal'  => (int) $pendingInvoiceTotal,
            'pendingInvoiceCount'  => (int) $pendingInvoiceCount,
            'pendingBreakdown'     => [
                'bp'      => ['count' => $bpPendingCount,     'total_cents' => (int) $bpPendingCents],
                'cs'      => ['count' => $csPendingCount,     'total_cents' => (int) $csPendingCents],
                'session' => ['count' => $sessionPendingCount,'total_cents' => (int) $sessionPendingCents],
            ],
            'activeContractCount'  => (int) $activeContractCount,
            'activeContractBreakdown' => [
                'bp'      => $bpContractCount,
                'cs'      => $csAgreementCount,
                'session' => $activeSessionsAsClient,
            ],

            // ── Disputes ──────────────────────────────────────────────
            'disputes'             => $disputesList,

            // ── Spending controls ─────────────────────────────────────
            'spendingControls'     => $spendingControls,

            // ── Meta ──────────────────────────────────────────────────
            // Demo accounts (acct_demo_*) are stubs — treat as not connected
            'stripeConnected'      => (bool) $user->stripe_connected
                                      && !str_starts_with((string) ($user->stripe_account_id ?? ''), 'acct_demo'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    //  WRITE ACTIONS
    // ─────────────────────────────────────────────────────────────────

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
            $result = $this->payouts->chargeProviderToCs(
                provider:    $provider,
                cs:          $cs,
                amountCents: (int) $invoice->total_cents,
                meta:        ['cs_invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number],
                description: 'CS invoice ' . $invoice->invoice_number,
            );

            $invoice->update([
                'status'                   => $result['status'] === 'paid' ? InvoiceStatus::Paid->value : $status,
                'stripe_payment_intent_id' => $result['stripe_payment_intent_id'],
                'stripe_transfer_id'       => $result['stripe_transfer_id'] ?? null,
                'paid_at'                  => $result['status'] === 'paid' ? now() : null,
            ]);

            PractitionerPayment::create([
                'id'                   => (string) Str::uuid(),
                'practitioner_id'      => $provider->id,
                'kind'                 => PractitionerPaymentKind::CsFee->value,
                'amount_cents'         => (int) $invoice->total_cents,
                'currency'             => 'USD',
                'status'               => $result['status'] === 'paid' ? PractitionerPaymentStatus::Paid->value : PractitionerPaymentStatus::Pending->value,
                'payment_method_label' => 'CS Invoice ' . $invoice->invoice_number,
                'stripe_charge_id'     => $result['stripe_payment_intent_id'],
                'stripe_transfer_id'   => $result['stripe_transfer_id'] ?? null,
                'paid_at'              => $result['status'] === 'paid' ? now() : null,
            ]);

            CsPayout::create([
                'id'               => (string) Str::uuid(),
                'cs_id'            => $cs->id,
                'amount_cents'     => (int) $invoice->total_cents,
                'currency'         => 'USD',
                'status'           => $result['status'] === 'paid' ? PayoutStatus::Paid->value : PayoutStatus::Pending->value,
                'description'      => 'CS invoice ' . $invoice->invoice_number,
                'stripe_payout_id' => $result['stripe_transfer_id'] ?? $result['stripe_payment_intent_id'],
                'paid_at'          => $result['status'] === 'paid' ? now() : null,
            ]);

            $this->activity->log(
                $provider->id, 'provider', 'finances', ActivitySeverity::Info,
                'cs_invoice_paid', 'CS invoice paid',
                'You paid invoice ' . $invoice->invoice_number . ' for $' . number_format($invoice->total_cents / 100, 2) . '.',
                CsInvoice::class, $invoice->id, $cs->id, 'log', $provider->id,
            );
            $this->activity->log(
                $cs->id, 'continuity_steward', 'finances', ActivitySeverity::Info,
                'cs_payout_received', 'Payment received',
                'You received $' . number_format($invoice->total_cents / 100, 2) . ' for invoice ' . $invoice->invoice_number . '.',
                CsInvoice::class, $invoice->id, $provider->id, 'notification', $provider->id,
            );

            return back()->with('success', 'Invoice ' . $invoice->invoice_number . ' paid.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }
    }

    public function rejectBpInvoice(Request $request, BpInvoice $invoice): RedirectResponse
    {
        $provider = $request->user();
        if ($invoice->practitioner_id !== $provider->id) abort(403);

        $data = $request->validate([
            'reason'  => 'required|string|max:120',
            'message' => 'nullable|string|max:1000',
        ]);

        $status = $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status;
        if (!in_array($status, [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value], true)) {
            return back()->withErrors(['invoice' => 'This invoice cannot be rejected in its current state.']);
        }

        $invoice->update(['status' => InvoiceStatus::Void->value, 'voided_at' => now()]);

        $bp = User::find($invoice->bp_id);
        $this->activity->log(
            $provider->id, 'provider', 'finances', ActivitySeverity::Info,
            'bp_invoice_rejected', 'Invoice rejected',
            'You rejected invoice ' . ($invoice->invoice_number ?? $invoice->id) . '. Reason: ' . $data['reason'],
            BpInvoice::class, $invoice->id, $invoice->bp_id, 'log', $provider->id,
        );
        if ($bp) {
            $this->activity->log(
                $bp->id, 'business_partner', 'finances', ActivitySeverity::Warning,
                'invoice_rejected_by_provider', 'Invoice rejected',
                'Your invoice ' . ($invoice->invoice_number ?? $invoice->id) . ' was rejected. Reason: ' . $data['reason'],
                BpInvoice::class, $invoice->id, $provider->id, 'notification', $provider->id,
            );
        }
        return back()->with('success', 'Invoice rejected — Business Partner notified.');
    }

    public function cancelBpContract(Request $request, BpContract $contract): RedirectResponse
    {
        $provider = $request->user();
        if ($contract->practitioner_id !== $provider->id) abort(403);

        $data = $request->validate([
            'reason'   => 'required|string|max:500',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $contract->update([
            'status'        => ContractStatus::Cancelled->value,
            'cancelled_at'  => now(),
            'cancel_reason' => $data['reason'],
        ]);

        $bp = User::find($contract->bp_id);
        $this->activity->log(
            $provider->id, 'provider', 'finances', ActivitySeverity::Info,
            'bp_contract_cancelled', 'Contract cancelled',
            'Cancelled contract with ' . ($bp?->display_name ?? 'BP') . '.',
            BpContract::class, $contract->id, $contract->bp_id, 'log', $provider->id,
        );
        if ($bp) {
            $this->activity->log(
                $bp->id, 'business_partner', 'finances', ActivitySeverity::Warning,
                'contract_cancelled_by_provider', 'Contract cancelled',
                'Your contract with ' . ($provider->display_name ?? 'Practitioner') . ' has been cancelled.',
                BpContract::class, $contract->id, $provider->id, 'notification', $provider->id,
            );
        }
        return back()->with('success', 'Contract cancelled.');
    }

    public function cancelCsAgreement(Request $request, PlanSteward $steward): RedirectResponse
    {
        $provider = $request->user();
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)->firstOrFail();
        if ($steward->plan_id !== $plan->id) abort(403);

        $data = $request->validate(['reason' => 'required|string|max:500']);

        $steward->update([
            'status'          => StewardStatus::Archived->value,
            'declined_reason' => $data['reason'],
        ]);

        $cs = User::find($steward->steward_id);
        $this->activity->log(
            $provider->id, 'provider', 'steward', ActivitySeverity::Info,
            'cs_agreement_cancelled', 'CS agreement cancelled',
            'Cancelled the Continuity Steward agreement with ' . ($cs?->display_name ?? 'CS') . '.',
            PlanSteward::class, $steward->id, $steward->steward_id, 'log', $provider->id,
        );

        return back()->with('success', 'Continuity Steward agreement cancelled.');
    }

    public function updateCsPayModel(Request $request, PlanSteward $steward): RedirectResponse
    {
        $provider = $request->user();
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)->firstOrFail();
        if ($steward->plan_id !== $plan->id) abort(403);

        $data = $request->validate([
            'payment_model' => 'required|string|in:on_close,net_30,net_60',
            'fee_cents'     => 'nullable|integer|min:0',
        ]);

        $steward->update([
            'payment_terms' => $data['payment_model'],
            'fee_cents'     => $data['fee_cents'] ?? $steward->fee_cents,
        ]);

        $this->activity->log(
            $provider->id, 'provider', 'finances', ActivitySeverity::Info,
            'cs_payment_model_updated', 'CS payment model updated',
            'Updated payment terms for CS to ' . $data['payment_model'] . '.',
            PlanSteward::class, $steward->id, $steward->steward_id, 'log', $provider->id,
        );

        return back()->with('success', 'Payment model updated.');
    }

    public function saveSpendingControls(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'auto_pay'           => 'required|boolean',
            'approval_threshold' => 'required|integer|min:0|max:1000000',
            'monthly_limit'      => 'required|integer|min:0|max:10000000',
        ]);

        $prefs = [
            'fin_autopay_enabled'          => $data['auto_pay'] ? '1' : '0',
            'fin_approval_threshold_cents' => (string) ($data['approval_threshold'] * 100),
            'fin_monthly_limit_cents'      => (string) ($data['monthly_limit'] * 100),
        ];

        foreach ($prefs as $key => $value) {
            // Manual upsert — user_meta uses UUID PK ('um_' prefix), no auto-increment.
            $existing = DB::table('user_meta')
                ->where('user_id', $user->id)
                ->where('meta_key', $key)
                ->first();
            if ($existing) {
                DB::table('user_meta')->where('id', $existing->id)->update([
                    'meta_value' => $value,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('user_meta')->insert([
                    'id'         => 'um_' . Str::lower(Str::random(12)),
                    'user_id'    => $user->id,
                    'meta_key'   => $key,
                    'meta_value' => $value,
                    'meta_type'  => 'string',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->activity->log(
            $user->id, 'provider', 'finances', ActivitySeverity::Info,
            'spending_controls_updated', 'Spending controls updated',
            'Updated autopay + approval + monthly-limit settings.',
            User::class, $user->id, null, 'log', $user->id,
        );

        return back()->with('success', 'Spending controls saved.');
    }

    public function saveAutopay(Request $request, BpContract $contract): RedirectResponse
    {
        $provider = $request->user();
        if ($contract->practitioner_id !== $provider->id) abort(403);

        $data = $request->validate([
            'enabled'   => 'required|boolean',
            'day'       => 'required|string|in:1st,15th,last,due',
            'method_id' => 'nullable|string',
            'notify'    => 'required|string|in:3_days,1_day,same_day,none',
            'limit'     => 'nullable|numeric|min:0',
        ]);

        $key = 'contract_autopay_' . $contract->id;
        $value = json_encode($data);
        $existing = DB::table('user_meta')
            ->where('user_id', $provider->id)
            ->where('meta_key', $key)
            ->first();
        if ($existing) {
            DB::table('user_meta')->where('id', $existing->id)->update(['meta_value' => $value, 'updated_at' => now()]);
        } else {
            DB::table('user_meta')->insert([
                'id' => 'um_' . Str::lower(Str::random(12)),
                'user_id' => $provider->id, 'meta_key' => $key, 'meta_value' => $value,
                'meta_type' => 'json', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return back()->with('success', $data['enabled'] ? 'Auto-pay enabled.' : 'Auto-pay turned off.');
    }

    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => 'required|string|starts_with:pm_',
            'set_default'       => 'nullable|boolean',
        ]);
        $user = $request->user();
        try {
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer(['name' => $user->display_name, 'email' => $user->email]);
            }
            if (!empty($data['set_default'])) {
                $user->updateDefaultPaymentMethod($data['payment_method_id']);
                $user->update(['stripe_payment_method_id' => $data['payment_method_id']]);
            } else {
                $user->addPaymentMethod($data['payment_method_id']);
            }
            return redirect()->route('provider.finances.index', ['tab' => 'methods'])
                ->with('success', 'Payment method saved.');
        } catch (\Throwable $e) {
            return redirect()->route('provider.finances.index', ['tab' => 'methods'])
                ->withErrors(['payment' => 'Could not save payment method. ' . $e->getMessage()]);
        }
    }

    public function exportReport(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'from'         => 'required|date',
            'to'           => 'required|date|after_or_equal:from',
            'includes'     => 'required|array|min:1',
            'includes.*'   => 'string|in:cs,bp,sessions,subscription',
            'format'       => 'required|string|in:csv,pdf,xlsx',
        ]);

        // Build CSV in-memory
        $csv  = "Date,Type,Payee,Description,Amount,Status\n";
        $from = Carbon::parse($data['from'])->startOfDay();
        $to   = Carbon::parse($data['to'])->endOfDay();

        $kindFilter = collect($data['includes'])->flatMap(fn ($k) => match ($k) {
            'cs'           => ['cs_fee'],
            'bp'           => ['bp_invoice'],
            'sessions'     => ['service_session'],
            'subscription' => ['subscription', 'maat_addon'],
            default        => [],
        })->all();

        $rows = PractitionerPayment::where('practitioner_id', $user->id)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('kind', $kindFilter)
            ->orderBy('created_at')
            ->get();

        foreach ($rows as $r) {
            $date   = optional($r->paid_at ?? $r->created_at)->format('Y-m-d');
            $kind   = $r->kind instanceof PractitionerPaymentKind ? $r->kind->value : (string) $r->kind;
            $type   = ucfirst(str_replace('_', ' ', $kind));
            $payee  = str_replace(',', ' ', $r->payment_method_label ?? '');
            $amount = number_format($r->amount_cents / 100, 2);
            $status = $r->status instanceof PractitionerPaymentStatus ? $r->status->value : (string) $r->status;
            $csv .= "{$date},{$type},{$payee},{$payee},{$amount},{$status}\n";
        }

        $this->activity->log(
            $user->id, 'provider', 'finances', ActivitySeverity::Info,
            'finances_exported', 'Finances exported',
            'Exported financial report from ' . $data['from'] . ' to ' . $data['to'] . ' (' . count($rows) . ' rows).',
            User::class, $user->id, null, 'log', $user->id,
        );

        $filename = 'aegis-finances-' . $data['from'] . '-to-' . $data['to'] . '.csv';
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────
    private function resolveDisputeSubjectLabel(Dispute $d): string
    {
        $subject = $d->resolveSubject();
        if (!$subject) return ucfirst(str_replace('_', ' ', $d->subject_type)) . ' (deleted)';
        return match ($d->subject_type) {
            'cs_invoice' => 'CS Invoice ' . ($subject->invoice_number ?? $d->subject_id),
            'bp_invoice' => 'BP Invoice ' . ($subject->invoice_number ?? $d->subject_id),
            default      => ucfirst(str_replace('_', ' ', $d->subject_type)),
        };
    }
}
