<?php

declare(strict_types=1);

namespace App\Events\Business;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MilestoneAutoApproveFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public \App\Models\BpContract|\App\Models\BpMilestone $subject,
        public ?string $message = null,
    ) {}
}
