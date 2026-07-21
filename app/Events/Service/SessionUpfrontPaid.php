<?php

declare(strict_types=1);

namespace App\Events\Service;

use App\Models\PractitionerPayment;
use App\Models\ServiceSession;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Rev 4: Replaces SessionDepositPaid for new payment terms system.
// Fires when the upfront portion is charged (full_upfront = 100%, split = configured %).
// SessionDepositPaid is also fired alongside this for one release cycle (BC layer).

class SessionUpfrontPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ServiceSession      $session,
        public User                $client,
        public User                $practitioner,
        public PractitionerPayment $payment,
        public int                 $upfrontCents,
    ) {}
}
