<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\StripeWebhookEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Events\Stripe\PaymentFailed;
use App\Events\Stripe\PaymentReceived;
use App\Events\Stripe\WebhookReceived;

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
                'payment_intent.succeeded'       => $this->handlePaymentIntentSucceeded($payload),
                'payment_intent.payment_failed'  => $this->handlePaymentIntentFailed($payload),
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
        $invoice    = $payload['data']['object'] ?? [];
        $customerId = $invoice['customer'] ?? null;
        if (! $customerId) return;

        $user = User::where('stripe_id', $customerId)->first();
        if (! $user) return;

        $amountCents = (int) ($invoice['amount_paid'] ?? 0);
        $paymentRef  = $invoice['number'] ?? ($invoice['id'] ?? 'N/A');
        $periodStart = isset($invoice['period_start'])
            ? \Carbon\Carbon::createFromTimestamp($invoice['period_start'])->format('M Y')
            : now()->format('M Y');
        $priceId     = $invoice['lines']['data'][0]['price']['id'] ?? null;
        $planLabel   = $priceId ? (config("aegis.stripe_price_to_tier.{$priceId}") ?? 'Standard') : 'Standard';

        Log::info('Stripe payment succeeded', ['user' => $user->id, 'invoice' => $invoice['id'] ?? null]);

        event(new PaymentReceived($user, $amountCents, $paymentRef, $periodStart, $planLabel));
    }

    private function handlePaymentFailed(array $payload): void
    {
        $invoice    = $payload['data']['object'] ?? [];
        $customerId = $invoice['customer'] ?? null;
        $user       = $customerId ? User::where('stripe_id', $customerId)->first() : null;
        if (! $user) return;

        $amountCents   = (int) ($invoice['amount_due'] ?? 0);
        $failureReason = $invoice['last_payment_error']['message'] ?? 'Payment declined';
        $nextRetry     = isset($invoice['next_payment_attempt'])
            ? \Carbon\Carbon::createFromTimestamp($invoice['next_payment_attempt'])->toFormattedDateString()
            : null;

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

        event(new PaymentFailed($user, $amountCents, $failureReason, $nextRetry));
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

    /**
     * PaymentIntent succeeded — destination charge confirmed.
     * Marks the BpPayout as paid and notifies both parties.
     */
    private function handlePaymentIntentSucceeded(array $payload): void
    {
        $intent   = $payload['data']['object'] ?? [];
        $intentId = $intent['id'] ?? null;
        if (!$intentId) return;

        // ── Check BP contract payments first ─────────────────────────────
        $payout = \App\Models\BpPayout::where('stripe_payment_intent_id', $intentId)->first();

        if ($payout) {
            if ($payout->status === 'paid') return; // Already confirmed — idempotent

            $payout->update(['status' => 'paid', 'paid_at' => now()]);

            event(new \App\Events\Business\PayoutReleased($payout->fresh()));

            $bp       = \App\Models\User::find($payout->bp_id);
            $provider = \App\Models\User::find($payout->provider_id);
            $amount   = '$' . number_format($payout->amount_cents / 100, 2);

            if ($provider) {
                app(\App\Services\ActivityService::class)->log(
                    $provider->id, 'provider', 'payment', \App\Enums\ActivitySeverity::Info,
                    'payment_confirmed', 'Payment confirmed by Stripe',
                    $amount . ' successfully charged and delivered to ' . ($bp?->display_name ?? 'BP') . '.',
                    'bp_payout', $payout->id, null, 'log', $provider->id
                );
            }

            if ($bp) {
                app(\App\Services\ActivityService::class)->log(
                    $bp->id, 'business_partner', 'payment', \App\Enums\ActivitySeverity::Info,
                    'payment_confirmed', 'Payment confirmed',
                    $amount . ' has been confirmed and is on its way to your bank.',
                    'bp_payout', $payout->id, $provider?->id, 'notification', $provider?->id ?? $bp->id
                );
            }
            return;
        }

        // ── Check service session payments (PractitionerPayment) ─────────
        $practitionerPayment = \App\Models\PractitionerPayment::where('stripe_payment_intent_id', $intentId)->first();

        if ($practitionerPayment) {
            if ($practitionerPayment->status === \App\Enums\PractitionerPaymentStatus::Paid->value) return;

            $practitionerPayment->update([
                'status'  => \App\Enums\PractitionerPaymentStatus::Paid->value,
                'paid_at' => now(),
            ]);

            $practitioner = \App\Models\User::find($practitionerPayment->practitioner_id);
            $amount       = '$' . number_format($practitionerPayment->amount_cents / 100, 2);

            if ($practitioner) {
                app(\App\Services\ActivityService::class)->log(
                    $practitioner->id, 'provider', 'payment', \App\Enums\ActivitySeverity::Info,
                    'session_payment_confirmed', 'Session payment confirmed by Stripe',
                    $amount . ' has been confirmed and is on its way to your Stripe account.',
                    'practitioner_payment', $practitionerPayment->id,
                    null, 'notification', $practitioner->id
                );
            }
            return;
        }

        // Not a known payment — subscription or other charge, ignore
    }

    /**
     * PaymentIntent failed — destination charge declined.
     * Marks BpPayout as failed and surfaces a critical alert to the provider.
     */
    private function handlePaymentIntentFailed(array $payload): void
    {
        $intent   = $payload['data']['object'] ?? [];
        $intentId = $intent['id'] ?? null;
        if (!$intentId) return;

        $payout = \App\Models\BpPayout::where('stripe_payment_intent_id', $intentId)->first();

        // Check service session payments if not a BP payout
        if (!$payout) {
            $practitionerPayment = \App\Models\PractitionerPayment::where('stripe_payment_intent_id', $intentId)->first();
            if ($practitionerPayment) {
                $reason = $intent['last_payment_error']['message'] ?? 'Payment declined.';
                $practitionerPayment->update(['status' => \App\Enums\PractitionerPaymentStatus::Failed->value]);
                $practitioner = \App\Models\User::find($practitionerPayment->practitioner_id);
                if ($practitioner) {
                    app(\App\Services\ActivityService::class)->log(
                        $practitioner->id, 'provider', 'payment', \App\Enums\ActivitySeverity::Critical,
                        'session_payment_failed', 'Session payment failed',
                        'Card declined: ' . $reason . ' The client may need to update their payment method.',
                        'practitioner_payment', $practitionerPayment->id,
                        null, 'notification', $practitioner->id
                    );
                }
            }
            return;
        }

        $reason   = $intent['last_payment_error']['message'] ?? 'Payment declined.';
        $payout->update(['status' => 'failed']);

        $provider = \App\Models\User::find($payout->provider_id);
        if (!$provider) return;

        app(\App\Services\ActivityService::class)->log(
            $provider->id, 'provider', 'payment', \App\Enums\ActivitySeverity::Critical,
            'payment_failed', 'Contract payment failed',
            'Your card was declined: ' . $reason . ' Please update your payment method in Settings → Billing.',
            'bp_payout', $payout->id, null, 'notification', $provider->id
        );
    }
}
