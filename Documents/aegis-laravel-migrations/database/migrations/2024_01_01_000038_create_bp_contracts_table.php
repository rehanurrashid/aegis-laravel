<?php
// Domain 8 — bp_contracts — UC-PRV-132,135,137,138; UC-XP-010

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('job_id', 36)->nullable()->index();
            $table->char('proposal_id', 36)->nullable()->index();
            $table->char('practitioner_id', 36)->index();
            $table->char('bp_id', 36)->index();
            $table->string('title', 191);
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft')->index();
            $table->integer('total_value_cents')->default(0);
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['bp_id', 'status'], 'ix_contract_bp_status');
            $table->index(['practitioner_id', 'status'], 'ix_contract_pract_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_contracts');
    }
};
