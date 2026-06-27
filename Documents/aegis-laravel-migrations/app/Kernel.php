<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Laravel 11 — scheduled commands live in routes/console.php. This file is
 * retained as a structural marker per AEGIS_FILE_TREE.md and exposes the
 * schedule() hook for any extensions that still rely on it.
 */
class Kernel extends ConsoleKernel
{
    /** @var array<int, class-string> */
    protected $commands = [
        Commands\DispatchDigestsCommand::class,
        Commands\ExpireStalePlansCommand::class,
        Commands\SweepOverdueInvoicesCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Defined in routes/console.php
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
