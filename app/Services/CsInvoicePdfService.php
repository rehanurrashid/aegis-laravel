<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CsInvoice;

/**
 * CsInvoicePdfService — thin adapter over AegisPdfService for CS invoices.
 */
class CsInvoicePdfService
{
    public function __construct(private AegisPdfService $pdf) {}

    public function render(CsInvoice $invoice): string
    {
        return $this->pdf->csInvoice($invoice);
    }
}
