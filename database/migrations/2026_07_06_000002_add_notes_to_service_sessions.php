<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_sessions', function (Blueprint $table) {
            $table->text('session_summary')->nullable()->after('amount_cents');
            $table->text('session_action_items')->nullable()->after('session_summary');
            $table->tinyInteger('share_notes_with_client')->default(0)->after('session_action_items');
            $table->string('cancel_reason', 255)->nullable()->after('share_notes_with_client');
        });
    }

    public function down(): void
    {
        Schema::table('service_sessions', function (Blueprint $table) {
            $table->dropColumn(['session_summary','session_action_items','share_notes_with_client','cancel_reason']);
        });
    }
};
