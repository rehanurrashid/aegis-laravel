<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ActivitySeverity;
use App\Services\ActivityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ActivityFanoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    /**
     * @param array<int, array{user_id:string, portal:string}> $recipients
     * @param array{module:string, severity:string|ActivitySeverity, event_type:string, title:string, description:string, linkable_type?:?string, linkable_id?:?string, related_user_id?:?string} $payload
     */
    public function __construct(
        public array $recipients,
        public array $payload
    ) {
        $this->onQueue('default');
    }

    public function handle(ActivityService $activity): void
    {
        $severity = $this->payload['severity'];
        if (is_string($severity)) {
            $severity = ActivitySeverity::from($severity);
        }

        foreach ($this->recipients as $r) {
            $activity->log(
                $r['user_id'],
                $r['portal'],
                $this->payload['module'],
                $severity,
                $this->payload['event_type'],
                $this->payload['title'],
                $this->payload['description'],
                $this->payload['linkable_type']   ?? null,
                $this->payload['linkable_id']     ?? null,
                $this->payload['related_user_id'] ?? null,
            );
        }
    }
}
