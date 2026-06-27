<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Models\BpMilestone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MilestonesController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('BusinessPartner/Milestones', [
            'milestones' => BpMilestone::whereHas('contract', fn ($q) => $q->where('bp_id', $request->user()->id))->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contract_id' => 'required|exists:bp_contracts,id',
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'amount_cents'=> 'required|integer|min:0',
            'due_date'    => 'nullable|date|after:today',
        ]);
        BpMilestone::create(array_merge($data, [
            'id'         => 'bm_' . Str::lower(Str::random(12)),
            'status'     => 'pending',
            'created_at' => now(),
        ]));
        return back()->with('success', 'Milestone added.');
    }

    public function submit(Request $request, BpMilestone $milestone): RedirectResponse
    {
        $note = $request->validate(['notes' => 'nullable|string|max:2000'])['notes'] ?? null;
        $milestone->update(['status' => 'submitted', 'submitted_at' => now(), 'submission_notes' => $note]);
        return back()->with('success', 'Milestone submitted.');
    }
}
