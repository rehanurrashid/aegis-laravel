<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Services\DisputeService;
use App\Services\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisputesController extends Controller
{
    public function __construct(
        private DisputeService $disputes,
        private PayoutService  $payouts,
    ) {}

    public function index(Request $request): Response
    {
        $filter = $request->string('status')->toString();
        $status = null;
        if ($filter && in_array($filter, array_map(fn ($c) => $c->value, DisputeStatus::cases()), true)) {
            $status = DisputeStatus::from($filter);
        }

        $rows = $this->disputes->listForAdmin($status)
            ->load(['disputer:id,display_name,email', 'respondent:id,display_name,email'])
            ->map(fn (Dispute $d) => [
                'id'                    => $d->id,
                'disputer'              => $d->disputer?->display_name,
                'respondent'            => $d->respondent?->display_name,
                'subject_type'          => $d->subject_type,
                'subject_id'            => $d->subject_id,
                'reason_label'          => $d->reason?->label(),
                'status'                => $d->status?->value,
                'status_label'          => $d->status?->label(),
                'status_color'          => $d->status?->color(),
                'amount_disputed_cents' => (int) $d->amount_disputed_cents,
                'opened_at'             => $d->opened_at?->format('M j, Y'),
                'age_days'              => $d->opened_at ? $d->opened_at->diffInDays(now()) : null,
            ]);

        // Queue counts by status for admin filter chips
        $counts = Dispute::selectRaw('status, COUNT(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        return Inertia::render('admin/Disputes', [
            'disputes' => $rows,
            'counts'   => $counts,
            'filter'   => $filter,
        ]);
    }

    public function show(Request $request, Dispute $dispute): Response
    {
        $dispute->load([
            'disputer:id,display_name,email,role',
            'respondent:id,display_name,email,role',
            'messages.author:id,display_name,role',
            'resolvedByUser:id,display_name',
        ]);

        return Inertia::render('admin/DisputeDetail', [
            'dispute' => [
                'id'                    => $dispute->id,
                'disputer'              => ['id' => $dispute->disputer?->id, 'name' => $dispute->disputer?->display_name, 'email' => $dispute->disputer?->email],
                'respondent'            => ['id' => $dispute->respondent?->id, 'name' => $dispute->respondent?->display_name, 'email' => $dispute->respondent?->email],
                'subject_type'          => $dispute->subject_type,
                'subject_id'            => $dispute->subject_id,
                'reason'                => $dispute->reason?->value,
                'reason_label'          => $dispute->reason?->label(),
                'status'                => $dispute->status?->value,
                'status_label'          => $dispute->status?->label(),
                'amount_disputed_cents' => (int) $dispute->amount_disputed_cents,
                'description'           => $dispute->description,
                'resolution'            => $dispute->resolution?->value,
                'resolution_label'      => $dispute->resolution?->label(),
                'resolution_cents'      => $dispute->resolution_cents,
                'resolution_summary'    => $dispute->resolution_summary,
                'resolved_by'           => $dispute->resolvedByUser?->display_name,
                'opened_at'             => $dispute->opened_at?->format('M j, Y g:i A'),
                'resolved_at'           => $dispute->resolved_at?->format('M j, Y g:i A'),
                'is_active'             => $dispute->status?->isActive(),
            ],
            'messages' => $dispute->messages->map(fn ($m) => [
                'id'          => $m->id,
                'author_name' => $m->author?->display_name,
                'author_role' => $m->author_role,
                'body'        => $m->body,
                'created_at'  => $m->created_at?->format('M j, Y g:i A'),
            ]),
        ]);
    }

    public function reply(Request $request, Dispute $dispute): RedirectResponse
    {
        $data = $request->validate(['body' => 'required|string|max:5000']);
        try {
            $this->disputes->reply($dispute, $request->user(), $data['body']);
            return back()->with('success', 'Message posted.');
        } catch (\Throwable $e) {
            return back()->withErrors(['dispute' => $e->getMessage()]);
        }
    }

    public function resolve(Request $request, Dispute $dispute): RedirectResponse
    {
        $data = $request->validate([
            'resolution'       => 'required|in:refund_full,refund_partial,pay_full,pay_partial,no_action,stripe_dispute_escalated',
            'resolution_cents' => 'nullable|integer|min:1',
            'summary'          => 'required|string|min:10|max:2000',
        ]);
        try {
            $this->disputes->resolve(
                dispute:         $dispute,
                admin:           $request->user(),
                resolution:      DisputeResolution::from($data['resolution']),
                resolutionCents: $data['resolution_cents'] ?? null,
                summary:         $data['summary'],
                payouts:         $this->payouts,
            );
            return back()->with('success', 'Dispute resolved.');
        } catch (\Throwable $e) {
            return back()->withErrors(['dispute' => $e->getMessage()]);
        }
    }
}
