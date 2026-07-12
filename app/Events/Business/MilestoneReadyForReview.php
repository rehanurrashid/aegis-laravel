<?php

declare(strict_types=1);

namespace App\Events\Business;

use App\Models\BpMilestone;
use App\Models\BpMilestoneSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when BP submits milestone work. Notifies provider to review. */
class MilestoneReadyForReview
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BpMilestone           $milestone,
        public BpMilestoneSubmission $submission,
    ) {}
}
