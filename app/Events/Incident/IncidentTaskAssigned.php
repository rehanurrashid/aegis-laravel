<?php

declare(strict_types=1);

namespace App\Events\Incident;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentTaskAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\IncidentTask $task, public \App\Models\User $assignee) {}
}
