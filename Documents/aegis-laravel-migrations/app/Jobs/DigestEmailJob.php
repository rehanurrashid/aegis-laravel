<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/**
 * Weekly (Sun 08:00) + Monthly (1st 08:00).
 * Sends template 56 (weekly) or 57 (monthly) to users with notify_summary=true.
 */
class DigestEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $cadence = 'weekly') // 'weekly' | 'monthly'
    {
        if (!in_array($cadence, ['weekly', 'monthly'], true)) {
            throw new RuntimeException("Invalid digest cadence: {$cadence}");
        }
        $this->onQueue('digest');
    }

    public function handle(): void
    {
        $template = $this->cadence === 'weekly'
            ? 'emails.summary.56-weekly-digest'
            : 'emails.summary.57-monthly-digest';

        // Find users opted-in to summaries
        $optedInUserIds = UserMeta::where('meta_key', 'notify_summary')
            ->whereIn('meta_value', ['1', 'true', 'on'])
            ->pluck('user_id');

        $users = User::whereIn('id', $optedInUserIds)
            ->whereNull('deactivated_at')
            ->whereNull('locked_at')
            ->get();

        foreach ($users as $user) {
            SendEmailJob::dispatch(
                $template,
                ['cadence' => $this->cadence, 'user_id' => $user->id, 'period' => $this->periodLabel()],
                $user->id
            );
        }
    }

    private function periodLabel(): string
    {
        return $this->cadence === 'weekly'
            ? 'week of ' . now()->subWeek()->format('M j')
            : now()->subMonth()->format('F Y');
    }
}
