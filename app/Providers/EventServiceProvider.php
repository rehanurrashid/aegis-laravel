<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Laravel 11 — event→listener wiring lives in AppServiceProvider::boot().
 * This provider is retained as a structural marker per AEGIS_FILE_TREE.md.
 */
class EventServiceProvider extends ServiceProvider
{
    /** @var array<class-string, array<int, class-string>> */
    protected $listen = [
        // Wired in AppServiceProvider — leave empty here.
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
