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
        $milestones = BpMilestone::with('contract:id,title,bp_id')
            ->whereHas('contract', fn ($q) => $q->where('bp_id', $request->user()->id))
            ->orderByDesc('created_at')
            ->get()
            ->map(function (BpMilestone $m) {
                $status = $m->status instanceof \BackedEnum ? $m->status->value : (string) $m->status;
                return [
                    'id'             => $m->id,
                    'title'          => $m->title,
                    'description'    => $m->description,
                    'contract_id'    => $m->contract_id,
                    'contract_title' => $m->contract?->title ?? '—',
                    'amount_cents'   => (int) $m->amount_cents,
                    'status'         => $status,   // pending | submitted | approved | rejected | paid
                    'due_at'         => $m->due_at?->toDateString(),
                    'submitted_at'   => $m->submitted_at?->toDateString(),
                    'approved_at'    => $m->approved_at?->toDateString(),
                    'paid_at'        => $m->paid_at?->toDateString(),
                ];
            });

        return Inertia::render('business-partner/Milestones', [
            'milestones' => $milestones,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contract_id' => 'required|exists:bp_contracts,id',
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'amount_cents'=> 'required|integer|min:0',
            'due_at'      => 'nullable|date|after:today',
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
        // Guard: must be on a contract owned by the current BP
        abort_unless($milestone->contract?->bp_id === $request->user()->id, 403);

        $status = $milestone->status instanceof \BackedEnum ? $milestone->status->value : (string) $milestone->status;
        if ($status !== 'pending') {
            return back()->withErrors(['milestone' => 'Only pending milestones can be submitted.']);
        }

        $note = $request->validate(['notes' => 'nullable|string|max:2000'])['notes'] ?? null;
        // Note: submission notes are captured in activity/messaging, not on the milestone row itself.
        $milestone->update(['status' => 'submitted', 'submitted_at' => now()]);
        return back()->with('success', 'Milestone submitted.');
    }
}
