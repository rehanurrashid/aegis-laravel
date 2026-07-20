<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Http\Controllers\Controller;
use App\Models\BpInvoice;
use App\Models\CsPayout;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancesController extends Controller
{
    public function __construct(private InvoiceService $invoices) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $ytd = CsPayout::where('cs_id', $user->id)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return Inertia::render('ContinuitySteward/Finances', [
            'invoices' => BpInvoice::where('bp_id', $user->id)->orderByDesc('issued_at')->get(),
            'payouts'  => CsPayout::where('cs_id', $user->id)->orderByDesc('created_at')->get(),
            'ytdCents' => (int) ($ytd * 100),
            'pricing'  => config('aegis.pricing'),
        ]);
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        // Stub for CS invoice creation flow — concrete logic depends on contract model
        return back()->with('success', 'Invoice request submitted.');
    }

    public function feeAmendment(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_id'     => 'required|exists:continuity_plans,id',
            'new_amount'  => 'required|integer|min:0',
            'reason'      => 'required|string|min:10|max:1000',
        ]);
        return back()->with('success', 'Fee amendment requested.');
    }
}
