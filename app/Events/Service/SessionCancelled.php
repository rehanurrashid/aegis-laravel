<?php

declare(strict_types=1);

namespace App\Events\Service;

use App\Models\ServiceSession;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ServiceSession $session,
        public User           $actor,      // who cancelled
        public string         $reason
    ) {}
}
