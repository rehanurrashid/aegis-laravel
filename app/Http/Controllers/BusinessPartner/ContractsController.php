<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Models\BpContract;
use App\Services\ContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractsController extends Controller
{
    public function __construct(private ContractService $contracts) {}

    public function index(Request $request): Response
    {
        return Inertia::render('BusinessPartner/Contracts', [
            'contracts' => $this->contracts->getForBp($request->user()->id),
        ]);
    }

    public function sign(Request $request, BpContract $contract): RedirectResponse
    {
        $this->authorize('sign', $contract);
        $data = $request->validate(['name' => 'required|string|max:100']);
        $this->contracts->sign($contract, $request->user(), $data);
        return back()->with('success', 'Contract signed.');
    }

    public function cancel(Request $request, BpContract $contract): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        $this->contracts->cancel($contract, $request->user(), $request->input('reason'));
        return back()->with('success', 'Contract cancelled.');
    }
}
