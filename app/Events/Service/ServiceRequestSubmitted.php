<?php

declare(strict_types=1);

namespace App\Events\Service;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\ServiceRequest $request, public \App\Models\User $requester) {}
}
