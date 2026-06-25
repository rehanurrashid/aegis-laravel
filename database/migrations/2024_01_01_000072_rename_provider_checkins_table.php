<?php
// Domain 9 — provider_checkins — UC-CS-015,016; UC-SS-040,041
// Rename `ss_provider_checkins` to `provider_checkins` and add `steward_type` enum so
// the table can host both CS proactive check-ins (UC-CS-015) and SS provider check-ins.
//
// Backwards-compatible: existing `ss_id` column is renamed to `steward_id`. Existing rows are
// backfilled with `steward_type='ss'`. New rows from CS portal use `steward_type='cs'`.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: rename column ss_id → steward_id (using doctrine/dbal would normally be needed
        // for column rename pre-Laravel 10.x; Laravel 11 supports renameColumn natively).
        Schema::table('ss_provider_checkins', function (Blueprint $table) {
            $table->renameColumn('ss_id', 'steward_id');
        });

        // Step 2: add steward_type enum column with default 'ss' so existing rows are correct
        Schema::table('ss_provider_checkins', function (Blueprint $table) {
            $table->enum('steward_type', ['cs', 'ss'])->default('ss')->after('steward_id')->index();
        });

        // Step 3: rename the table itself
        Schema::rename('ss_provider_checkins', 'provider_checkins');

        // Step 4: add composite index for steward dashboard queries
        Schema::table('provider_checkins', function (Blueprint $table) {
            $table->index(['steward_id', 'steward_type', 'created_at'], 'ix_provider_checkins_steward_type_created');
        });
    }

    public function down(): void
    {
        Schema::table('provider_checkins', function (Blueprint $table) {
            $table->dropIndex('ix_provider_checkins_steward_type_created');
        });
        Schema::rename('provider_checkins', 'ss_provider_checkins');
        Schema::table('ss_provider_checkins', function (Blueprint $table) {
            $table->dropColumn('steward_type');
            $table->renameColumn('steward_id', 'ss_id');
        });
    }
};
