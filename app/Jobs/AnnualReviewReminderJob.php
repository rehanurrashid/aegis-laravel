<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\Plan\AnnualReviewDue;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs daily at 09:00 UTC.
 * For each active plan with annual_review_date in {30d, 7d, 0d}, fires
 * AnnualReviewDue (which the email listener uses to pick template a/b/c).
 */
class AnnualReviewReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $today = now()->startOfDay();
        $windows = [30, 7, 0];

        foreach ($windows as $days) {
            $target = $today->copy()->addDays($days);
            $plans = ContinuityPlan::whereIn('status', ['active', 'annual_review_due'])
                ->whereDate('annual_review_date', $target)
                ->get();

            foreach ($plans as $plan) {
                event(new AnnualReviewDue($plan, $days));

                if ($days === 0 && $plan->status === 'active') {
                    $plan->update(['status' => 'annual_review_due']);
                }
            }
        }
    }
}
