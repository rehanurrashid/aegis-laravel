<?php
// Domain 2 — continuity_plans — UC-PRV-030,035,036,038,039,040; UC-XP-002,003,008,014; UC-ADM-021

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('continuity_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('practitioner_id', 36)->index();
            $table->enum('status', ['draft', 'pending_review', 'active', 'annual_review_due', 'expired'])->default('draft')->index();
            $table->integer('plan_version')->default(1);
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_name', 191)->nullable();
            $table->string('signature_title', 191)->nullable();
            $table->string('signature_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('annual_review_date')->nullable()->index();
            $table->timestamp('last_review_at')->nullable();
            $table->text('annual_review_notes')->nullable();
            $table->timestamp('vault_attested_at')->nullable()->index();
            $table->text('vault_attestation_note')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['status', 'annual_review_date'], 'ix_plan_status_review');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('continuity_plans');
    }
};
