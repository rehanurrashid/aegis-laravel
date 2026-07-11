<?php

declare(strict_types=1);

namespace App\Events\Service;

use App\Models\PractitionerPayment;
use App\Models\ServiceSession;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionDepositPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ServiceSession       $session,
        public User                 $client,
        public User                 $practitioner,
        public PractitionerPayment  $payment,
        public int                  $depositCents,
    ) {}
}
