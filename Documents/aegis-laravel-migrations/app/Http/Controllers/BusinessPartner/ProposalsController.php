<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Models\BpProposal;
use App\Services\ProposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProposalsController extends Controller
{
    public function __construct(private ProposalService $proposals) {}

    public function index(Request $request): Response
    {
        return Inertia::render('BusinessPartner/Proposals', [
            'proposals' => $this->proposals->getForBp($request->user()->id),
        ]);
    }

    public function withdraw(Request $request, BpProposal $proposal): RedirectResponse
    {
        abort_unless($proposal->bp_id === $request->user()->id, 403);
        $this->proposals->withdraw($proposal);
        return back()->with('success', 'Proposal withdrawn.');
    }
}
