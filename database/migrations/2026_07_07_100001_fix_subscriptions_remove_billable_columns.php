<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * The subscriptions table was created with billable_id + billable_type
 * (polymorphic pattern) from an earlier migration, but Cashier v16 uses
 * user_id (non-polymorphic). MySQL rejects inserts because billable_id
 * has no default value.
 *
 * This migration:
 *  1. Drops billable_id and billable_type if they exist
 *  2. Ensures user_id is VARCHAR(255) (UUID-compatible)
 *  3. Ensures the user_id + stripe_status composite index exists
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Drop billable columns if present
            if (Schema::hasColumn('subscriptions', 'billable_id')) {
                $table->dropColumn('billable_id');
            }
            if (Schema::hasColumn('subscriptions', 'billable_type')) {
                $table->dropColumn('billable_type');
            }
        });

        // Ensure user_id is string type (UUID-compatible)
        $cols = DB::select("SHOW COLUMNS FROM `subscriptions` LIKE 'user_id'");
        if (!empty($cols)) {
            $type = strtolower($cols[0]->Type ?? '');
            if (str_contains($type, 'int')) {
                // Drop composite index before modifying column
                try {
                    DB::statement('ALTER TABLE `subscriptions` DROP INDEX `subscriptions_user_id_stripe_status_index`');
                } catch (\Throwable $e) { /* may not exist */ }

                DB::statement('ALTER TABLE `subscriptions` MODIFY `user_id` VARCHAR(255) NOT NULL');

                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->index(['user_id', 'stripe_status']);
                });
            }
        } elseif (!Schema::hasColumn('subscriptions', 'user_id')) {
            // user_id missing entirely — add it
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->string('user_id')->after('id');
                $table->index(['user_id', 'stripe_status']);
            });
        }
    }

    public function down(): void
    {
        // No safe rollback — billable data would be lost
    }
};
