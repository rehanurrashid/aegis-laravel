<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Listeners\StripeEventListener;
use App\Models\StripeWebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Events\Stripe\WebhookReceived;

/**
 * Sweeps unprocessed stripe_webhook_events rows and runs them back through
 * StripeEventListener. Retries on failure with exponential backoff.
 */
class StripeWebhookProcessorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 180, 600];

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(StripeEventListener $listener): void
    {
        $unprocessed = StripeWebhookEvent::where('processed', 0)
            ->where(function ($q) {
                $q->whereNull('attempts')->orWhere('attempts', '<', 5);
            })
            ->orderBy('received_at')
            ->limit(100)
            ->get();

        foreach ($unprocessed as $row) {
            $payload = json_decode($row->payload_json ?? '{}', true) ?: [];
            if (empty($payload['type'])) {
                $row->update(['last_error' => 'missing type', 'attempts' => ($row->attempts ?? 0) + 1]);
                continue;
            }

            try {
                $listener->handle(new WebhookReceived($payload));
            } catch (\Throwable $e) {
                Log::error('StripeWebhookProcessorJob: row failed', [
                    'row'   => $row->id,
                    'type'  => $payload['type'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                // StripeEventListener already updates attempts + last_error
            }
        }
    }
}
