<?php

declare(strict_types=1);

namespace App\Events\Stripe;

/**
 * Local stub that mirrors Laravel\Cashier\Events\WebhookReceived.
 * Cashier is not installed — this lets StripeEventListener and
 * AppServiceProvider reference the event without a missing-class fatal.
 */
class WebhookReceived
{
    public function __construct(public readonly array $payload = []) {}
}
