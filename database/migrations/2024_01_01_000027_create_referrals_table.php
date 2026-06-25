<?php
// Domain 5 — referrals — UC-PRV-108,110,111

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('sender_id', 36)->index();
            $table->char('recipient_id', 36)->index();
            $table->enum('status', ['sent', 'accepted', 'declined', 'closed', 'cancelled'])->default('sent')->index();
            $table->string('subject', 191)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['recipient_id', 'status'], 'ix_ref_recipient_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
