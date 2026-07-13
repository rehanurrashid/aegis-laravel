<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Http\Controllers\Controller;
use App\Models\CsInvoice;
use App\Services\CsInvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CS portal — renders a CS invoice as a printable HTML document.
 * Route: GET /cs/invoices/{invoice}/pdf  → name: cs.invoices.pdf
 *
 * Authorization: only the CS who owns the invoice can view.
 */
class InvoicePdfController extends Controller
{
    public function __construct(private CsInvoicePdfService $pdf) {}

    public function show(Request $request, CsInvoice $invoice): Response
    {
        abort_unless($invoice->cs_id === $request->user()->id, 403);

        return response($this->pdf->render($invoice), 200, [
            'Content-Type'  => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Robots-Tag'  => 'noindex',
        ]);
    }
}
