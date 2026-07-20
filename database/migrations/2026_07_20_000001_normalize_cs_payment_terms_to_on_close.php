<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize all CS payment terms to 'on_close'.
     * The UI no longer exposes payment_terms selection — billing always
     * triggers when the incident closes and all CS tasks are complete.
     */
    public function up(): void
    {
        DB::table('plan_stewards')->update(['payment_terms' => 'on_close']);
    }

    public function down(): void
    {
        // No rollback — 'on_close' was already the default; data is not lossy.
    }
};
