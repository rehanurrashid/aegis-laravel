<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BpMilestone;
use App\Services\EscrowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Runs hourly.
 *
 * Scans for submitted milestones where auto_release_at <= now and the provider
 * has not yet reviewed. Releases escrow to the BP via EscrowService::releaseMilestone()
 * with a null $approver (system actor). Fires MilestoneAutoReleased event.
 *
 * This protects the BP from provider ghosting — payment is never held indefinitely.
 *
 * Config: aegis.milestone_auto_release_days (env: MILESTONE_AUTO_RELEASE_DAYS, default 7)
 * Scheduled: hourly in routes/console.php
 *
 * Safety guards:
 *  - scopeAutoReleaseDue() on BpMilestone filters to status=submitted + auto_release_at <= now
 *  - EscrowService::releaseMilestone() checks status is 'submitted' or 'approved' before acting
 *  - Each milestone processed in try/catch to prevent one failure blocking the rest
 *  - Processes max 50 milestones per run (pagination-safe for future scale)
 */
class MilestoneAutoReleaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(EscrowService $escrow): void
    {
        // Rev 2 guard: only process legacy escrow contracts (payment_structure IS NULL)
        // Rev 2 contracts are handled by MilestoneAutoApproveJob
        $candidates = BpMilestone::scopeAutoReleaseDue(BpMilestone::query())
            ->whereHas('contract', fn ($q) => $q->whereNull('payment_structure'))
            ->with(['contract:id,bp_id,practitioner_id,title'])
            ->limit(50)
            ->get();

        Log::info('[MILESTONE_AUTO_RELEASE] scan', [
            'candidates' => $candidates->count(),
            'run_at'     => now()->toISOString(),
        ]);

        $released = 0;
        $failed   = 0;

        foreach ($candidates as $milestone) {
            try {
                // releaseMilestone with null approver = system auto-release
                $escrow->releaseMilestone($milestone, null);
                $released++;
                Log::info('[MILESTONE_AUTO_RELEASE] released', [
                    'milestone_id'  => $milestone->id,
                    'contract_id'   => $milestone->contract_id,
                    'amount_cents'  => $milestone->funded_cents ?? $milestone->amount_cents,
                ]);
            } catch (\Throwable $e) {
                $failed++;
                Log::error('[MILESTONE_AUTO_RELEASE] failure', [
                    'milestone_id' => $milestone->id,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        Log::info('[MILESTONE_AUTO_RELEASE] complete', [
            'released' => $released,
            'failed'   => $failed,
        ]);
    }
}
