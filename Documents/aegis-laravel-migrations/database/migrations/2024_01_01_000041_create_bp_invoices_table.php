<?php
// Domain 8 — bp_invoices — UC-BP invoice_create,update_draft,invoice_send,invoice_void; UC-XP-011

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('bp_id', 36)->index();
            $table->char('practitioner_id', 36)->index();
            $table->char('contract_id', 36)->nullable()->index();
            $table->string('invoice_number', 40)->nullable()->unique('uq_bp_invoice_num');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'void'])->default('draft')->index();
            $table->integer('subtotal_cents')->default(0);
            $table->integer('total_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['practitioner_id', 'status'], 'ix_bpinv_pract_status');
            $table->index(['bp_id', 'status'], 'ix_bpinv_bp_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_invoices');
    }
};
