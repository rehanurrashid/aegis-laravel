<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->index()->after('reactions');
            }
            if (!Schema::hasColumn('messages', 'read_by')) {
                $table->json('read_by')->nullable()->after('sent_at');
            }
        });

        // Backfill: if rows exist, copy created_at → sent_at
        if (Schema::hasColumn('messages', 'sent_at')) {
            \DB::statement('UPDATE messages SET sent_at = created_at WHERE sent_at IS NULL');
        }
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            foreach (['sent_at', 'read_by'] as $col) {
                if (Schema::hasColumn('messages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
