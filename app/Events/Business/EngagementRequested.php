<?php
declare(strict_types=1);
namespace App\Events\Business;
use App\Events\AegisEvent;
use App\Models\User;
class EngagementRequested extends AegisEvent
{
    public function __construct(
        public readonly User   $bp,
        public readonly User   $practitioner,
        public readonly string $type,          // 'hire'|'quote'|'consultation'
        public readonly array  $details,
    ) {}
}
