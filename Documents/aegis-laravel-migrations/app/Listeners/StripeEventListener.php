<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\StripeWebhookEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Handles incoming Stripe webhooks. Logs every event to stripe_webhook_events,
 * then routes specific types to local handlers.
 */
class StripeEventListener
{
    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;
        $type    = $payload['type'] ?? 'unknown';
        $eventId = $payload['id'] ?? ('evt_' . Str::lower(Str::random(12)));

        // Idempotency — Stripe may re-send. Skip if already logged.
        $existing = StripeWebhookEvent::where('stripe_event_id', $eventId)->first();
        if ($existing && $existing->processed) {
            return;
        }

        $row = $existing ?? StripeWebhookEvent::create([
            'id'              => 'swe_' . Str::lower(Str::random(12)),
            'stripe_event_id' => $eventId,
            'event_type'      => $type,
            'payload_json'    => json_encode($payload),
            'received_at'     => now(),
            'processed'       => 0,
        ]);

        try {
            match ($type) {
                'invoice.payment_succeeded'      => $this->handlePaymentSucceeded($payload),
                'invoice.payment_failed'         => $this->handlePaymentFailed($payload),
                'customer.subscription.created'  => $this->handleSubscriptionCreated($payload),
                'customer.subscription.updated'  => $this->handleSubscriptionUpdated($payload),
                'customer.subscription.deleted'  => $this->handleSubscriptionCancelled($payload),
                'charge.refunded'                => $this->handleChargeRefunded($payload),
                'transfer.created'               => $this->handleTransferCreated($payload),
                'transfer.paid'                  => $this->handleTransferPaid($payload),
                'transfer.failed'                => $this->handleTransferFailed($payload),
                default                          => Log::info('Unhandled Stripe webhook type', ['type' => $type]),
            };

            $row->update(['processed' => 1, 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $row->update([
                'last_error'  => $e->getMessage(),
                'attempts'    => ($row->attempts ?? 0) + 1,
            ]);
            Log::error('Stripe webhook handler error', ['type' => $type, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function handlePaymentSucceeded(array $payload): void
    {
        $invoice = $payload['data']['object'] ?? [];
        $customerId = $invoice['customer'] ?? null;
        if (!$customerId) return;

        $user = User::where('stripe_id', $customerId)->first();
        if (!$user) return;

        // Cashier will sync the subscription state via its own listener.
        Log::info('Stripe payment succeeded', ['user' => $user->id, 'invoice' => $invoice['id'] ?? null]);
    }

    private function handlePaymentFailed(array $payload): void
    {
        $invoice = $payload['data']['object'] ?? [];
        $customerId = $invoice['customer'] ?? null;
        $user = $customerId ? User::where('stripe_id', $customerId)->first() : null;
        if (!$user) return;

        // Surface failure as a critical activity event for the practitioner.
        app(\App\Services\ActivityService::class)->log(
            $user->id,
            $user->role === 'business_partner' ? 'business_partner' : 'provider',
            'payment',
            \App\Enums\ActivitySeverity::Critical,
            'payment_failed',
            'Payment failed',
            'Update your payment method to keep your account in good standing.',
            'stripe_invoice',
            $invoice['id'] ?? null,
            null
        );
    }

    private function handleSubscriptionCreated(array $payload): void
    {
        // Cashier handles DB sync; nothing custom here yet.
    }

    private function handleSubscriptionUpdated(array $payload): void
    {
        $sub = $payload['data']['object'] ?? [];
        $customerId = $sub['customer'] ?? null;
        $user = $customerId ? User::where('stripe_id', $customerId)->first() : null;
        if (!$user) return;

        // Sync tier based on the current Stripe price
        $priceId = $sub['items']['data'][0]['price']['id'] ?? null;
        if ($priceId) {
            $tier = config("aegis.stripe_price_to_tier.{$priceId}");
            if ($tier) $user->update(['tier' => $tier]);
        }
    }

    private function handleSubscriptionCancelled(array $payload): void
    {
        $sub = $payload['data']['object'] ?? [];
        $customerId = $sub['customer'] ?? null;
        $user = $customerId ? User::where('stripe_id', $customerId)->first() : null;
        if (!$user) return;

        app(\App\Services\ActivityService::class)->log(
            $user->id,
            $user->role === 'business_partner' ? 'business_partner' : 'provider',
            'payment',
            \App\Enums\ActivitySeverity::Warning,
            'subscription_cancelled',
            'Your subscription has ended',
            'Resubscribe at any time to restore full features.',
            'stripe_subscription',
            $sub['id'] ?? null,
            null
        );
    }

    private function handleChargeRefunded(array $payload): void
    {
        $charge = $payload['data']['object'] ?? [];
        Log::info('Stripe charge refunded', ['charge' => $charge['id'] ?? null]);
    }

    private function handleTransferCreated(array $payload): void
    {
        // Connect transfer initiated — local payout record already updated by PayoutService.
    }

    private function handleTransferPaid(array $payload): void
    {
        $transfer = $payload['data']['object'] ?? [];
        $transferId = $transfer['id'] ?? null;
        if (!$transferId) return;

        \App\Models\BpPayout::where('stripe_transfer_id', $transferId)->update(['status' => 'paid', 'paid_at' => now()]);
        \App\Models\CsPayout::where('stripe_transfer_id', $transferId)->update(['status' => 'paid', 'paid_at' => now()]);
    }

    private function handleTransferFailed(array $payload): void
    {
        $transfer = $payload['data']['object'] ?? [];
        $transferId = $transfer['id'] ?? null;
        if (!$transferId) return;

        \App\Models\BpPayout::where('stripe_transfer_id', $transferId)->update(['status' => 'failed']);
        \App\Models\CsPayout::where('stripe_transfer_id', $transferId)->update(['status' => 'failed']);
    }
}
