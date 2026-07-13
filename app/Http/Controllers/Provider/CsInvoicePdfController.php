<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\CsInvoice;
use App\Services\CsInvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Provider portal — renders a CS invoice PDF.
 * Route: GET /provider/finances/cs-invoices/{invoice}/pdf  → name: provider.finances.cs-invoice.pdf
 *
 * Authorization: only the practitioner who was billed can view.
 */
class CsInvoicePdfController extends Controller
{
    public function __construct(private CsInvoicePdfService $pdf) {}

    public function show(Request $request, CsInvoice $invoice): Response
    {
        abort_unless($invoice->practitioner_id === $request->user()->id, 403);

        return response($this->pdf->render($invoice), 200, [
            'Content-Type'  => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Robots-Tag'  => 'noindex',
        ]);
    }
}
