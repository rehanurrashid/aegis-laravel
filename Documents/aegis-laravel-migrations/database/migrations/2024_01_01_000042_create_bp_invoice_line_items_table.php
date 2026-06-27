<?php
// Domain 8 — bp_invoice_line_items — UC-BP invoice_create,update_draft

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_invoice_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('invoice_id', 36)->index();
            $table->string('description', 255);
            $table->integer('quantity')->default(1);
            $table->integer('unit_amount_cents')->default(0);
            $table->integer('line_total_cents')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_invoice_line_items');
    }
};
