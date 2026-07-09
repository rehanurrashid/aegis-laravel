<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\BpInvoice;
use App\Models\PractitionerPayment;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancesController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptions,
        private \App\Services\PayoutService $payouts,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('Provider/Finances', [
            'subscription'    => $this->subscriptions->getStatus($user),
            'paymentMethods'  => method_exists($user, 'paymentMethods') ? $user->paymentMethods() : [],
            'invoiceHistory'  => PractitionerPayment::where('practitioner_id', $user->id)
                                    ->orderByDesc('created_at')->limit(50)->get()
                                    ->map(fn($p) => [
                                        'id'           => $p->id,
                                        'kind'         => $p->kind instanceof \App\Enums\PractitionerPaymentKind ? $p->kind->value : (string) $p->kind,
                                        'kind_label'   => $p->kind instanceof \App\Enums\PractitionerPaymentKind ? $p->kind->label() : (string) $p->kind,
                                        'amount'       => '$' . number_format($p->amount_cents / 100, 2),
                                        'amount_cents' => $p->amount_cents,
                                        'status'       => $p->status instanceof \App\Enums\PractitionerPaymentStatus ? $p->status->value : (string) $p->status,
                                        'status_label' => $p->status instanceof \App\Enums\PractitionerPaymentStatus ? $p->status->label() : (string) $p->status,
                                        'method'       => $p->payment_method_label ?? '—',
                                        'date'         => $p->paid_at?->format('M j, Y') ?? $p->created_at?->format('M j, Y') ?? '—',
                                    ])->values(),
            'csServiceFees'   => BpInvoice::where('practitioner_id', $user->id)
                                    ->orderByDesc('issued_at')->limit(50)->get(),
            'serviceEarnings' => PractitionerPayment::where('practitioner_id', $user->id)
                                    ->where('kind', \App\Enums\PractitionerPaymentKind::ServiceSession->value)
                                    ->orderByDesc('created_at')->limit(50)->get()
                                    ->map(fn($p) => [
                                        'id'           => $p->id,
                                        'amount'       => '$' . number_format($p->amount_cents / 100, 2),
                                        'amount_cents' => $p->amount_cents,
                                        'status'       => $p->status instanceof \App\Enums\PractitionerPaymentStatus ? $p->status->value : (string) $p->status,
                                        'date'         => $p->created_at?->format('M j, Y') ?? '—',
                                    ])->values(),
        ]);
    }

    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate(['payment_method_id' => 'required|string']);
        if (method_exists($request->user(), 'updateDefaultPaymentMethod')) {
            $request->user()->updateDefaultPaymentMethod($data['payment_method_id']);
        }
        return back()->with('success', 'Payment method saved.');
    }
    public function payCSInvoice(Request $request, \App\Models\CsInvoice $invoice): \Illuminate\Http\RedirectResponse
    {
        $provider = $request->user();

        // Guard: must be the provider this invoice is billed to
        if ($invoice->provider_id !== $provider->id) {
            abort(403, 'Not authorised to pay this invoice.');
        }

        if ($invoice->status === 'paid') {
            return back()->withErrors(['invoice' => 'This invoice has already been paid.']);
        }

        $cs = \App\Models\User::find($invoice->cs_id);
        if (!$cs) {
            return back()->withErrors(['invoice' => 'Continuity Steward not found.']);
        }

        try {
            $result = $this->payouts->chargeProviderToCs(
                provider:    $provider,
                cs:          $cs,
                amountCents: $invoice->amount_cents,
                meta:        ['cs_invoice_id' => $invoice->id],
                description: 'CS invoice payment — Aegis',
            );

            $invoice->update([
                'status'                   => $result['status'],
                'stripe_payment_intent_id' => $result['stripe_payment_intent_id'],
                'paid_at'                  => $result['status'] === 'paid' ? now() : null,
            ]);

            // Record in practitioner_payments
            \App\Models\PractitionerPayment::create([
                'id'                       => 'pp_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)),
                'practitioner_id'          => $provider->id,
                'kind'                     => 'cs_fee',
                'amount_cents'             => $invoice->amount_cents,
                'currency'                 => 'usd',
                'stripe_payment_intent_id' => $result['stripe_payment_intent_id'],
                'status'                   => $result['status'],
                'paid_at'                  => $result['status'] === 'paid' ? now() : null,
                'description'              => 'CS invoice #' . $invoice->id,
            ]);

            return back()->with('success', 'Invoice paid successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }
    }

}
