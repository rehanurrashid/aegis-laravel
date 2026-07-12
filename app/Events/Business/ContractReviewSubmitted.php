<?php

declare(strict_types=1);

namespace App\Events\Business;

use App\Models\BpContractReview;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when either party submits a post-completion contract review. */
class ContractReviewSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BpContractReview $review,
    ) {}
}
