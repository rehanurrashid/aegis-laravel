<?php

declare(strict_types=1);

namespace App\Events\Support;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketResolved
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\Complaint $complaint) {}
}
