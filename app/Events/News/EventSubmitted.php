<?php

declare(strict_types=1);

namespace App\Events\News;

use App\Models\NewsEvent;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly NewsEvent $event,
        public readonly User $submitter,
    ) {}
}
