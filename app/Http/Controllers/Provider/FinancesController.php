<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Enums\ActivitySeverity;
use App\Enums\InvoiceStatus;
use App\Enums\PayoutStatus;
use App\Enums\PractitionerPaymentKind;
use App\Enums\PractitionerPaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\BpInvoice;
use App\Models\CsInvoice;
use App\Models\CsPayout;
use App\Models\PractitionerPayment;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\PayoutService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FinancesController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptions,
        private PayoutService $payouts,
        private ActivityService $activity,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // Provider-issued spend history (subscription, MAAT, CS fees, BP invoices, sessions)
        $paymentHistory = PractitionerPayment::where('practitioner_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (PractitionerPayment $p) => [
                'id'           => $p->id,
                'kind'         => $p->kind instanceof PractitionerPaymentKind ? $p->kind->value : (string) $p->kind,
                'kind_label'   => $p->kind instanceof PractitionerPaymentKind ? $p->kind->label() : (string) $p->kind,
                'amount_cents' => (int) $p->amount_cents,
                'status'       => $p->status instanceof PractitionerPaymentStatus ? $p->status->value : (string) $p->status,
                'method'       => $p->payment_method_label ?? '—',
                'date'         => optional($p->paid_at ?? $p->created_at)->format('M j, Y') ?? '—',
            ])->values();

        // Outstanding CS invoices (billed to this provider)
        $csInvoices = CsInvoice::where('practitioner_id', $user->id)
            ->with(['cs:id,display_name,slug'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function (CsInvoice $inv) {
                $status = $inv->status instanceof InvoiceStatus ? $inv->status->value : (string) $inv->status;
                return [
                    'id'             => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'cs_name'        => $inv->cs?->display_name ?? '—',
                    'total_cents'    => (int) $inv->total_cents,
                    'status'         => $status,
                    'issued_at'      => $inv->issued_at?->format('M j, Y'),
                    'due_at'         => $inv->due_at?->format('M j, Y'),
                    'payable'        => in_array($status, [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value], true),
                ];
            });

        // Outstanding BP invoices (billed to this provider)
        $bpInvoices = BpInvoice::where('practitioner_id', $user->id)
            ->with(['bp:id,display_name,slug'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function (BpInvoice $inv) {
                $status = $inv->status instanceof InvoiceStatus ? $inv->status->value : (string) $inv->status;
                return [
                    'id'             => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'bp_name'        => $inv->bp?->display_name ?? '—',
                    'total_cents'    => (int) $inv->total_cents,
                    'status'         => $status,
                    'issued_at'      => $inv->issued_at?->format('M j, Y'),
                    'due_at'         => $inv->due_at?->format('M j, Y'),
                    'payable'        => in_array($status, [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value], true),
                ];
            });

        // Provider earnings (as recipient) — service sessions
        $earnings = PractitionerPayment::where('practitioner_id', $user->id)
            ->where('kind', PractitionerPaymentKind::ServiceSession->value)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (PractitionerPayment $p) => [
                'id'           => $p->id,
                'amount_cents' => (int) $p->amount_cents,
                'status'       => $p->status instanceof PractitionerPaymentStatus ? $p->status->value : (string) $p->status,
                'date'         => optional($p->paid_at ?? $p->created_at)->format('M j, Y') ?? '—',
            ])->values();

        // Aggregate spend / outstanding
        $totalSpend = PractitionerPayment::where('practitioner_id', $user->id)
            ->where('status', PractitionerPaymentStatus::Paid->value)
            ->whereIn('kind', [
                PractitionerPaymentKind::CsFee->value,
                PractitionerPaymentKind::BpInvoice->value,
                PractitionerPaymentKind::Subscription->value,
                PractitionerPaymentKind::MaatAddon->value,
            ])
            ->sum('amount_cents');

        $outstanding = $csInvoices->where('payable', true)->sum('total_cents')
                     + $bpInvoices->where('payable', true)->sum('total_cents');

        return Inertia::render('provider/Finances', [
            'subscription'    => $this->subscriptions->getStatus($user),
            'paymentMethods'  => method_exists($user, 'paymentMethods') ? collect($user->paymentMethods())->map(fn ($pm) => [
                'id'        => $pm->id,
                'brand'     => $pm->card->brand ?? 'card',
                'last4'     => $pm->card->last4 ?? '••••',
                'exp_month' => $pm->card->exp_month ?? null,
                'exp_year'  => $pm->card->exp_year ?? null,
                'is_default'=> $user->stripe_payment_method_id === $pm->id,
            ])->values() : [],
            'csInvoices'      => $csInvoices,
            'bpInvoices'      => $bpInvoices,
            'paymentHistory'  => $paymentHistory,
            'earnings'        => $earnings,
            'totalSpendCents' => (int) $totalSpend,
            'outstandingCents'=> (int) $outstanding,
            'stripeConnected' => (bool) $user->stripe_connected,
        ]);
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
                // Mirror to users.stripe_payment_method_id so peer-payment charges can find it
                $user->update(['stripe_payment_method_id' => $data['payment_method_id']]);
            } else {
                $user->addPaymentMethod($data['payment_method_id']);
            }
            return back()->with('success', 'Payment method saved.');
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => 'Could not save payment method. ' . $e->getMessage()]);
        }
    }

    public function payCSInvoice(Request $request, CsInvoice $invoice): RedirectResponse
    {
        $provider = $request->user();

        // Guard: this invoice must be billed to the current provider
        if ($invoice->practitioner_id !== $provider->id) {
            abort(403, 'Not authorised to pay this invoice.');
        }

        $status = $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status;
        if ($status === InvoiceStatus::Paid->value) {
            return back()->withErrors(['invoice' => 'This invoice has already been paid.']);
        }
        if ($status === InvoiceStatus::Void->value) {
            return back()->withErrors(['invoice' => 'This invoice has been voided.']);
        }
        if ($status === InvoiceStatus::Draft->value) {
            return back()->withErrors(['invoice' => 'This invoice has not been sent yet.']);
        }
        if ($status === InvoiceStatus::Disputed->value) {
            return back()->withErrors(['invoice' => 'This invoice is under dispute. Wait for the dispute to be resolved before paying.']);
        }

        $cs = User::find($invoice->cs_id);
        if (!$cs) {
            return back()->withErrors(['invoice' => 'Continuity Steward not found.']);
        }

        // W-9 soft-warn (Rev 2 §0.10 — Stripe handles 1099-K reporting for
        // destination charges; we don't need to hard-block. Log for admin
        // audit only.)
        $w9 = method_exists($cs, 'bpTaxDocuments')
            ? $cs->bpTaxDocuments()->where('doc_type', 'w9')->latest()->first()
            : null;
        if (!$w9 || (string) $w9->status !== \App\Enums\TaxDocStatus::Verified->value) {
            $this->activity->log(
                $provider->id, 'provider', 'finances',
                \App\Enums\ActivitySeverity::Warning,
                'cs_invoice_pay_no_w9', 'Payment to CS without verified W-9',
                'CS ' . ($cs->display_name ?? $cs->id) . ' does not have a verified W-9 on file. Payment proceeded; Stripe Connect will issue 1099-K if thresholds are met.',
                CsInvoice::class, $invoice->id, $cs->id, 'log', $provider->id,
            );
        }

        try {
            $result = $this->payouts->chargeProviderToCs(
                provider:    $provider,
                cs:          $cs,
                amountCents: (int) $invoice->total_cents,
                meta:        ['cs_invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number],
                description: 'CS invoice ' . $invoice->invoice_number . ' — Aegis',
            );

            // Persist the invoice
            $invoice->update([
                'status'                   => $result['status'] === 'paid' ? InvoiceStatus::Paid->value : $status,
                'stripe_payment_intent_id' => $result['stripe_payment_intent_id'],
                'stripe_transfer_id'       => $result['stripe_transfer_id'] ?? null,
                'paid_at'                  => $result['status'] === 'paid' ? now() : null,
            ]);

            // Record on provider side (spend history)
            PractitionerPayment::create([
                'id'                   => (string) Str::uuid(),
                'practitioner_id'      => $provider->id,
                'kind'                 => PractitionerPaymentKind::CsFee->value,
                'amount_cents'         => (int) $invoice->total_cents,
                'currency'             => 'USD',
                'status'               => $result['status'] === 'paid'
                                            ? PractitionerPaymentStatus::Paid->value
                                            : PractitionerPaymentStatus::Pending->value,
                'payment_method_label' => 'CS Invoice ' . $invoice->invoice_number,
                'stripe_charge_id'     => $result['stripe_payment_intent_id'],
                'stripe_transfer_id'   => $result['stripe_transfer_id'] ?? null,
                'paid_at'              => $result['status'] === 'paid' ? now() : null,
            ]);

            // Record on CS side (payout history)
            CsPayout::create([
                'id'                => (string) Str::uuid(),
                'cs_id'             => $cs->id,
                'amount_cents'      => (int) $invoice->total_cents,
                'currency'          => 'USD',
                'status'            => $result['status'] === 'paid' ? PayoutStatus::Paid->value : PayoutStatus::Pending->value,
                'description'       => 'CS invoice ' . $invoice->invoice_number,
                'stripe_payout_id'  => $result['stripe_transfer_id'] ?? $result['stripe_payment_intent_id'],
                'paid_at'           => $result['status'] === 'paid' ? now() : null,
            ]);

            // Activity fan-out — provider log + CS notification
            $this->activity->log(
                $provider->id, 'provider', 'finances',
                ActivitySeverity::Info,
                'cs_invoice_paid', 'CS invoice paid',
                'You paid invoice ' . $invoice->invoice_number . ' for $' . number_format($invoice->total_cents / 100, 2) . ' to ' . ($cs->display_name ?? 'your CS') . '.',
                CsInvoice::class, $invoice->id, $cs->id, 'log', $provider->id,
            );

            $this->activity->log(
                $cs->id, 'continuity_steward', 'finances',
                ActivitySeverity::Info,
                'cs_payout_received', 'Payment received',
                'You received $' . number_format($invoice->total_cents / 100, 2) . ' for invoice ' . $invoice->invoice_number . '.',
                CsInvoice::class, $invoice->id, $provider->id, 'notification', $provider->id,
            );

            return back()->with('success', 'Invoice ' . $invoice->invoice_number . ' paid successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }
    }
}
