<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\CreateInvoiceRequest;
use App\Models\BpContract;
use App\Models\BpInvoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoicesController extends Controller
{
    public function __construct(private InvoiceService $invoices) {}

    public function index(Request $request): Response
    {
        return Inertia::render('BusinessPartner/Invoices', [
            'invoices' => $this->invoices->getForBp($request->user()->id),
        ]);
    }

    public function store(CreateInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $contract = BpContract::findOrFail($data['contract_id']);
        $this->authorize('create', [\App\Models\BpInvoice::class, $contract]);

        $invoice = $this->invoices->create($contract, $request->user(), $data);
        foreach ($data['line_items'] as $li) {
            $this->invoices->addLineItem($invoice, $li);
        }
        return back()->with('success', 'Invoice created.');
    }

    public function send(BpInvoice $invoice): RedirectResponse
    {
        abort_unless($invoice->bp_id === request()->user()->id, 403);
        $this->invoices->send($invoice);
        return back()->with('success', 'Invoice sent.');
    }

    public function void(BpInvoice $invoice): RedirectResponse
    {
        $this->authorize('void', $invoice);
        $this->invoices->void($invoice);
        return back()->with('success', 'Invoice voided.');
    }
}
