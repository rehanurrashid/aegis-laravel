<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\StripeWebhookEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Events\Stripe\PaymentFailed;
use App\Events\Account\SubscriptionRenewalUpcoming;
use App\Events\Stripe\PaymentReceived;
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
        $obj     = $payload['data']['object'] ?? [];

        Log::info('[STRIPE_WEBHOOK] received', [
            'event_id'    => $eventId,
            'type'        => $type,
            'customer_id' => $obj['customer'] ?? $obj['id'] ?? null,
            'object_id'   => $obj['id'] ?? null,
        ]);

        // Idempotency — Stripe may re-send. Skip if already logged.
        $existing = StripeWebhookEvent::where('stripe_event_id', $eventId)->first();
        if ($existing && $existing->processed) {
            Log::info('[STRIPE_WEBHOOK] skipped (already processed)', ['event_id' => $eventId]);
            return;
        }

        // ── DB write ─────────────────────────────────────────────────────
        try {
            $row = $existing ?? StripeWebhookEvent::create([
                'id'              => \Illuminate\Support\Str::uuid()->toString(),
                'stripe_event_id' => $eventId,
                'event_type'      => $type,
                'payload_json'    => json_encode($payload),
                'received_at'     => now(),
                'processed'       => 0,
            ]);
            Log::info('[STRIPE_WEBHOOK] logged to DB', ['event_id' => $eventId, 'row_id' => $row->id]);
        } catch (\Throwable $e) {
            Log::error('[STRIPE_WEBHOOK] DB write failed — cannot persist event', [
                'event_id' => $eventId,
                'type'     => $type,
                'error'    => $e->getMessage(),
                'trace'    => collect(explode("\n", $e->getTraceAsString()))->take(8)->implode("\n"),
            ]);
            // Re-throw so Stripe gets a 500 and retries
            throw $e;
        }

        // ── Route to handler ─────────────────────────────────────────────
        try {
            Log::info('[STRIPE_WEBHOOK] routing', ['type' => $type, 'event_id' => $eventId]);

            match ($type) {
                'invoice.payment_succeeded'      => $this->handlePaymentSucceeded($payload),
                'invoice.payment_failed'         => $this->handlePaymentFailed($payload),
                'invoice.upcoming'               => $this->handleInvoiceUpcoming($payload),
                'payment_intent.succeeded'       => $this->handlePaymentIntentSucceeded($payload),
                'payment_intent.payment_failed'  => $this->handlePaymentIntentFailed($payload),
                'customer.subscription.created'  => $this->handleSubscriptionCreated($payload),
                'customer.subscription.updated'  => $this->handleSubscriptionUpdated($payload),
                'customer.subscription.deleted'  => $this->handleSubscriptionCancelled($payload),
                'payment_method.attached'        => $this->handlePaymentMethodAttached($payload),
                'payment_method.detached'        => $this->handlePaymentMethodDetached($payload),
                'charge.refunded'                => $this->handleChargeRefunded($payload),
                'transfer.created'               => $this->handleTransferCreated($payload),
                'transfer.paid'                  => $this->handleTransferPaid($payload),
                'transfer.failed'                => $this->handleTransferFailed($payload),
                default                          => Log::info('[STRIPE_WEBHOOK] no handler (ignored)', ['type' => $type]),
            };

            $row->update(['processed' => 1, 'processed_at' => now()]);
            Log::info('[STRIPE_WEBHOOK] processed OK', ['event_id' => $eventId, 'type' => $type]);
        } catch (\Throwable $e) {
            Log::error('[STRIPE_WEBHOOK] handler error', [
                'event_id' => $eventId,
                'type'     => $type,
                'error'    => $e->getMessage(),
                'file'     => $e->getFile() . ':' . $e->getLine(),
                'trace'    => collect(explode("\n", $e->getTraceAsString()))->take(10)->implode("\n"),
            ]);
            // last_error / attempts columns are optional — only update if they exist
            $updates = [];
            if (\Illuminate\Support\Facades\Schema::hasColumn('stripe_webhook_events', 'last_error')) {
                $updates['last_error'] = $e->getMessage();
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('stripe_webhook_events', 'attempts')) {
                $updates['attempts'] = ($row->attempts ?? 0) + 1;
            }
            if ($updates) $row->update($updates);
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
        if (!$user) {
            Log::info('[STRIPE_WEBHOOK] subscription.updated — no user found', ['customer_id' => $customerId]);
            return;
        }

        // Sync tier based on the current Stripe base price (first item)
        // NOTE: users.tier enum only accepts 'access'|'practice'. BP and CS Business
        // roles map to their own columns/logic, not users.tier — so we only write
        // practitioner tiers here to avoid corrupting the enum.
        $priceId = $sub['items']['data'][0]['price']['id'] ?? null;
        if ($priceId) {
            $tier = config("aegis.stripe_price_to_tier.{$priceId}");
            if ($tier && in_array($tier, ['access', 'practice'], true)) {
                $user->update(['tier' => $tier]);
                Log::info('[STRIPE_WEBHOOK] tier synced from Stripe', [
                    'user_id'  => $user->id,
                    'tier'     => $tier,
                    'price_id' => $priceId,
                ]);
            } elseif (!$tier) {
                Log::warning('[STRIPE_WEBHOOK] Unknown price ID in subscription update — check config/aegis.php stripe_price_to_tier map', [
                    'price_id' => $priceId,
                    'user_id'  => $user->id,
                ]);
            }
            // tiers 'business_partner', 'cs_business', 'maat_addon' are intentionally
            // not written to users.tier — they don't belong to the practitioner enum.
        }

        // Sync MAAT addon state — check all subscription items for a MAAT price
        $maatMonthly = env('STRIPE_PRICE_MAAT_MONTHLY');
        $maatAnnual  = env('STRIPE_PRICE_MAAT_ANNUAL');
        $itemPriceIds = array_column(
            array_column($sub['items']['data'] ?? [], 'price'),
            'id'
        );
        $hasMaat = ($maatMonthly && in_array($maatMonthly, $itemPriceIds, true))
                || ($maatAnnual  && in_array($maatAnnual,  $itemPriceIds, true));

        if ((bool) $user->maat_addon !== $hasMaat) {
            $user->update(['maat_addon' => $hasMaat ? 1 : 0]);
            Log::info('[STRIPE_WEBHOOK] MAAT addon state synced', [
                'user_id'  => $user->id,
                'has_maat' => $hasMaat,
            ]);
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
     * Payment method attached — sync pm_type + pm_last_four on the user
     * so the Settings page shows the correct card without an extra API call.
     */
    private function handlePaymentMethodAttached(array $payload): void
    {
        $pm         = $payload['data']['object'] ?? [];
        $customerId = $pm['customer'] ?? null;
        if (!$customerId) return;

        $user = User::where('stripe_id', $customerId)->first();
        if (!$user) return;

        // Save pm_type/pm_last_four for display, AND stripe_payment_method_id for peer charges
        $updates = [
            'pm_type'      => $pm['card']['brand'] ?? ($pm['type'] ?? null),
            'pm_last_four' => $pm['card']['last4'] ?? null,
        ];
        // Only set stripe_payment_method_id if this is being attached as default
        // (payment_method.attached fires for any attachment; we set it if no existing default)
        if (!$user->stripe_payment_method_id && isset($pm['id'])) {
            $updates['stripe_payment_method_id'] = $pm['id'];
        }
        $user->update($updates);

        Log::info('[STRIPE_WEBHOOK] payment method attached', [
            'user_id' => $user->id,
            'brand'   => $pm['card']['brand'] ?? null,
            'last4'   => $pm['card']['last4'] ?? null,
        ]);
    }

    /**
     * account.updated — fired by Stripe when a Connect Express account completes onboarding.
     * Flips stripe_connected = 1 when charges_enabled && payouts_enabled.
     */
    private function handleAccountUpdated(array $payload): void
    {
        $account = $payload['data']['object'] ?? [];
        $acctId  = $account['id'] ?? null;
        if (!$acctId) return;

        $chargesEnabled  = (bool) ($account['charges_enabled']  ?? false);
        $payoutsEnabled  = (bool) ($account['payouts_enabled']  ?? false);
        $detailsSubmitted = (bool) ($account['details_submitted'] ?? false);

        $user = \App\Models\User::where('stripe_account_id', $acctId)->first();
        if (!$user) {
            Log::info('[STRIPE_WEBHOOK] account.updated — no user found', ['acct_id' => $acctId]);
            return;
        }

        $isConnected = $chargesEnabled && $payoutsEnabled && $detailsSubmitted;
        $user->update(['stripe_connected' => $isConnected ? 1 : 0]);

        Log::info('[STRIPE_WEBHOOK] account.updated — stripe_connected synced', [
            'user_id'         => $user->id,
            'stripe_connected' => $isConnected,
            'charges_enabled' => $chargesEnabled,
            'payouts_enabled' => $payoutsEnabled,
        ]);
    }

    /**
     * Payment method detached — clear pm_type / pm_last_four if the removed card
     * matches the last-four on record.
     */
    private function handlePaymentMethodDetached(array $payload): void
    {
        $pm    = $payload['data']['object'] ?? [];
        $last4 = $pm['card']['last4'] ?? null;
        if (!$last4) return;

        User::where('pm_last_four', $last4)->update([
            'pm_type'      => null,
            'pm_last_four' => null,
        ]);

        Log::info('[STRIPE_WEBHOOK] payment method detached', [
            'pm_id' => $pm['id'] ?? null,
            'last4' => $last4,
        ]);
    }

    /**
     * invoice.upcoming — fires ~7 days before subscription renewal.
     * Logs for now; a renewal-reminder email can be dispatched from here later.
     */
    private function handleInvoiceUpcoming(array $payload): void
    {
        $invoice    = $payload['data']['object'] ?? [];
        $customerId = $invoice['customer'] ?? null;
        if (!$customerId) return;

        $user = User::where('stripe_id', $customerId)->first();
        if (!$user) return;

        Log::info('[STRIPE_WEBHOOK] invoice.upcoming — renewal due soon', [
            'user_id'      => $user->id,
            'amount_cents' => $invoice['amount_due'] ?? 0,
            'due_date'     => $invoice['next_payment_attempt'] ?? null,
        ]);

        $amountCents = (int) ($invoice['amount_due'] ?? 0);
        $renewalDate = isset($invoice['next_payment_attempt'])
            ? \Carbon\Carbon::createFromTimestamp($invoice['next_payment_attempt'])->toFormattedDateString()
            : 'soon';

        $sub      = $user->subscription('default');
        $priceId  = $sub?->stripe_price;
        $tier     = $priceId ? (config("aegis.stripe_price_to_tier.{$priceId}") ?? 'Standard') : 'Standard';

        event(new SubscriptionRenewalUpcoming($user, $amountCents, $renewalDate, $tier));
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
