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
                                    ->orderByDesc('paid_at')->limit(50)->get(),
            'csServiceFees'   => BpInvoice::where('practitioner_id', $user->id)
                                    ->orderByDesc('issued_at')->limit(50)->get(),
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
