<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\BpContract;
use App\Services\ContractPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContractPdfController extends Controller
{
    public function __construct(private ContractPdfService $pdf) {}

    /**
     * Render the contract as a printable HTML page (browser Print → Save as PDF).
     * Authorization: only provider who owns the contract can view.
     */
    public function show(Request $request, BpContract $contract): Response
    {
        abort_unless($contract->practitioner_id === $request->user()->id, 403);

        $html = $this->pdf->render($contract, $request->user());

        return response($html, 200, [
            'Content-Type'  => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Robots-Tag'  => 'noindex',
        ]);
    }
}
