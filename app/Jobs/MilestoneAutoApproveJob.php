<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MilestoneStatus;
use App\Events\Business\MilestoneAutoApproved;
use App\Events\Business\MilestoneAutoApproveFailed;
use App\Models\BpMilestone;
use App\Services\ContractService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * MilestoneAutoApproveJob — Rev 2 direct-charge equivalent of MilestoneAutoReleaseJob.
 *
 * Runs hourly. Finds submitted Rev 2 milestones (payment_structure IS NOT NULL)
 * whose auto_approve_at deadline has passed. Calls ContractService::approveMilestone()
 * which fires the direct charge, updates milestone → paid, fires events.
 *
 * Only processes Rev 2 contracts — legacy escrow contracts handled by MilestoneAutoReleaseJob.
 */
class MilestoneAutoApproveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 120;

    public function handle(ContractService $contracts): void
    {
        $milestones = BpMilestone::query()
            ->where('status', MilestoneStatus::Submitted->value)
            ->whereNotNull('auto_approve_at')
            ->where('auto_approve_at', '<=', now())
            ->whereHas('contract', fn ($q) => $q->whereNotNull('payment_structure'))
            ->with(['contract.practitioner', 'contract.bp'])
            ->limit(100)
            ->get();

        Log::info('[MilestoneAutoApproveJob] Processing ' . $milestones->count() . ' milestones.');

        foreach ($milestones as $milestone) {
            try {
                $contracts->approveMilestone($milestone, null /* system */);
                event(new MilestoneAutoApproved($milestone->fresh()));
            } catch (\Throwable $e) {
                Log::error('[MilestoneAutoApproveJob] Failed for milestone ' . $milestone->id, [
                    'error' => $e->getMessage(),
                ]);
                event(new MilestoneAutoApproveFailed($milestone->fresh(), $e->getMessage()));
            }
        }
    }
}
