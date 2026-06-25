<?php
// Domain 6 — messages — UC-PRV (messages)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('thread_id', 36)->index();
            $table->char('sender_id', 36)->index();
            $table->char('recipient_id', 36)->nullable()->index();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->json('reactions')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['thread_id', 'created_at'], 'ix_msg_thread_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
