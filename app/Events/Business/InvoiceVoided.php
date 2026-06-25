<?php

declare(strict_types=1);

namespace App\Events\Business;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceVoided
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\BpInvoice $invoice, public \App\Models\User $actor, public ?string $reason = null) {}
}
