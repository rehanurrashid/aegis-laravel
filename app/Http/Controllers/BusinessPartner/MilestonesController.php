<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Models\BpMilestone;
use App\Models\BpMilestoneSubmission;
use App\Services\ActivityService;
use App\Services\ContractService;
use App\Enums\ActivitySeverity;
use App\Enums\MilestoneStatus;
use App\Events\Business\MilestoneSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MilestonesController extends Controller
{
    public function __construct(
        private ActivityService $activity,
        private ContractService $contracts,
    ) {}

    public function index(Request $request): Response
    {
        $milestones = BpMilestone::with([
            'contract:id,title,bp_id,practitioner_id,payment_type',
            'submissions' => fn ($q) => $q->latest()->limit(1),
        ])
            ->whereHas('contract', fn ($q) => $q->where('bp_id', $request->user()->id))
            ->orderByDesc('created_at')
            ->get()
            ->map(function (BpMilestone $m) {
                $status = $m->status instanceof \BackedEnum ? $m->status->value : (string) $m->status;
                $sub    = $m->submissions->first();
                return [
                    'id'               => $m->id,
                    'title'            => $m->title,
                    'description'      => $m->description,
                    'contract_id'      => $m->contract_id,
                    'contract_title'   => $m->contract?->title ?? '—',
                    'practitioner_id'  => $m->contract?->practitioner_id,
                    'amount_cents'     => (int) $m->amount_cents,
                    'status'           => $status,
                    'revision_count'   => (int) ($m->revision_count ?? 0),
                    'rejection_reason' => $m->rejection_reason,
                    'revision_notes'   => $m->revision_notes,
                    'due_at'           => $m->due_at?->toDateString(),
                    'submitted_at'     => $m->submitted_at?->toDateString(),
                    'approved_at'      => $m->approved_at?->toDateString(),
                    'paid_at'          => $m->paid_at?->toDateString(),
                    'funded_cents'     => (int) ($m->funded_cents ?? 0),
                    'released_cents'   => (int) ($m->released_cents ?? 0),
                    'auto_release_at'  => $m->auto_release_at?->toISOString(),
                    // Latest submission for resubmit context
                    'latest_submission' => $sub ? [
                        'id'               => $sub->id,
                        'submission_notes' => $sub->submission_notes,
                        'hours_logged'     => $sub->hours_logged,
                        'review_notes'     => $sub->review_notes,
                        'created_at'       => $sub->created_at?->toDateString(),
                    ] : null,
                ];
            });

        return Inertia::render('business-partner/Milestones', [
            'milestones' => $milestones,
        ]);
    }

    /**
     * BP submits milestone work for provider review.
     *
     * Writes a BpMilestoneSubmission audit row.
     * Fires MilestoneSubmitted event → email + activity notification to provider.
     *
     * Allowed from: pending, funded, in_progress, revision_requested
     */
    public function submit(Request $request, BpMilestone $milestone): RedirectResponse
    {
        // Authorization: must belong to a contract owned by this BP
        abort_unless($milestone->contract?->bp_id === $request->user()->id, 403);

        $status = $milestone->status instanceof \BackedEnum
            ? $milestone->status->value
            : (string) $milestone->status;

        $submittable = [
            MilestoneStatus::Pending->value,
            MilestoneStatus::Funded->value,
            MilestoneStatus::InProgress->value,
            MilestoneStatus::RevisionRequested->value,
        ];

        if (!in_array($status, $submittable, true)) {
            return back()->withErrors(['milestone' => 'This milestone cannot be submitted in its current state.']);
        }

        $validated = $request->validate([
            'notes'        => 'required|string|min:10|max:2000',
            'hours_logged' => 'nullable|numeric|min:0|max:9999',
        ]);

        $contract = $milestone->contract;
        $bp       = $request->user();

        // Write immutable submission audit row
        BpMilestoneSubmission::create([
            'id'               => 'bms_' . Str::lower(Str::random(12)),
            'milestone_id'     => $milestone->id,
            'contract_id'      => $milestone->contract_id,
            'submitted_by'     => $bp->id,
            'submission_notes' => $validated['notes'],
            'hours_logged'     => $validated['hours_logged'] ?? null,
            'attachments'      => null, // file uploads in Wave 4+ extension
        ]);

        $autoReleaseDays = (int) config('aegis.milestone_auto_release_days', 7);

        $milestone->update([
            'status'         => MilestoneStatus::Submitted->value,
            'submitted_at'   => now(),
            'auto_release_at'=> now()->addDays($autoReleaseDays),
        ]);

        // Actor log — BP's own history
        $this->activity->log(
            $bp->id,
            'business_partner',
            'job_postings',
            ActivitySeverity::Info,
            'milestone_submitted',
            "Milestone submitted: {$milestone->title}",
            "Awaiting provider review. Auto-releases in {$autoReleaseDays} days if not reviewed.",
            'bp_milestone',
            $milestone->id,
            $contract->practitioner_id,
            'log',
            $bp->id
        );

        // Notification → provider
        $this->activity->log(
            $contract->practitioner_id,
            'provider',
            'job_postings',
            ActivitySeverity::Info,
            'milestone_submitted',
            "{$bp->display_name} submitted milestone: {$milestone->title}",
            "Review and approve to release payment. Auto-releases in {$autoReleaseDays} days.",
            'bp_milestone',
            $milestone->id,
            $bp->id,
            'notification',
            $bp->id
        );

        event(new MilestoneSubmitted($milestone->fresh()));

        return back()->with('success', 'Milestone submitted for review.');
    }

    // NOTE: store() intentionally removed — only Providers create milestones.
}
