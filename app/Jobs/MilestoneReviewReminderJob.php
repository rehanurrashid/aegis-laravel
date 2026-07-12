<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\SendEmailJob;
use App\Models\BpMilestone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Runs daily at 08:00 UTC.
 *
 * Finds milestones that:
 *   1. Have status = 'submitted'
 *   2. Have auto_release_at within the next MILESTONE_REVIEW_REMINDER_HOURS (default 48h)
 *   3. Have not already had a reminder sent (reminder_sent_at IS NULL)
 *
 * Sends a reminder email to the provider (practitioner) via template
 * emails.business.58-milestone-review-reminder.
 *
 * Sets reminder_sent_at = now() so the cron doesn't fire again for the same milestone.
 * (The "don't send twice" guard uses a 24h cooldown — if somehow cron fires twice,
 *  milestones with reminder_sent_at < 24h ago are skipped.)
 *
 * Config: aegis.milestone_review_reminder_hours (env: MILESTONE_REVIEW_REMINDER_HOURS, default 48)
 */
class MilestoneReviewReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('email');
    }

    public function handle(): void
    {
        $reminderHours = (int) config('aegis.milestone_review_reminder_hours', 48);
        $cooldownHours = 24; // don't resend if already sent within 24h

        $cutoffFuture = now()->addHours($reminderHours);
        $cooldownCutoff = now()->subHours($cooldownHours);

        // Submitted milestones auto-releasing within next $reminderHours hours
        // AND either never reminded (NULL) or reminded more than $cooldownHours ago
        $candidates = BpMilestone::where('status', 'submitted')
            ->whereNotNull('auto_release_at')
            ->where('auto_release_at', '<=', $cutoffFuture)
            ->where('auto_release_at', '>', now()) // not yet released
            ->where(function ($q) use ($cooldownCutoff) {
                $q->whereNull('reminder_sent_at')
                  ->orWhere('reminder_sent_at', '<', $cooldownCutoff);
            })
            ->with(['contract:id,practitioner_id,title,bp_id'])
            ->limit(100)
            ->get();

        Log::info('[MILESTONE_REVIEW_REMINDER] scan', [
            'candidates'      => $candidates->count(),
            'reminder_hours'  => $reminderHours,
            'run_at'          => now()->toISOString(),
        ]);

        $sent   = 0;
        $failed = 0;

        foreach ($candidates as $milestone) {
            try {
                $contract = $milestone->contract;
                if (!$contract || !$contract->practitioner_id) {
                    continue;
                }

                // Dispatch reminder email to provider
                SendEmailJob::dispatch(
                    'emails.business.58-milestone-review-reminder',
                    [
                        'milestone_id'   => $milestone->id,
                        'contract_id'    => $contract->id,
                        'bp_id'          => $contract->bp_id,
                        'auto_release_at'=> $milestone->auto_release_at->toISOString(),
                        'hours_remaining'=> max(0, (int) round(
                            ($milestone->auto_release_at->diffInMinutes(now(), false) * -1) / 60
                        )),
                    ],
                    $contract->practitioner_id,
                )->onQueue('email');

                // Mark as reminded (dedup guard)
                $milestone->update(['reminder_sent_at' => now()]);

                $sent++;
                Log::info('[MILESTONE_REVIEW_REMINDER] sent', [
                    'milestone_id'   => $milestone->id,
                    'practitioner_id'=> $contract->practitioner_id,
                    'auto_release_at'=> $milestone->auto_release_at->toISOString(),
                ]);
            } catch (\Throwable $e) {
                $failed++;
                Log::error('[MILESTONE_REVIEW_REMINDER] failure', [
                    'milestone_id' => $milestone->id,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        Log::info('[MILESTONE_REVIEW_REMINDER] complete', [
            'sent'   => $sent,
            'failed' => $failed,
        ]);
    }
}
