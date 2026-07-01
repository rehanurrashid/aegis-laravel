<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MessageThread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sweeps message_threads where is_muted=1 and muted_until <= now().
 * Clears both flags so the bell-off icon disappears on next page load.
 * Scheduled every minute via routes/console.php.
 */
class ExpireMutedThreadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function handle(): void
    {
        $count = MessageThread::where('is_muted', 1)
            ->whereNotNull('muted_until')
            ->where('muted_until', '<=', now())
            ->update([
                'is_muted'    => 0,
                'muted_until' => null,
            ]);

        if ($count > 0) {
            Log::info("ExpireMutedThreadsJob: unmuted {$count} thread(s).");
        }
    }
}
