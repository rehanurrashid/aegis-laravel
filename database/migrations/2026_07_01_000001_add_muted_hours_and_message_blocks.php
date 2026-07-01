<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add muted_hours so the frontend knows which icon to show
        Schema::table('message_threads', function (Blueprint $table) {
            if (!Schema::hasColumn('message_threads', 'muted_hours')) {
                $table->unsignedSmallInteger('muted_hours')->nullable()->after('muted_until');
                // null = indefinite (bell-off), 8 = clock, 24 = moon, 168 = calendar
            }
        });

        // Thread-level block: one row per (thread, blocker)
        if (!Schema::hasTable('message_thread_blocks')) {
            Schema::create('message_thread_blocks', function (Blueprint $table) {
                $table->id();
                $table->char('thread_id', 36)->index();
                $table->char('blocker_id', 36)->index();
                $table->char('blocked_id', 36)->index();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['thread_id', 'blocker_id'], 'uq_thread_block');
                $table->foreign('thread_id')->references('id')->on('message_threads')->cascadeOnDelete();
                $table->foreign('blocker_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('blocked_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('message_thread_blocks');
        Schema::table('message_threads', function (Blueprint $table) {
            if (Schema::hasColumn('message_threads', 'muted_hours')) {
                $table->dropColumn('muted_hours');
            }
        });
    }
};
