<?php
// Domain 9 — cs_invoices — UC-CS finances; UC-ADM-040

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cs_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('cs_id', 36)->index();
            $table->char('practitioner_id', 36)->index();
            $table->string('invoice_number', 40)->nullable()->unique('uq_cs_invoice_num');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'void'])->default('draft')->index();
            $table->integer('total_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['practitioner_id', 'status'], 'ix_csinv_pract_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cs_invoices');
    }
};
