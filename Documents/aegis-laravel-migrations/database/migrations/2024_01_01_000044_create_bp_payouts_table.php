<?php
// Domain 8 — bp_payouts — UC-ADM-044,045; UC-XP-012

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('bp_id', 36)->index();
            $table->integer('amount_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'in_transit', 'paid', 'failed', 'cancelled'])->default('pending')->index();
            $table->string('description', 255)->nullable();
            $table->string('stripe_payout_id', 64)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['status', 'created_at'], 'ix_payout_status_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_payouts');
    }
};
