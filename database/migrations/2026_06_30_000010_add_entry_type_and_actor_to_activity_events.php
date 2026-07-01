<?php
// Adds entry_type ('log' vs 'notification') and actor_id to activity_events so
// the frontend can distinguish "things I did" (log) from "things done TO me
// by another party" (notification), and can attribute each notification to
// the acting user.
//
// Backfill policy:
//   • entry_type defaults to 'notification' for existing rows. The vast
//     majority of historical writes are cross-portal fan-outs where the
//     feed owner (user_id) was the recipient, not the actor.
//   • actor_id is left NULL for historical rows — no reliable inference
//     from scoped_provider_id (which was CS/SS→Provider scoping, not actor).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            $table->enum('entry_type', ['log', 'notification'])
                  ->default('notification')
                  ->after('event_type')
                  ->index();

            $table->char('actor_id', 36)
                  ->nullable()
                  ->after('entry_type')
                  ->index();
        });
    }

    public function down(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            $table->dropIndex(['entry_type']);
            $table->dropIndex(['actor_id']);
            $table->dropColumn(['entry_type', 'actor_id']);
        });
    }
};
