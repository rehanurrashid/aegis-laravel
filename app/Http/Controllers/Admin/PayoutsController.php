<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManualPayoutRequest;
use App\Models\BpPayout;
use App\Services\AdminPayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayoutsController extends Controller
{
    public function __construct(private AdminPayoutService $payouts) {}

    public function index(Request $request): Response
    {
        $status = $request->query('status');
        return Inertia::render('Admin/Payouts', [
            'pending' => $this->payouts->listPending(),
            'all'     => $this->payouts->listAll($status),
            'filters' => compact('status'),
        ]);
    }

    public function release(ManualPayoutRequest $request, BpPayout $payout): RedirectResponse
    {
        $this->payouts->releaseManually($request->user(), $payout, $request->validated()['note'] ?? null);
        return back()->with('success', 'Payout released.');
    }

    public function cancel(Request $request, BpPayout $payout): RedirectResponse
    {
        $reason = $request->validate(['reason' => 'nullable|string|max:500'])['reason'] ?? null;
        $this->payouts->cancel($request->user(), $payout, $reason);
        return back()->with('success', 'Payout cancelled.');
    }

    public function retry(Request $request, BpPayout $payout): RedirectResponse
    {
        $this->payouts->retry($request->user(), $payout);
        return back()->with('success', 'Payout retry queued.');
    }
}
