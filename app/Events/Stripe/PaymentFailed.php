<?php

declare(strict_types=1);

namespace App\Events\Stripe;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\User $user, public int $amountCents, public string $failureReason, public ?string $retryDate = null) {}
}
