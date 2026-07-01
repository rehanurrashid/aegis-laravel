<?php
// Adds entry_type ('log' vs 'notification') and actor_id to activity_events.
// Idempotent — checks column existence before adding so re-runs are safe.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_events', 'entry_type')) {
                $table->enum('entry_type', ['log', 'notification'])
                      ->default('notification')
                      ->after('event_type')
                      ->index();
            }

            if (!Schema::hasColumn('activity_events', 'actor_id')) {
                $table->char('actor_id', 36)
                      ->nullable()
                      ->after('entry_type')
                      ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            if (Schema::hasColumn('activity_events', 'entry_type')) {
                $table->dropIndex(['entry_type']);
                $table->dropColumn('entry_type');
            }
            if (Schema::hasColumn('activity_events', 'actor_id')) {
                $table->dropIndex(['actor_id']);
                $table->dropColumn('actor_id');
            }
        });
    }
};
