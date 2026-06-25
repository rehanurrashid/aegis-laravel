<?php

declare(strict_types=1);

namespace App\Events\News;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventRsvpReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\NewsEvent $event, public \App\Models\User $attendee) {}
}
