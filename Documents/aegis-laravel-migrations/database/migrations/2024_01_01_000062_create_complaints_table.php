<?php
// Domain 12 — complaints — UC-ADM-050..057; UC-XP-015

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('submitter_id', 36)->index();
            $table->string('subject', 191);
            $table->text('body');
            $table->enum('category', ['support_ticket', 'feedback', 'complaint'])->default('support_ticket')->index();
            $table->string('submission_channel', 40)->default('ticket')->index();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open')->index();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->index();
            $table->char('assigned_to', 36)->nullable()->index();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['status', 'created_at'], 'ix_compl_status_created');
            $table->index(['submitter_id', 'status'], 'ix_compl_submitter_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
