<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BpContract;
use App\Models\User;

/**
 * ContractPdfService — thin adapter over AegisPdfService.
 *
 * Kept as a named class so Provider\ContractPdfController and
 * BusinessPartner\ContractPdfController don't need to change.
 */
class ContractPdfService
{
    public function __construct(private AegisPdfService $pdf) {}

    public function render(BpContract $contract, User $viewer): string
    {
        return $this->pdf->contract($contract, $viewer);
    }
}
