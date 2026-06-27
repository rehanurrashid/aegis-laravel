<?php

declare(strict_types=1);

namespace App\Events\Network;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferralReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\Referral $referral, public \App\Models\User $sender, public \App\Models\User $recipient) {}
}
