<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\BpInvoice;
use App\Services\BpInvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BpInvoicePdfController extends Controller
{
    public function __construct(private BpInvoicePdfService $pdf) {}

    public function show(Request $request, BpInvoice $invoice): Response
    {
        abort_unless($invoice->practitioner_id === $request->user()->id, 403);

        $html = $this->pdf->render($invoice);

        return response($html, 200, [
            'Content-Type'  => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Robots-Tag'  => 'noindex',
        ]);
    }
}
