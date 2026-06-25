<?php
// Domain 9 — practitioner_payments — UC-PRV-003,004,136,144,145,208,210; UC-ADM-040,041,043

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practitioner_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('practitioner_id', 36)->index();
            $table->char('payment_method_id', 36)->nullable()->index();
            $table->enum('kind', ['subscription', 'maat_addon', 'cs_fee', 'bp_invoice', 'refund'])->default('subscription')->index();
            $table->integer('amount_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['paid', 'failed', 'refunded', 'partially_refunded', 'pending'])->default('paid')->index();
            $table->string('payment_method_label', 64)->nullable();
            $table->string('stripe_charge_id', 64)->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['practitioner_id', 'kind'], 'ix_pp_pract_kind');
            $table->index(['status', 'paid_at'], 'ix_pp_status_paid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_payments');
    }
};
