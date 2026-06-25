<?php

declare(strict_types=1);

namespace App\Events\News;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewsCommented
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\NewsComment $comment) {}
}
