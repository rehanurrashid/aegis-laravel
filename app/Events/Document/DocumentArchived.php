<?php
declare(strict_types=1);
namespace App\Events\Document;
use App\Models\ContinuityDocument;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class DocumentArchived
{
    use Dispatchable, SerializesModels;
    public function __construct(
        public ContinuityDocument $document,
        public User $actor,
        public string $reason = 'archived'
    ) {}
}
