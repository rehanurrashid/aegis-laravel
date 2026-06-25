<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DigestEmailJob;
use App\Models\User;
use Illuminate\Console\Command;

class DispatchDigestsCommand extends Command
{
    protected $signature = 'aegis:dispatch-digests {--cadence=weekly}';

    protected $description = 'Queue weekly/monthly digest emails for users with notify_digest enabled.';

    public function handle(): int
    {
        $cadence = (string) $this->option('cadence');

        User::query()
            ->whereNull('locked_at')
            ->whereHas('meta', function ($q) use ($cadence) {
                $q->where('key', 'digest_cadence')->where('value', $cadence);
            })
            ->chunkById(200, function ($users) use ($cadence) {
                foreach ($users as $user) {
                    DigestEmailJob::dispatch($user->id, $cadence)->onQueue('digest');
                }
            });

        $this->info("Dispatched {$cadence} digests.");
        return self::SUCCESS;
    }
}
