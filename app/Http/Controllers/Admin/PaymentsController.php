<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $filters = $request->only(['q', 'status']);
        $q       = $filters['q']      ?? '';
        $status  = $filters['status'] ?? '';

        // Build paginated payments query matching Payments.vue expectations
        $query = \App\Models\PractitionerPayment::with('practitioner')
            ->when($status, fn($qb) => $qb->where('status', $status))
            ->when($q, fn($qb) => $qb->whereHas('practitioner', fn($u) => $u->where('display_name', 'like', "%{$q}%"))
                ->orWhere('stripe_payment_intent_id', 'like', "%{$q}%"))
            ->orderByDesc('created_at');

        $payments = $query->paginate(30)->through(fn($p) => [
            'id'          => $p->id,
            'created_at'  => $p->created_at,
            'user_name'   => $p->practitioner?->display_name ?? 'Unknown',
            'description' => $p->description ?? ucfirst($p->kind ?? 'Payment'),
            'amount_cents' => $p->amount_cents,
            'status'      => match($p->status) {
                'paid'    => 'succeeded',
                'failed'  => 'failed',
                'refunded'=> 'refunded',
                default   => $p->status,
            },
            'stripe_payment_intent_id' => $p->stripe_payment_intent_id,
        ]);

        // Summary stats
        $now = now();
        $summary = [
            'total_cents'    => \App\Models\PractitionerPayment::where('status', 'paid')->sum('amount_cents'),
            'mtd_cents'      => \App\Models\PractitionerPayment::where('status', 'paid')
                                    ->whereYear('paid_at', $now->year)->whereMonth('paid_at', $now->month)
                                    ->sum('amount_cents'),
            'ytd_cents'      => \App\Models\PractitionerPayment::where('status', 'paid')
                                    ->whereYear('paid_at', $now->year)->sum('amount_cents'),
            'failed_count'   => \App\Models\PractitionerPayment::where('status', 'failed')
                                    ->where('created_at', '>=', $now->subDays(30))->count(),
            'refunded_count' => \App\Models\PractitionerPayment::where('status', 'refunded')
                                    ->where('created_at', '>=', $now->subDays(30))->count(),
        ];

        return Inertia::render('Admin/Payments', [
            'payments' => $payments,
            'summary'  => $summary,
            'filters'  => $filters,
        ]);
    }

    public function show(string $id): Response
    {
        return Inertia::render('Admin/PaymentDetail', [
            'payment' => $this->payments->getById($id),
        ]);
    }

    public function refund(Request $request, PractitionerPayment $payment): RedirectResponse
    {
        // Default to full refund if no amount provided (Payments.vue sends empty body)
        $amountCents = $request->input('amount_cents', $payment->amount_cents);
        $this->payments->refundPayment($request->user(), $payment, (int) $amountCents);
        return back()->with('success', 'Refund of $' . number_format($amountCents / 100, 2) . ' processed.');
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
