<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundPaymentRequest;
use App\Models\BpPayout;
use App\Models\CsPayout;
use App\Models\PractitionerPayment;
use App\Services\AdminPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentsController extends Controller
{
    public function __construct(private AdminPaymentService $payments) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'user', 'from', 'to']);
        $pending = $this->payments->getPendingPayouts();

        return Inertia::render('Admin/Payments', [
            'ledger'         => $this->payments->getLedger($filters + ['limit' => 200]),
            'failedPayments' => $this->payments->getFailedPayments(),
            'pendingPayouts' => $pending,
            'webhookEvents'  => $this->payments->getWebhookEvents(['limit' => 50]),
            'filters'        => $filters,
            'stats'          => [
                'failed_count'          => $this->payments->getFailedPayments()->count(),
                'pending_payouts_count' => $pending['bp']->count() + $pending['cs']->count(),
            ],
        ]);
    }

    public function show(string $id): Response
    {
        return Inertia::render('Admin/PaymentDetail', [
            'payment' => $this->payments->getById($id),
        ]);
    }

    public function refund(RefundPaymentRequest $request, PractitionerPayment $payment): RedirectResponse
    {
        $this->payments->refundPayment($request->user(), $payment, $request->validated()['amount_cents']);
        return back()->with('success', 'Refund processed.');
    }

    public function retry(Request $request, PractitionerPayment $payment): RedirectResponse
    {
        $this->payments->retryPayment($request->user(), $payment);
        return back()->with('success', 'Payment retry queued.');
    }

    public function releasePayout(Request $request, string $payout): RedirectResponse
    {
        $row = BpPayout::find($payout) ?: CsPayout::find($payout);
        abort_if(!$row, 404);
        $this->payments->releasePayout($request->user(), $row);
        return back()->with('success', 'Payout released.');
    }
}
