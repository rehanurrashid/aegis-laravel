<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Fix subscriptions.user_id column type from bigint to string (varchar 255)
 * to support Aegis UUID primary keys on the users table.
 *
 * Cashier's published migration used foreignId() which creates BIGINT UNSIGNED.
 * Aegis uses CHAR(36) UUIDs on users.id — the mismatch causes "column not found"
 * errors at runtime even though the column exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only run if the column is currently an integer type
        $cols = DB::select("SHOW COLUMNS FROM `subscriptions` LIKE 'user_id'");

        if (empty($cols)) {
            // Column doesn't exist yet — add it as string
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->string('user_id')->nullable()->after('id');
                $table->index(['user_id', 'stripe_status']);
            });
            return;
        }

        $type = strtolower($cols[0]->Type ?? '');

        if (str_contains($type, 'int')) {
            // Drop existing integer FK index if present, then modify column
            try {
                DB::statement('ALTER TABLE `subscriptions` DROP INDEX `subscriptions_user_id_stripe_status_index`');
            } catch (\Throwable $e) {
                // Index may not exist under this name — continue
            }

            // Change user_id to VARCHAR(255) to hold UUID strings
            DB::statement('ALTER TABLE `subscriptions` MODIFY `user_id` VARCHAR(255) NOT NULL');

            // Recreate index
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->index(['user_id', 'stripe_status']);
            });
        }
        // If already string — nothing to do
    }

    public function down(): void
    {
        // Reversing this would destroy data — intentionally left as no-op
    }
};
