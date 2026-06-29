<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_threads', function (Blueprint $table) {
            if (!Schema::hasColumn('message_threads', 'participant_ids')) {
                $table->json('participant_ids')->nullable()->after('subject');
            }
            if (!Schema::hasColumn('message_threads', 'title')) {
                $table->string('title', 200)->nullable()->after('participant_ids');
            }
            if (!Schema::hasColumn('message_threads', 'is_continuity_contact')) {
                $table->tinyInteger('is_continuity_contact')->default(0)->index()->after('is_muted');
            }
            if (!Schema::hasColumn('message_threads', 'incident_id')) {
                $table->char('incident_id', 36)->nullable()->index()->after('is_continuity_contact');
            }
            if (!Schema::hasColumn('message_threads', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('message_threads', function (Blueprint $table) {
            foreach (['participant_ids', 'title', 'is_continuity_contact', 'incident_id', 'archived_at'] as $col) {
                if (Schema::hasColumn('message_threads', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
