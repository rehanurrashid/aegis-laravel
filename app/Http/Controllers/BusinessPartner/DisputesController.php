<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Enums\DisputeReason;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisputesController extends Controller
{
    public function __construct(
        private DisputeService $disputes
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $rows = $this->disputes->listForUser($user->id)
            ->map(fn (Dispute $d) => [
                'id' => $d->id, 'subject_type' => $d->subject_type, 'subject_id' => $d->subject_id,
                'reason' => $d->reason?->value, 'reason_label' => $d->reason?->label(),
                'status' => $d->status?->value, 'status_label' => $d->status?->label(),
                'role' => $d->disputer_id === $user->id ? 'disputer' : 'respondent',
                'amount_disputed_cents' => (int) $d->amount_disputed_cents,
                'opened_at' => $d->opened_at?->format('M j, Y'),
                'resolved_at' => $d->resolved_at?->format('M j, Y'),
            ]);

        return Inertia::render('business-partner/Disputes', ['disputes' => $rows]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_type' => 'required|in:cs_invoice,bp_invoice,bp_payout,bp_milestone,session',
            'subject_id'   => 'required|string',
            'reason'       => 'required|in:non_delivery,quality_issue,unauthorized_charge,duplicate_charge,wrong_amount,other',
            'amount_disputed_cents' => 'required|integer|min:1',
            'description'  => 'required|string|min:20|max:5000',
        ]);
        try {
            $this->disputes->withEscrow(app(\App\Services\EscrowService::class))->open(
                disputer:            $request->user(),
                subjectType:         $data['subject_type'],
                subjectId:           $data['subject_id'],
                reason:              DisputeReason::from($data['reason']),
                amountDisputedCents: (int) $data['amount_disputed_cents'],
                description:         $data['description'],
            );
            return back()->with('success', 'Dispute opened.');
        } catch (\Throwable $e) {
            return back()->withErrors(['dispute' => $e->getMessage()]);
        }
    }

    public function show(Request $request, Dispute $dispute): Response
    {
        $user = $request->user();
        abort_unless(in_array($user->id, [$dispute->disputer_id, $dispute->respondent_id], true), 403);
        $dispute->load(['messages.author:id,display_name']);
        return Inertia::render('business-partner/DisputeDetail', [
            'dispute'  => $dispute->only(['id','subject_type','subject_id','description','amount_disputed_cents','opened_at','resolved_at']) + [
                'reason_label' => $dispute->reason?->label(),
                'status_label' => $dispute->status?->label(),
                'role'         => $dispute->disputer_id === $user->id ? 'disputer' : 'respondent',
            ],
            'messages' => $dispute->messages->map(fn ($m) => [
                'id' => $m->id, 'author_name' => $m->author?->display_name, 'author_role' => $m->author_role,
                'body' => $m->body, 'created_at' => $m->created_at?->format('M j, Y g:i A'),
                'is_mine' => $m->author_id === $user->id,
            ]),
        ]);
    }

    public function reply(Request $request, Dispute $dispute): RedirectResponse
    {
        $data = $request->validate(['body' => 'required|string|max:5000']);
        try {
            $this->disputes->reply($dispute, $request->user(), $data['body']);
            return back()->with('success', 'Reply posted.');
        } catch (\Throwable $e) {
            return back()->withErrors(['dispute' => $e->getMessage()]);
        }
    }
}
