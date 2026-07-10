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

class FinancesController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptions,
        private PayoutService $payouts,
        private ActivityService $activity,
        private DisputeService $disputes,
    ) {}

    // ─────────────────────────────────────────────────────────────────────
    // INDEX — all props the Vue page needs
    // ─────────────────────────────────────────────────────────────────────
    public function index(Request $request): Response
    {
        $user = $request->user();

        // ── CS Invoices (with active dispute map) ──────────────────────────
        $csInvCollection = CsInvoice::where('practitioner_id', $user->id)
            ->with(['cs:id,display_name,slug'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $csInvIds = $csInvCollection->pluck('id')->all();
        $csDisputeMap = Dispute::whereIn('subject_id', $csInvIds)
            ->where('subject_type', 'cs_invoice')
            ->whereNull('resolved_at')
            ->pluck('id', 'subject_id');

        $csInvoices = $csInvCollection->map(function (CsInvoice $inv) use ($csDisputeMap) {
            $status = $inv->status instanceof InvoiceStatus ? $inv->status->value : (string) $inv->status;
            return [
                'id'               => $inv->id,
                'invoice_number'   => $inv->invoice_number,
                'cs_name'          => $inv->cs?->display_name ?? '—',
                'cs_slug'          => $inv->cs?->slug ?? null,
                'total_cents'      => (int) $inv->total_cents,
                'status'           => $status,
                'issued_at'        => $inv->issued_at?->toDateString(),
                'due_at'           => $inv->due_at?->format('M j, Y'),
                'payable'          => in_array($status, [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value], true),
                'active_dispute_id'=> $csDisputeMap[$inv->id] ?? null,
            ];
        })->values();

        // ── BP Invoices (with active dispute map) ──────────────────────────
        $bpInvCollection = BpInvoice::where('practitioner_id', $user->id)
            ->with(['bp:id,display_name,slug', 'contract:id,title'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $bpInvIds = $bpInvCollection->pluck('id')->all();
        $bpDisputeMap = Dispute::whereIn('subject_id', $bpInvIds)
            ->where('subject_type', 'bp_invoice')
            ->whereNull('resolved_at')
            ->pluck('id', 'subject_id');

        $bpInvoices = $bpInvCollection->map(function (BpInvoice $inv) use ($bpDisputeMap) {
            $status = $inv->status instanceof InvoiceStatus ? $inv->status->value : (string) $inv->status;
            $issuedAt = $inv->issued_at;
            return [
                'id'               => $inv->id,
                'invoice_number'   => $inv->invoice_number,
                'bp_name'          => $inv->bp?->display_name ?? '—',
                'bp_slug'          => $inv->bp?->slug ?? null,
                'bp_connected'     => (bool) ($inv->bp?->stripe_connected ?? false),
                'contract_title'   => $inv->contract?->title ?? $inv->notes ?? '—',
                'total_cents'      => (int) $inv->total_cents,
                'status'           => $status,
                'issued_at'        => $issuedAt?->toDateString(),
                'issued_month'     => $issuedAt?->format('F Y'),
                'due_at'           => $inv->due_at?->format('M j, Y'),
                'notes_short'      => $inv->notes ? mb_strimwidth($inv->notes, 0, 60, '…') : null,
                'payable'          => in_array($status, [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value], true),
                'active_dispute_id'=> $bpDisputeMap[$inv->id] ?? null,
            ];
        })->values();

        // ── allInvoices — merged for tx receipt lookup ─────────────────────
        $allInvoices = $bpInvoices->map(fn ($inv) => array_merge($inv, ['__type' => 'bp']));

        // ── Active BP Contracts ────────────────────────────────────────────
        $activeContracts = BpContract::where('practitioner_id', $user->id)
            ->where('status', ContractStatus::Active->value)
            ->with(['bp:id,display_name,slug,stripe_connected'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (BpContract $con) {
                // Find last paid invoice for this contract
                $lastPaid = BpInvoice::where('contract_id', $con->id)
                    ->where('status', InvoiceStatus::Paid->value)
                    ->orderByDesc('paid_at')
                    ->value('paid_at');

                return [
                    'id'             => $con->id,
                    'title'          => $con->title,
                    'bp_name'        => $con->bp?->display_name ?? '—',
                    'bp_slug'        => $con->bp?->slug ?? null,
                    'bp_connected'   => (bool) ($con->bp?->stripe_connected ?? false),
                    'total_cents'    => (int) $con->total_value_cents,
                    'billing_type'   => $con->payment_type ?? 'one_time',
                    'term'           => ($con->started_at?->format('M Y') ?? '—') . ' – ' . ($con->completed_at?->format('M Y') ?? 'Ongoing'),
                    'last_paid'      => $lastPaid ? Carbon::parse($lastPaid)->format('M j, Y') : null,
                    'autopay_enabled'=> false,  // not yet in schema; always false
                    'autopay_day'    => '1st',
                    'autopay_method_id' => null,
                    'autopay_notify' => '3_days',
                    'autopay_limit'  => null,
                    'scope'          => null,
                    'status'         => $con->status instanceof ContractStatus ? $con->status->value : (string) $con->status,
                ];
            })->values();

        // ── CS Stewards (active only, for CS Wallet tab) ──────────────────
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
                    $isPrimary = $role === 'primary';
                    $displayName = $steward?->display_name ?? '—';
                    return [
                        'id'              => $ps->id,
                        'steward_id'      => $ps->steward_id,
                        'display_name'    => $displayName,
                        'slug'            => $steward?->slug ?? null,
                        'initials'        => strtoupper(mb_substr($displayName, 0, 2)),
                        'role'            => $role,
                        'role_label'      => $isPrimary ? 'Primary Continuity Steward' : ucfirst($role) . ' Continuity Steward',
                        'fee_cents'       => (int) ($ps->fee_cents ?? 0),
                        'payment_model'   => $ps->payment_terms ?? 'retainer',
                        'payment_terms'   => $ps->payment_terms ?? 'on_close',
                        'auto_charge'     => (bool) ($ps->auto_charge ?? false),
                        'stripe_connected'=> (bool) ($steward?->stripe_connected ?? false),
                    ];
                })->values();
        }

        // ── CS Agreed Total ────────────────────────────────────────────────
        $csAgreedTotal = $csStewards->sum('fee_cents');

        // ── Transaction History (recent 50, merged BP + CS + subscription) ─
        $recentTxRaw = PractitionerPayment::where('practitioner_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $recentTransactions = $recentTxRaw->map(function (PractitionerPayment $p) {
            $kind = $p->kind instanceof PractitionerPaymentKind ? $p->kind->value : (string) $p->kind;
            $catMap = [
                'cs_fee'      => ['label' => 'CS Finance',      'color' => 'var(--gold-dark)', 'cat' => 'executor'],
                'bp_invoice'  => ['label' => 'Business Partner','color' => 'var(--green)',      'cat' => 'bp'],
                'subscription'=> ['label' => 'Subscription',   'color' => 'var(--blue)',       'cat' => 'aegis'],
                'maat_addon'  => ['label' => 'MAAT Add-on',    'color' => 'var(--purple)',     'cat' => 'aegis'],
                'refund'      => ['label' => 'Refund',         'color' => 'var(--green)',      'cat' => 'aegis'],
                'service_session' => ['label' => 'Service Session', 'color' => 'var(--teal)',  'cat' => 'aegis'],
            ];
            $cat = $catMap[$kind] ?? ['label' => ucfirst($kind), 'color' => 'var(--text-3)', 'cat' => 'aegis'];
            $status = $p->status instanceof PractitionerPaymentStatus ? $p->status->value : (string) $p->status;

            return [
                'id'             => $p->id,
                'date'           => optional($p->paid_at ?? $p->created_at)->format('M j, Y') ?? '—',
                'payee'          => $p->payment_method_label ?? 'Aegis Platform',
                'payee_url'      => null,
                'description'    => $p->payment_method_label ?? ucfirst(str_replace('_', ' ', $kind)),
                'category_label' => $cat['label'],
                'cat'            => $cat['cat'],
                'cat_color'      => $cat['color'],
                'method'         => 'Card',
                'amount'         => -(int) $p->amount_cents,  // outgoing = negative
                'status'         => $status,
                'inv_id'         => null,
                'sort_ts'        => $p->paid_at?->toIso8601String() ?? $p->created_at->toIso8601String(),
            ];
        })->sortByDesc('sort_ts')->values();

        // ── Spend Breakdown ────────────────────────────────────────────────
        $paidPayments = PractitionerPayment::where('practitioner_id', $user->id)
            ->where('status', PractitionerPaymentStatus::Paid->value)
            ->get();

        $spendByCategory = [];
        foreach ($paidPayments as $p) {
            $kind = $p->kind instanceof PractitionerPaymentKind ? $p->kind->value : (string) $p->kind;
            $label = match ($kind) {
                'cs_fee'      => 'CS Fees',
                'bp_invoice'  => 'Business Partners',
                'subscription'=> 'Aegis Subscription',
                'maat_addon'  => 'MAAT Add-on',
                default       => ucfirst(str_replace('_', ' ', $kind)),
            };
            $spendByCategory[$label] = ($spendByCategory[$label] ?? 0) + (int) $p->amount_cents;
        }
        arsort($spendByCategory);
        $totalSpendCents = array_sum($spendByCategory);
        $palette = ['var(--green)', 'var(--blue)', 'var(--gold-dark)', 'var(--orange)', 'var(--purple)'];
        $pi = 0;
        $spendBreakdown = collect($spendByCategory)->map(function ($cents, $label) use ($totalSpendCents, $palette, &$pi) {
            $pct = $totalSpendCents > 0 ? (int) round($cents / $totalSpendCents * 100) : 0;
            return [
                'label'  => $label,
                'amount' => (int) round($cents / 100),
                'color'  => $label === 'Aegis Subscription' ? 'var(--blue)' : $palette[$pi++ % count($palette)],
                'pct'    => $pct,
            ];
        })->values();

        // ── Upcoming Payments (pending BP invoices sorted by due_at) ───────
        $upcomingPayments = $bpInvoices
            ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value])
            ->sortBy('due_at')
            ->map(function ($inv) {
                $dueTs = $inv['due_at'] ? Carbon::parse($inv['due_at']) : null;
                $isUrgent = $dueTs && $dueTs->diffInDays(now(), false) > -4;
                return array_merge($inv, [
                    'due_month' => $dueTs ? strtoupper($dueTs->format('M')) : '—',
                    'due_day'   => $dueTs ? $dueTs->format('j') : '—',
                    'is_urgent' => $isUrgent,
                ]);
            })->values();

        // ── Pending invoice count / total ──────────────────────────────────
        $pendingInvoiceCount = $bpInvoices->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value])->count();
        $pendingInvoiceTotal = $bpInvoices->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value])->sum('total_cents');
        $activeContractCount = $activeContracts->count();

        // ── Total spend / outstanding ──────────────────────────────────────
        $outstanding = $csInvoices->where('payable', true)->sum('total_cents')
                     + $bpInvoices->where('payable', true)->sum('total_cents');

        // ── Payment methods (Cashier + demo fallback) ─────────────────────
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
                    'purpose'     => null,
                ])->values()->toArray();
            } catch (\Throwable) {}
        }
        // Fallback: practitioner_payment_methods table (seeded demo cards)
        if (empty($paymentMethods)) {
            $paymentMethods = DB::table('practitioner_payment_methods')
                ->where('practitioner_id', $user->id)
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
                    'purpose'     => null,
                ])->toArray();
        }

        // ── Disputes ──────────────────────────────────────────────────────
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

        return Inertia::render('provider/Finances', [
            // ── Subscription ─────────────────────────────────────────────
            'subscription'         => $this->subscriptions->getStatus($user),
            // ── Payment methods ───────────────────────────────────────────
            'paymentMethods'       => $paymentMethods,
            'has_valid_default_pm' => (bool) $user->stripe_payment_method_id || !empty(array_filter($paymentMethods, fn($pm) => $pm['is_default'])),
            // ── Invoice lists ─────────────────────────────────────────────
            'csInvoices'           => $csInvoices,
            'bpInvoices'           => $bpInvoices,
            'allInvoices'          => $allInvoices,
            'activeContracts'      => $activeContracts,
            // ── CS Wallet ─────────────────────────────────────────────────
            'csStewards'           => $csStewards,
            'csAgreedTotal'        => (int) $csAgreedTotal,
            // ── Overview ──────────────────────────────────────────────────
            'spendBreakdown'       => $spendBreakdown,
            'recentTransactions'   => $recentTransactions,
            'upcomingPayments'     => $upcomingPayments,
            // ── Aggregates ────────────────────────────────────────────────
            'totalSpendCents'      => (int) $totalSpendCents,
            'outstandingCents'     => (int) $outstanding,
            'pendingInvoiceTotal'  => (int) $pendingInvoiceTotal,
            'pendingInvoiceCount'  => (int) $pendingInvoiceCount,
            'activeContractCount'  => (int) $activeContractCount,
            // ── Disputes ──────────────────────────────────────────────────
            'disputes'             => $disputesList,
            // ── Legacy ────────────────────────────────────────────────────
            'stripeConnected'      => (bool) $user->stripe_connected,
            'paymentHistory'       => [],
            'earnings'             => [],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // PAY CS INVOICE
    // ─────────────────────────────────────────────────────────────────────
    public function payCSInvoice(Request $request, CsInvoice $invoice): RedirectResponse
    {
        $provider = $request->user();

        if ($invoice->practitioner_id !== $provider->id) {
            abort(403, 'Not authorised to pay this invoice.');
        }

        $status = $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status;
        if ($status === InvoiceStatus::Paid->value)     return back()->withErrors(['invoice' => 'This invoice has already been paid.']);
        if ($status === InvoiceStatus::Void->value)     return back()->withErrors(['invoice' => 'This invoice has been voided.']);
        if ($status === InvoiceStatus::Draft->value)    return back()->withErrors(['invoice' => 'This invoice has not been sent yet.']);
        if ($status === InvoiceStatus::Disputed->value) return back()->withErrors(['invoice' => 'This invoice is under dispute.']);

        $cs = User::find($invoice->cs_id);
        if (!$cs) return back()->withErrors(['invoice' => 'Continuity Steward not found.']);

        try {
            $result = $this->payouts->chargeProviderToCs(
                provider:    $provider,
                cs:          $cs,
                amountCents: (int) $invoice->total_cents,
                meta:        ['cs_invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number],
                description: 'CS invoice ' . $invoice->invoice_number . ' — Aegis',
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
                'You paid invoice ' . $invoice->invoice_number . ' for $' . number_format($invoice->total_cents / 100, 2) . ' to ' . ($cs->display_name ?? 'your CS') . '.',
                CsInvoice::class, $invoice->id, $cs->id, 'log', $provider->id,
            );

            $this->activity->log(
                $cs->id, 'continuity_steward', 'finances', ActivitySeverity::Info,
                'cs_payout_received', 'Payment received',
                'You received $' . number_format($invoice->total_cents / 100, 2) . ' for invoice ' . $invoice->invoice_number . '.',
                CsInvoice::class, $invoice->id, $provider->id, 'notification', $provider->id,
            );

            return back()->with('success', 'Invoice ' . $invoice->invoice_number . ' paid successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // REJECT BP INVOICE
    // ─────────────────────────────────────────────────────────────────────
    public function rejectBpInvoice(Request $request, BpInvoice $invoice): RedirectResponse
    {
        $provider = $request->user();

        if ($invoice->practitioner_id !== $provider->id) {
            abort(403);
        }

        $data = $request->validate([
            'reason'  => 'required|string|in:Incorrect amount,Services not delivered,Duplicate invoice,Unauthorized charges,Missing documentation,Other',
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
            'You rejected invoice ' . ($invoice->invoice_number ?? $invoice->id) . ' from ' . ($bp?->display_name ?? 'BP') . '. Reason: ' . $data['reason'],
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

    // ─────────────────────────────────────────────────────────────────────
    // CANCEL BP CONTRACT
    // ─────────────────────────────────────────────────────────────────────
    public function cancelBpContract(Request $request, BpContract $contract): RedirectResponse
    {
        $provider = $request->user();

        if ($contract->practitioner_id !== $provider->id) {
            abort(403);
        }

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
            'You cancelled your contract with ' . ($bp?->display_name ?? 'BP') . '. Reason: ' . $data['reason'],
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

        return back()->with('success', 'Cancellation notice sent — contract cancelled.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // CANCEL CS AGREEMENT (remove plan_steward designation)
    // ─────────────────────────────────────────────────────────────────────
    public function cancelCsAgreement(Request $request, PlanSteward $steward): RedirectResponse
    {
        $provider = $request->user();
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)->firstOrFail();

        if ($steward->plan_id !== $plan->id) {
            abort(403);
        }

        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $steward->update([
            'status'         => \App\Enums\StewardStatus::Archived->value,
            'declined_reason'=> $data['reason'],
        ]);

        $cs = User::find($steward->steward_id);
        $this->activity->log(
            $provider->id, 'provider', 'steward', ActivitySeverity::Info,
            'cs_agreement_cancelled', 'CS agreement cancelled',
            'You cancelled the Continuity Steward agreement with ' . ($cs?->display_name ?? 'CS') . '.',
            PlanSteward::class, $steward->id, $steward->steward_id, 'log', $provider->id,
        );

        return back()->with('success', 'Continuity Steward agreement cancelled — payment authorization ended.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // UPDATE CS PAYMENT MODEL
    // ─────────────────────────────────────────────────────────────────────
    public function updateCsPayModel(Request $request, PlanSteward $steward): RedirectResponse
    {
        $provider = $request->user();
        $plan = ContinuityPlan::where('practitioner_id', $provider->id)->firstOrFail();

        if ($steward->plan_id !== $plan->id) {
            abort(403);
        }

        $data = $request->validate([
            'payment_model' => 'required|string|in:retainer,annual_fee,retainer_annual',
        ]);

        // Map payment_model to payment_terms
        $termMap = [
            'retainer'        => 'on_close',
            'annual_fee'      => 'net_30',
            'retainer_annual' => 'net_60',
        ];

        $steward->update([
            'payment_terms' => $termMap[$data['payment_model']] ?? 'on_close',
        ]);

        $this->activity->log(
            $provider->id, 'provider', 'finances', ActivitySeverity::Info,
            'cs_payment_model_updated', 'CS payment model updated',
            'Updated payment model for CS to ' . $data['payment_model'] . '.',
            PlanSteward::class, $steward->id, $steward->steward_id, 'log', $provider->id,
        );

        return back()->with('success', 'Payment model updated.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // SAVE SPENDING CONTROLS (user_meta)
    // ─────────────────────────────────────────────────────────────────────
    public function saveSpendingControls(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'auto_pay'           => 'nullable|boolean',
            'approval_threshold' => 'nullable|integer|min:0|max:999999',
            'monthly_limit'      => 'nullable|integer|min:0|max:9999999',
        ]);

        $prefs = [
            'fin_autopay_enabled'         => $data['auto_pay'] ? '1' : '0',
            'fin_approval_threshold_cents' => (string) (($data['approval_threshold'] ?? 500) * 100),
            'fin_monthly_limit_cents'      => (string) (($data['monthly_limit'] ?? 5000) * 100),
        ];

        foreach ($prefs as $key => $value) {
            $id = 'um_' . Str::lower(Str::random(12));
            DB::table('user_meta')->upsert(
                [['id' => $id, 'user_id' => $user->id, 'meta_key' => $key, 'meta_value' => $value, 'meta_type' => 'string', 'created_at' => now(), 'updated_at' => now()]],
                ['user_id', 'meta_key'],
                ['meta_value', 'updated_at']
            );
        }

        return back()->with('success', 'Spending controls saved.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // SAVE AUTOPAY SETTINGS
    // ─────────────────────────────────────────────────────────────────────
    public function saveAutopay(Request $request, BpContract $contract): RedirectResponse
    {
        $provider = $request->user();

        if ($contract->practitioner_id !== $provider->id) {
            abort(403);
        }

        $data = $request->validate([
            'enabled'   => 'required|boolean',
            'day'       => 'required|string|in:1st,15th,last,due',
            'method_id' => 'nullable|string',
            'notify'    => 'required|string|in:3_days,1_day,same_day,none',
            'limit'     => 'nullable|numeric|min:0',
        ]);

        // Autopay not yet in schema — store as user_meta on the contract
        $key = 'contract_autopay_' . $contract->id;
        $value = json_encode($data);
        $id = 'um_' . Str::lower(Str::random(12));
        DB::table('user_meta')->upsert(
            [['id' => $id, 'user_id' => $provider->id, 'meta_key' => $key, 'meta_value' => $value, 'meta_type' => 'json', 'created_at' => now(), 'updated_at' => now()]],
            ['user_id', 'meta_key'],
            ['meta_value', 'updated_at']
        );

        $msg = $data['enabled'] ? 'Auto-pay enabled.' : 'Auto-pay turned off.';
        return back()->with('success', $msg);
    }

    // ─────────────────────────────────────────────────────────────────────
    // STORE PAYMENT METHOD
    // ─────────────────────────────────────────────────────────────────────
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
            return back()->with('success', 'Payment method saved.');
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => 'Could not save payment method. ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────
    private function resolveDisputeSubjectLabel(Dispute $d): string
    {
        $subject = $d->resolveSubject();
        if (!$subject) {
            return ucfirst(str_replace('_', ' ', $d->subject_type)) . ' (deleted)';
        }
        return match ($d->subject_type) {
            'cs_invoice' => 'CS Invoice ' . ($subject->invoice_number ?? $d->subject_id),
            'bp_invoice' => 'BP Invoice ' . ($subject->invoice_number ?? $d->subject_id),
            default      => ucfirst(str_replace('_', ' ', $d->subject_type)),
        };
    }
}
