<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Http\Controllers\Controller;
use App\Models\CsInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoicesController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('continuity-steward/Invoices', [
            'invoices' => CsInvoice::where('cs_id', $user?->id)
                ->orderByDesc('created_at')
                ->limit(100)
                ->get(),
        ]);
    }

    public function show(CsInvoice $invoice): Response
    {
        return Inertia::render('continuity-steward/Invoices', [
            'invoice' => $invoice,
        ]);
    }
}
