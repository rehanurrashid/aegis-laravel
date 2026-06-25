<?php

declare(strict_types=1);

namespace App\Events\Admin;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class RefundProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $paymentId, public int $amountCents) {}

}
