<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Services\EscrowService;
use App\Enums\ContractStatus;
use App\Enums\MilestoneStatus;
use App\Models\BpMilestoneSubmission;
use App\Http\Requests\Business\CreateJobRequest;
use App\Http\Requests\Business\JobStatusRequest;
use App\Http\Requests\Business\ProposalNoteRequest;
use App\Http\Requests\Business\ProposalStageRequest;
use App\Models\BpMilestone;
use App\Models\BpInvoice;
use App\Http\Requests\Business\UpdateJobRequest;
use App\Models\BpContract;
use App\Models\BpEngagementRequest;
use App\Models\BpJob;
use App\Models\BpProposal;
use App\Services\BpJobService;
use App\Services\ContractService;
use App\Services\MessagingService;
use App\Services\PayoutService;
use App\Services\ProposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobPostingsController extends Controller
{
    public function __construct(
        private BpJobService $jobs,
        private ProposalService $proposals,
        private ContractService $contracts,
        private MessagingService $messaging,
        private \App\Services\InvoiceService $invoices,
        private PayoutService $payouts,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $jobs = BpJob::where('practitioner_id', $user->id)->orderByDesc('posted_at')->get();

        // Load proposal IDs that have a contract — used as a hired fallback
        // in case proposal status/pipeline_stage are stale (idempotency bug backfill).
        // Filter out null proposal_ids (contract pre-dates the unique constraint migration).
        $contractedProposalIds = BpContract::whereIn('job_id', $jobs->pluck('id'))
            ->whereNotNull('proposal_id')
            ->pluck('proposal_id')
            ->mapWithKeys(fn ($id) => [$id => true]); // O(1) set lookup

        $proposalsByJob = BpProposal::with('bp:id,display_name,bp_type,avatar_initials,avatar_path,location,verified,slug')
            ->whereIn('job_id', $jobs->pluck('id'))
            ->orderByDesc('submitted_at')
            ->get()
            ->each(function ($p) use ($contractedProposalIds) {
                // Ensure any proposal that has a contract is always marked hired —
                // guards against stale status/pipeline_stage in the database.
                if ($contractedProposalIds->get($p->id)) {
                    $p->status         = 'accepted';
                    $p->pipeline_stage = 'hired';
                }
            })
            ->groupBy('job_id');

        $contracts = BpContract::with('bp:id,display_name,bp_type,avatar_initials,avatar_path,slug')
            ->where('practitioner_id', $user->id)
            ->whereIn('status', ['pending_signature', 'pending_funding', 'active', 'completed'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (\App\Models\BpContract $c) {
                $statusVal = $c->status instanceof \BackedEnum ? $c->status->value : (string) $c->status;
                return array_merge($c->toArray(), [
                    'status'                 => $statusVal,
                    'total_value_cents'      => (int) $c->total_value_cents,
                    'escrow_funded_cents'    => (int) ($c->escrow_funded_cents ?? 0),
                    'escrow_released_cents'  => (int) ($c->escrow_released_cents ?? 0),
                    'escrow_refunded_cents'  => (int) ($c->escrow_refunded_cents ?? 0),
                    'payment_type'           => $c->payment_type ?? 'one_time',
                    'funding_mode'           => $c->funding_mode ?? 'per_milestone',
                    'provider_has_signed'    => (bool) $c->practitioner_signed_at,
                    'bp_has_signed'          => (bool) $c->bp_signed_at,
                    'fully_executed'         => (bool) $c->fully_executed_at,
                    'terms'                  => $c->terms,
                    'bp'                     => $c->bp ? [
                        'id'              => $c->bp->id,
                        'display_name'    => $c->bp->display_name,
                        'bp_type'         => $c->bp->bp_type instanceof \BackedEnum ? $c->bp->bp_type->value : (string) ($c->bp->bp_type ?? ''),
                        'avatar_initials' => $c->bp->avatar_initials,
                        'avatar_path'     => $c->bp->avatar_path,
                        'slug'            => $c->bp->slug,
                    ] : null,
                ]);
            });

        $totalSpent = BpContract::where('practitioner_id', $user->id)->sum('total_value_cents');

        $bpIds = $proposalsByJob->flatten()->pluck('bp_id')->unique()->values();
        $jobsDoneByBp = BpContract::whereIn('bp_id', $bpIds)
            ->where('status', 'completed')
            ->selectRaw('bp_id, count(*) as c')
            ->groupBy('bp_id')
            ->pluck('c', 'bp_id');
        $bpUsers = \App\Models\User::whereIn('id', $bpIds)->get(['id', 'bp_hourly_rate_cents'])->keyBy('id');
        $bpStats = $bpIds->mapWithKeys(fn ($id) => [$id => [
            'jobs_done'         => (int) ($jobsDoneByBp[$id] ?? 0),
            'asking_rate_cents' => $bpUsers[$id]->bp_hourly_rate_cents ?? null,
        ]]);

        // Direct engagement requests from public profile (hire/quote/consultation)
        $engagementRequests = BpEngagementRequest::with('bp:id,display_name,bp_type,avatar_initials,slug')
            ->where('practitioner_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'              => $r->id,
                'type'            => $r->type,
                'engagement_type' => $r->engagement_type,
                'service'         => $r->service,
                'meeting_type'    => $r->meeting_type,
                'start_date'      => $r->start_date?->format('M j, Y'),
                'budget'          => $r->budget,
                'payment_terms'   => $r->payment_terms,
                'duration'        => $r->duration,
                'notes'           => $r->notes,
                'agenda'          => $r->agenda,
                'status'          => $r->status,
                'urgent'          => $r->urgent,
                'created_at'      => $r->created_at->format('M j, Y g:i A'),
                'bp'              => $r->bp ? [
                    'id'             => $r->bp->id,
                    'display_name'   => $r->bp->display_name,
                    'bp_type'        => $r->bp->bp_type instanceof \BackedEnum ? $r->bp->bp_type->value : (string) ($r->bp->bp_type ?? ''),
                    'avatar_initials'=> $r->bp->avatar_initials,
                    'slug'           => $r->bp->slug,
                ] : null,
            ])->values()->toArray();

        return Inertia::render('provider/SupportServices', [
            'jobs'             => $jobs,
            'proposalsByJob'   => $proposalsByJob,
            'activeContracts'  => $contracts,
            'engagementRequests' => $engagementRequests,
            'milestonesByContract' => \App\Models\BpMilestone::with(['submissions' => fn ($q) => $q->latest()->limit(1)])
                ->whereIn('contract_id', collect($contracts)->pluck('id'))
                ->orderBy('sort_order')
                ->get()
                ->map(function (\App\Models\BpMilestone $m) {
                    $statusVal = $m->status instanceof \BackedEnum ? $m->status->value : (string) $m->status;
                    $sub = $m->submissions->first();
                    return [
                        'id'               => $m->id,
                        'contract_id'      => $m->contract_id,
                        'title'            => $m->title,
                        'description'      => $m->description,
                        'amount_cents'     => (int) $m->amount_cents,
                        'status'           => $statusVal,
                        'sort_order'       => (int) $m->sort_order,
                        'due_at'           => $m->due_at?->toDateString(),
                        'submitted_at'     => $m->submitted_at?->toDateString(),
                        'approved_at'      => $m->approved_at?->toDateString(),
                        'paid_at'          => $m->paid_at?->toDateString(),
                        'funded_cents'     => (int) ($m->funded_cents ?? 0),
                        'released_cents'   => (int) ($m->released_cents ?? 0),
                        'refunded_cents'   => (int) ($m->refunded_cents ?? 0),
                        'auto_release_at'  => $m->auto_release_at?->toISOString(),
                        'revision_count'   => (int) ($m->revision_count ?? 0),
                        'revision_notes'   => $m->revision_notes,
                        'rejection_reason' => $m->rejection_reason,
                        'latest_submission'=> $sub ? [
                            'id'               => $sub->id,
                            'submission_notes' => $sub->submission_notes,
                            'hours_logged'     => $sub->hours_logged,
                            'submitted_by'     => $sub->submitted_by,
                            'created_at'       => $sub->created_at?->toDateString(),
                        ] : null,
                    ];
                })
                ->groupBy('contract_id')
                ->map(fn ($group) => $group->values()),
            'bpStats'          => $bpStats,
            'stats' => [
                'open'              => $jobs->where('status', 'open')->count(),
                'draft'             => $jobs->where('status', 'draft')->count(),
                'paused'            => $jobs->where('status', 'paused')->count(),
                'filled'            => $jobs->where('status', 'filled')->count(),
                'closed'            => $jobs->whereIn('status', ['closed', 'cancelled'])->count(),
                'total_jobs'        => $jobs->count(),
                'total_proposals'   => $proposalsByJob->flatten()->count(),
                'pending_proposals' => $proposalsByJob->flatten()->where('status', 'pending')->count(),
                'hired'             => $contracts->whereIn('status', ['pending_signature', 'pending_funding', 'active'])->count(),
                'total_spent_cents' => (int) $totalSpent,
                'engagement_requests' => count($engagementRequests),
            ],
        ]);
    }

    public function store(CreateJobRequest $request): RedirectResponse
    {
        $job = $this->jobs->create($request->user(), $request->validated());

        return back()->with('success', $job->status === 'draft' ? 'Draft saved.' : 'Support request posted.');
    }

    public function update(UpdateJobRequest $request, BpJob $job): RedirectResponse
    {
        $this->authorize('close', $job);
        $this->jobs->update($job, $request->validated());
        return back()->with('success', 'Posting updated.');
    }

    public function setStatus(JobStatusRequest $request, BpJob $job): RedirectResponse
    {
        $this->authorize('close', $job);
        $this->jobs->setStatus($job, $request->validated()['status']);
        return back()->with('success', 'Posting status updated.');
    }

    public function destroy(Request $request, BpJob $job): RedirectResponse
    {
        $this->authorize('close', $job);
        $this->jobs->close($job);
        return back()->with('success', 'Job closed.');
    }

    public function acceptProposal(Request $request, BpJob $job, BpProposal $proposal): RedirectResponse
    {
        abort_if($proposal->job_id !== $job->id, 404);
        $this->authorize('close', $job); // owner == accept authority
        $validated = $request->validate(['final_rate_cents' => 'nullable|integer|min:0']);
        $this->proposals->accept($proposal, $validated['final_rate_cents'] ?? null);
        return back()->with('success', 'Proposal accepted. Contract created.');
    }

    public function declineProposal(Request $request, BpJob $job, BpProposal $proposal): RedirectResponse
    {
        abort_if($proposal->job_id !== $job->id, 404);
        $this->authorize('close', $job);
        $this->proposals->decline($proposal, $request->input('reason'));
        return back()->with('success', 'Proposal declined.');
    }

    public function setProposalStage(ProposalStageRequest $request, BpJob $job, BpProposal $proposal): RedirectResponse
    {
        abort_if($proposal->job_id !== $job->id, 404);
        $this->authorize('close', $job);
        $data = $request->validated();
        $this->proposals->setStage($proposal, $data['pipeline_stage'], $data['note'] ?? null, $data['interview_type'] ?? null, $data['interview_at'] ?? null);

        if ($data['pipeline_stage'] === 'interview' && !empty($data['notify_applicant'])) {
            $user = $request->user();
            $when = $data['interview_at'] ?? null;
            $whenLabel = $when ? \Illuminate\Support\Carbon::parse($when)->format('M j, Y \a\t g:ia') : 'a time to be confirmed';
            $typeLabel = ['video' => 'Video Call', 'phone' => 'Phone Call', 'in_person' => 'In-Person'][$data['interview_type'] ?? ''] ?? 'Interview';
            $body = "We'd like to schedule a {$typeLabel} for \"{$job->title}\" on {$whenLabel}.";
            if (!empty($data['note'])) {
                $body .= "\n\n" . $data['note'];
            }
            $thread = $this->messaging->createThread([$user->id, $proposal->bp_id], 'Interview: ' . $job->title);
            $this->messaging->sendMessage($thread, $user, $body);
        }

        return back()->with('success', 'Applicant status updated.');
    }

    public function setProposalNotes(ProposalNoteRequest $request, BpJob $job, BpProposal $proposal): RedirectResponse
    {
        abort_if($proposal->job_id !== $job->id, 404);
        $this->authorize('close', $job);
        $this->proposals->setNotes($proposal, $request->validated()['internal_notes'] ?? null);
        return back()->with('success', 'Notes saved.');
    }

    public function cancelContract(Request $request, BpContract $contract): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        $this->contracts->cancel($contract, $request->user(), $request->input('reason'));
        return back()->with('success', 'Contract ended.');
    }

    public function endContract(Request $request, BpContract $contract): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        // Block if ANY milestones exist and are not all paid — regardless of payment_type.
        // Once milestones are added, the contract is milestone-driven.
        if ($contract->milestones()->exists()
            && $contract->milestones()->whereNotIn('status', ['paid'])->exists()) {
            return back()->withErrors(['contract' => 'All milestones must be paid before ending this contract.']);
        }
        $contract->update(['status' => 'completed', 'ended_at' => now(), 'completed_at' => now()]);
        return back()->with('success', 'Contract ended.');
    }

    public function releasePayment(Request $request, BpContract $contract): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        // Block if milestones exist — contract is milestone-driven, use payMilestone instead.
        if ($contract->milestones()->exists()) {
            return back()->withErrors(['contract' => 'This contract has milestones. Pay each milestone individually.']);
        }
        $paymentType = $contract->payment_type ?? 'one_time';
        if ($paymentType !== 'one_time') {
            abort(422, 'Only one-time payment contracts can use this action.');
        }
        $statusVal = $contract->status instanceof \BackedEnum ? $contract->status->value : (string) $contract->status;
        if ($statusVal !== 'active') {
            return back()->withErrors(['contract' => 'Contract is not active.']);
        }
        try {
            $payout = $this->payouts->endContractAndRelease($contract, $request->user());
            $amount = number_format($payout->amount_cents / 100, 2);
            return back()->with('success', "Payment of \${$amount} released to BP via Stripe. Contract ended.");
        } catch (\Throwable $e) {
            return back()->withErrors(['contract' => $e->getMessage()]);
        }
    }

    public function storeMilestone(Request $request, BpContract $contract): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:191'],
            'description'  => ['nullable', 'string'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'due_at'       => ['nullable', 'date'],
            'sort_order'   => ['nullable', 'integer'],
        ]);
        BpMilestone::create([
            'id'           => 'bm_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)),
            'contract_id'  => $contract->id,
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'amount_cents' => $data['amount_cents'],
            'due_at'       => $data['due_at'] ?? null,
            'sort_order'   => $data['sort_order'] ?? 0,
            'status'       => 'pending',
        ]);
        return back()->with('success', 'Milestone added.');
    }

    public function updateMilestone(Request $request, BpContract $contract, BpMilestone $milestone): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        abort_if($milestone->contract_id !== $contract->id, 404);
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:191'],
            'description'  => ['nullable', 'string'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'due_at'       => ['nullable', 'date'],
        ]);
        $milestone->update($data);
        return back()->with('success', 'Milestone updated.');
    }

    public function destroyMilestone(Request $request, BpContract $contract, BpMilestone $milestone): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        abort_if($milestone->contract_id !== $contract->id, 404);
        $statusVal = $milestone->status instanceof \BackedEnum ? $milestone->status->value : (string) $milestone->status;
        if ($statusVal === 'paid') {
            return back()->withErrors(['milestone' => 'Cannot delete a paid milestone.']);
        }
        $milestone->delete();
        return back()->with('success', 'Milestone removed.');
    }

    public function approveMilestone(Request $request, BpContract $contract, BpMilestone $milestone): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        abort_if($milestone->contract_id !== $contract->id, 404);
        try {
            $this->contracts->approveMilestone($milestone, $request->user());
            return back()->with('success', 'Milestone approved and payment released to Business Partner.');
        } catch (\Throwable $e) {
            return back()->withErrors(['milestone' => $e->getMessage()]);
        }
    }

    public function payMilestone(Request $request, BpContract $contract, BpMilestone $milestone): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        abort_if($milestone->contract_id !== $contract->id, 404);
        try {
            $payout = $this->payouts->payMilestone($milestone, $request->user());
            $amount = number_format($payout->amount_cents / 100, 2);
            return back()->with('success', "Milestone payment of \${$amount} released via Stripe.");
        } catch (\Throwable $e) {
            return back()->withErrors(['milestone' => $e->getMessage()]);
        }
    }
    /**
     * Wave 3: Fund full contract into Aegis escrow (full_upfront mode).
     */
    public function fundContract(Request $request, BpContract $contract): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        try {
            $this->escrow->fundContract($contract, $request->user());
            return back()->with('success', 'Contract funded. All milestones are now active.');
        } catch (\Throwable $e) {
            return back()->withErrors(['contract' => $e->getMessage()]);
        }
    }

    /**
     * Wave 3: Fund a single milestone into Aegis escrow (per_milestone mode).
     */
    public function fundMilestone(Request $request, BpContract $contract, BpMilestone $milestone): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        abort_if($milestone->contract_id !== $contract->id, 404);
        try {
            $this->escrow->fundMilestone($milestone, $request->user());
            return back()->with('success', 'Milestone funded. Business Partner can now begin work.');
        } catch (\Throwable $e) {
            return back()->withErrors(['milestone' => $e->getMessage()]);
        }
    }

    /**
     * Wave 3: Provider reviews a submitted milestone.
     * Actions: approved | revision_requested | rejected
     */
    public function reviewMilestone(Request $request, BpContract $contract, BpMilestone $milestone): RedirectResponse
    {
        $this->authorize('cancel', $contract);
        abort_if($milestone->contract_id !== $contract->id, 404);

        $data = $request->validate([
            'action' => ['required', 'in:approved,revision_requested,rejected'],
            'notes'  => ['nullable', 'string', 'max:1000',
                         'required_if:action,revision_requested'],
        ]);

        try {
            match ($data['action']) {
                'approved' => $this->contracts->approveMilestone($milestone, $request->user()),
                'revision_requested' => $this->contracts->requestRevision(
                    $milestone, $request->user(), $data['notes'] ?? ''
                ),
                'rejected' => $this->openMilestoneDispute($contract, $milestone, $request->user(), $data['notes']),
            };

            $msgs = [
                'approved'           => 'Milestone approved and payment released.',
                'revision_requested' => 'Revision requested. Business Partner has been notified.',
                'rejected'           => 'Dispute opened. Aegis will mediate the escrow.',
            ];
            return back()->with('success', $msgs[$data['action']]);
        } catch (\Throwable $e) {
            return back()->withErrors(['milestone' => $e->getMessage()]);
        }
    }

    /**
     * Wave 3: Provider signs the contract (dual-signature).
     */
    public function signContract(Request $request, BpContract $contract): RedirectResponse
    {
        abort_unless(
            $contract->practitioner_id === $request->user()->id,
            403, 'Not authorised to sign this contract.'
        );
        $data = $request->validate(['name' => 'required|string|max:120']);
        try {
            $this->contracts->sign($contract, $request->user(), $data);
            return back()->with('success', 'Contract signed.');
        } catch (\Throwable $e) {
            return back()->withErrors(['contract' => $e->getMessage()]);
        }
    }

    private function openMilestoneDispute(BpContract $contract, BpMilestone $milestone, \App\Models\User $provider, ?string $reason): void
    {
        // Delegate to DisputeService — Wave 5 wires full dispute flow.
        // For now, flag milestone as disputed to freeze escrow.
        $statusVal = $milestone->status instanceof \BackedEnum ? $milestone->status->value : (string) $milestone->status;
        if ($statusVal === MilestoneStatus::Submitted->value) {
            $milestone->update([
                'status'           => MilestoneStatus::Disputed->value,
                'rejection_reason' => $reason,
                'auto_release_at'  => null,
            ]);
        }
    }

    /**
     * Gap 5: Provider pays a BP-issued invoice directly.
     * Uses chargeProviderToBp() (destination charge) then calls InvoiceService::recordPayment().
     *
     * Decision: soft W-9 gate — warn in activity log but don't hard-block,
     * since BP may not yet have uploaded their W-9 during onboarding.
     * Hard block only enforced by admin if compliance requires it.
     */
    public function payBPInvoice(Request $request, BpInvoice $invoice): RedirectResponse
    {
        $provider = $request->user();

        if ($invoice->practitioner_id !== $provider->id) {
            abort(403, 'Not authorised to pay this invoice.');
        }

        if ($invoice->status === 'paid') {
            return back()->withErrors(['invoice' => 'This invoice has already been paid.']);
        }

        $bp = \App\Models\User::find($invoice->bp_id);
        if (!$bp) {
            return back()->withErrors(['invoice' => 'Business Partner not found.']);
        }

        // Gap 9 soft W-9 gate: warn if W-9 not verified but don't block
        $w9 = $bp->bpTaxDocuments()->where('doc_type', 'w9')->latest()->first();
        if (!$w9 || $w9->status !== \App\Enums\TaxDocStatus::Verified) {
            app(\App\Services\ActivityService::class)->log(
                $provider->id, 'provider', 'job_postings', \App\Enums\ActivitySeverity::Warning,
                'invoice_pay_no_w9', 'Payment issued without verified W-9',
                'BP ' . ($bp->display_name ?? $bp->id) . ' does not have a verified W-9 on file. Payment proceeded — admin notified.',
                'bp_invoice', $invoice->id, null, 'log', $provider->id,
            );
        }

        try {
            $result = $this->payouts->chargeProviderToBp(
                provider:    $provider,
                bp:          $bp,
                amountCents: $invoice->total_cents,
                meta:        ['bp_invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number],
                description: 'BP invoice ' . $invoice->invoice_number . ' — Aegis',
            );

            $this->invoices->recordPayment(
                invoice:       $invoice,
                amountCents:   $invoice->total_cents,
                stripeChargeId: $result['stripe_payment_intent_id'],
            );

            return back()->with('success', 'Invoice ' . $invoice->invoice_number . ' paid successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }
    }

}
