<?php

declare(strict_types=1);

namespace App\Events\Incident;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentActivated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\CriticalIncident $incident) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('incident.' . $this->incident->practitioner_id)];
    }
}
