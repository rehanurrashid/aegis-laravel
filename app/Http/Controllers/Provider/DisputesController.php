<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

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
    public function __construct(private DisputeService $disputes) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $rows = $this->disputes->listForUser($user->id)
            ->map(fn (Dispute $d) => [
                'id'                    => $d->id,
                'subject_type'          => $d->subject_type,
                'subject_id'            => $d->subject_id,
                'reason'                => $d->reason?->value,
                'reason_label'          => $d->reason?->label(),
                'status'                => $d->status?->value,
                'status_label'          => $d->status?->label(),
                'status_color'          => $d->status?->color(),
                'role'                  => $d->disputer_id === $user->id ? 'disputer' : 'respondent',
                'amount_disputed_cents' => (int) $d->amount_disputed_cents,
                'opened_at'             => $d->opened_at?->format('M j, Y'),
                'resolved_at'           => $d->resolved_at?->format('M j, Y'),
            ]);

        return Inertia::render('provider/Disputes', [
            'disputes' => $rows,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_type'          => 'required|in:cs_invoice,bp_invoice,bp_payout,session',
            'subject_id'            => 'required|string',
            'reason'                => 'required|in:non_delivery,quality_issue,unauthorized_charge,duplicate_charge,wrong_amount,other',
            'amount_disputed_cents' => 'required|integer|min:1',
            'description'           => 'required|string|min:20|max:5000',
        ]);

        try {
            $this->disputes->open(
                disputer:            $request->user(),
                subjectType:         $data['subject_type'],
                subjectId:           $data['subject_id'],
                reason:              DisputeReason::from($data['reason']),
                amountDisputedCents: (int) $data['amount_disputed_cents'],
                description:         $data['description'],
            );
            return back()->with('success', 'Dispute opened. The other party has ' . (int) env('DISPUTE_RESPONDENT_REPLY_DAYS', 5) . ' business days to respond.');
        } catch (\Throwable $e) {
            return back()->withErrors(['dispute' => $e->getMessage()]);
        }
    }

    public function show(Request $request, Dispute $dispute): Response
    {
        $user = $request->user();
        abort_unless(in_array($user->id, [$dispute->disputer_id, $dispute->respondent_id], true), 403);

        $dispute->load(['messages.author:id,display_name']);

        return Inertia::render('provider/DisputeDetail', [
            'dispute'  => [
                'id'                    => $dispute->id,
                'subject_type'          => $dispute->subject_type,
                'subject_id'            => $dispute->subject_id,
                'reason'                => $dispute->reason?->value,
                'reason_label'          => $dispute->reason?->label(),
                'status'                => $dispute->status?->value,
                'status_label'          => $dispute->status?->label(),
                'amount_disputed_cents' => (int) $dispute->amount_disputed_cents,
                'description'           => $dispute->description,
                'role'                  => $dispute->disputer_id === $user->id ? 'disputer' : 'respondent',
                'resolution'            => $dispute->resolution?->value,
                'resolution_label'      => $dispute->resolution?->label(),
                'resolution_cents'      => $dispute->resolution_cents,
                'resolution_summary'    => $dispute->resolution_summary,
                'opened_at'             => $dispute->opened_at?->format('M j, Y g:i A'),
                'resolved_at'           => $dispute->resolved_at?->format('M j, Y g:i A'),
            ],
            'messages' => $dispute->messages->map(fn ($m) => [
                'id'          => $m->id,
                'author_name' => $m->author?->display_name ?? '—',
                'author_role' => $m->author_role,
                'body'        => $m->body,
                'attachment_url' => $m->attachment_url,
                'created_at'  => $m->created_at?->format('M j, Y g:i A'),
                'is_mine'     => $m->author_id === $user->id,
            ]),
        ]);
    }

    public function reply(Request $request, Dispute $dispute): RedirectResponse
    {
        $data = $request->validate([
            'body'            => 'required|string|min:1|max:5000',
            'attachment_url'  => 'nullable|url|max:500',
        ]);
        try {
            $this->disputes->reply($dispute, $request->user(), $data['body'], $data['attachment_url'] ?? null);
            return back()->with('success', 'Reply posted.');
        } catch (\Throwable $e) {
            return back()->withErrors(['dispute' => $e->getMessage()]);
        }
    }
}
