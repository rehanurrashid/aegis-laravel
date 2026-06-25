<?php
// Domain 8 — bp_invoice_payments — UC-PRV-136; UC-BP invoice_mark_paid_manually; UC-XP-012

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_invoice_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('invoice_id', 36)->index();
            $table->char('payer_id', 36)->index();
            $table->integer('amount_cents')->default(0);
            $table->enum('method', ['stripe', 'manual', 'ach', 'card'])->default('stripe');
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'])->default('pending')->index();
            $table->string('stripe_payment_intent', 64)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_invoice_payments');
    }
};
