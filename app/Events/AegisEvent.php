<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Abstract base for all Aegis domain events. Concrete events extend this
 * or use Dispatchable+SerializesModels directly (legacy style).
 */
abstract class AegisEvent
{
    use Dispatchable, SerializesModels;
}
