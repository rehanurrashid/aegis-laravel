<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Models\BpInvoice;
use App\Models\BpPayout;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancesController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $ytd = BpInvoice::where('bp_id', $user->id)->where('status', 'paid')
            ->whereYear('paid_at', now()->year)->sum('total_cents');

        return Inertia::render('BusinessPartner/Finances', [
            'ytdCents'      => (int) $ytd,
            'payoutHistory' => BpPayout::where('bp_id', $user->id)
                                 ->orderByDesc('created_at')->limit(50)->get(),
        ]);
    }
}
