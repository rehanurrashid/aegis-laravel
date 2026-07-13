<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BpInvoice;

/**
 * BpInvoicePdfService — thin adapter over AegisPdfService.
 *
 * Kept as a named class so the existing BpInvoicePdfController
 * and any future callers don't need to change their constructor injection.
 */
class BpInvoicePdfService
{
    public function __construct(private AegisPdfService $pdf) {}

    public function render(BpInvoice $invoice): string
    {
        return $this->pdf->bpInvoice($invoice);
    }
}
