<?php
// Domain 6 — message_threads — UC-PRV (messages); UC-XP-015

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('subject', 191)->nullable();
            $table->char('created_by_id', 36)->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->tinyInteger('is_pinned')->default(0)->index();
            $table->tinyInteger('is_muted')->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_threads');
    }
};
