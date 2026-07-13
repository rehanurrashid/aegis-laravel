<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ServiceSession;

/**
 * ServiceSessionPdfService — thin adapter over AegisPdfService for clinical
 * session invoices. Kept as a named class so ServicesController and any
 * future callers can inject it directly.
 */
class ServiceSessionPdfService
{
    public function __construct(private AegisPdfService $pdf) {}

    public function render(ServiceSession $session): string
    {
        return $this->pdf->serviceSession($session);
    }
}
