<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Data-only migration — no schema changes.
// Backfills Rev 4 payment terms columns on existing rows, preserving
// the legacy 30/70 split so all computed attrs return identical values.

return new class extends Migration
{
    public function up(): void
    {
        // services — set split as the default for all existing listings
        DB::table('services')->update([
            'default_payment_structure'  => 'split',
            'default_upfront_percentage' => 30,
            'allow_completion_only'      => 0,
        ]);

        // service_requests — all existing requests used the provider default (split 30%)
        DB::table('service_requests')->update([
            'proposed_payment_structure'  => 'split',
            'proposed_upfront_percentage' => 30,
            'terms_source'                => 'provider_default',
        ]);

        // service_sessions — map legacy deposit/balance columns to new structure columns
        // upfront_cents   = existing deposit_cents (already charged or pre-calculated)
        // completion_cents = existing balance_cents (remaining)
        // terms_agreed_at = first meaningful timestamp (deposit paid or created_at)
        DB::statement("
            UPDATE service_sessions
               SET payment_structure    = 'split',
                   upfront_percentage   = 30,
                   upfront_cents        = COALESCE(deposit_cents, 0),
                   completion_cents     = COALESCE(balance_cents, 0),
                   terms_source         = 'provider_default',
                   terms_agreed_at      = COALESCE(deposit_paid_at, created_at)
             WHERE payment_structure IS NULL
                OR payment_structure = ''
        ");
    }

    public function down(): void
    {
        // Backfill is non-destructive — no rollback needed.
        // The columns themselves are dropped by the _000001/_000002/_000003 rollbacks.
    }
};
