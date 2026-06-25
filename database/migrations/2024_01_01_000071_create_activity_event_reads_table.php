<?php
// Domain 14 — activity_event_reads — UC-PRV-092,093 (and equivalents across CS/SS/BP/Admin)
// Per-user read tracking for activity feed events. Without this, "mark as read" / "mark all read"
// and "unread count" widgets cannot be implemented.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_event_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->index();
            $table->char('activity_event_id', 36)->index();
            $table->timestamp('read_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent()->index();

            // Each user reads each event at most once
            $table->unique(['user_id', 'activity_event_id'], 'uq_activity_event_reads_user_event');
            // Useful for "unread count by module" queries
            $table->index(['user_id', 'read_at'], 'ix_activity_event_reads_user_read_at');
        });

        // FKs added inline since they target existing tables
        Schema::table('activity_event_reads', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('activity_event_id')->references('id')->on('activity_events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_event_reads');
    }
};
