<?php

declare(strict_types=1);

namespace App\Events\Network;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConnectionAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\NetworkConnection $connection, public \App\Models\User $accepter) {}
}
