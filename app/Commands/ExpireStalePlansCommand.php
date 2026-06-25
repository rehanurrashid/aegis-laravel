<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AnnualReviewReminderJob;
use App\Models\ContinuityPlan;
use Illuminate\Console\Command;

class ExpireStalePlansCommand extends Command
{
    protected $signature = 'aegis:expire-stale-plans';

    protected $description = 'Mark plans past their annual_review_due_at as stale and queue reminder emails.';

    public function handle(): int
    {
        $count = 0;

        ContinuityPlan::query()
            ->where('status', 'active')
            ->whereDate('annual_review_due_at', '<=', now()->toDateString())
            ->chunkById(100, function ($plans) use (&$count) {
                foreach ($plans as $plan) {
                    AnnualReviewReminderJob::dispatch($plan->id);
                    $count++;
                }
            });

        $this->info("Queued reminders for {$count} stale plans.");
        return self::SUCCESS;
    }
}
