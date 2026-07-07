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
    public function __construct(private SubscriptionService $subscriptions) {}

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
}
