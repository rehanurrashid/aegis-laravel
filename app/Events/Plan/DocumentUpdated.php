<?php

declare(strict_types=1);

namespace App\Events\Plan;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\ContinuityDocument $document, public \App\Models\User $updater, public string $changeType) {}
}
