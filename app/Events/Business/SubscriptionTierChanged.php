<?php

declare(strict_types=1);

namespace App\Events\Business;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionTierChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\User $user, public string $direction, public ?string $tier = null) {}
}
