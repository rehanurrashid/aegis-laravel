<?php

declare(strict_types=1);

namespace App\Events\Plan;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VaultItemShared
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\VaultItem $item, public array $stewardIds, public \App\Models\User $sharer) {}
}
