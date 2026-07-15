<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ContinuityPlan;
use Illuminate\Console\Command;

class CheckAnnualReviewDates extends Command
{
    protected $signature   = 'aegis:check-annual-review-dates';
    protected $description = 'Flip active plans to annual_review_due when annual_review_date has passed.';

    public function handle(): int
    {
        $count = ContinuityPlan::query()
            ->where('status', 'active')
            ->whereNotNull('annual_review_date')
            ->whereDate('annual_review_date', '<', now()->toDateString())
            ->update(['status' => 'annual_review_due', 'updated_at' => now()]);

        $this->info("Flagged {$count} plan(s) as annual_review_due.");
        return self::SUCCESS;
    }
}
