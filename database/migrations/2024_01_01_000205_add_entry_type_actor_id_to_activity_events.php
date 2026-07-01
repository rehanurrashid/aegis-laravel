<?php
// Add entry_type ('log'|'notification') and actor_id to activity_events.
// entry_type: 'log' = actor's own history; 'notification' = another party's feed.
// actor_id: who triggered the action (populated on notifications so the recipient
//           knows who did it — e.g. "Dr Johnson accepted your proposal").

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            $table->enum('entry_type', ['log', 'notification'])->default('log')->after('module');
            $table->char('actor_id', 36)->nullable()->after('entry_type');
            $table->index('entry_type', 'ix_act_entry_type');
            $table->index('actor_id',   'ix_act_actor_id');
        });

        // Back-fill: existing rows that have scoped_provider_id are cross-user
        // notifications; rows without it are actor logs.
        \Illuminate\Support\Facades\DB::statement("
            UPDATE activity_events
            SET entry_type = 'notification',
                actor_id   = scoped_provider_id
            WHERE scoped_provider_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            $table->dropIndex('ix_act_entry_type');
            $table->dropIndex('ix_act_actor_id');
            $table->dropColumn(['entry_type', 'actor_id']);
        });
    }
};
