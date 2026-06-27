<?php
// Domain 8 — bp_milestones — UC-PRV-135; UC-BP milestone submit/approve

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_milestones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('contract_id', 36)->index();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->integer('amount_cents')->default(0);
            $table->enum('status', ['pending', 'submitted', 'approved', 'rejected', 'paid'])->default('pending')->index();
            $table->char('assigned_member_id', 36)->nullable()->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['contract_id', 'status'], 'ix_ms_contract_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_milestones');
    }
};
