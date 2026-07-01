<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_threads', function (Blueprint $table) {
            if (!Schema::hasColumn('message_threads', 'muted_until')) {
                $table->timestamp('muted_until')->nullable()->after('is_muted');
            }
        });
    }

    public function down(): void
    {
        Schema::table('message_threads', function (Blueprint $table) {
            if (Schema::hasColumn('message_threads', 'muted_until')) {
                $table->dropColumn('muted_until');
            }
        });
    }
};
