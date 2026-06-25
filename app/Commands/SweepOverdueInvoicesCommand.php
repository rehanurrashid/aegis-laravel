<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BpInvoice;
use Illuminate\Console\Command;

class SweepOverdueInvoicesCommand extends Command
{
    protected $signature = 'aegis:sweep-overdue-invoices';

    protected $description = 'Mark sent invoices past their due_at as overdue.';

    public function handle(): int
    {
        $updated = BpInvoice::query()
            ->where('status', 'sent')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->update(['status' => 'overdue', 'updated_at' => now()]);

        $this->info("Marked {$updated} invoices overdue.");
        return self::SUCCESS;
    }
}
