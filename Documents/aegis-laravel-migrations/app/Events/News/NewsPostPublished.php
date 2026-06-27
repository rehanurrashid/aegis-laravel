<?php

declare(strict_types=1);

namespace App\Events\News;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewsPostPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\NewsPost $post) {}
}
