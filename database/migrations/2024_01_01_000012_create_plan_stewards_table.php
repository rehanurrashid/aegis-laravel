<?php
// Domain 2 — plan_stewards — UC-PRV-050,051,053,054,056,204,205,206; UC-CS-001,025; UC-SS-001,015

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_stewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('plan_id', 36)->index();
            $table->char('steward_id', 36)->index();
            $table->enum('role', ['primary', 'alternate', 'support'])->default('primary')->index();
            $table->string('steward_category', 40)->nullable()->index();
            $table->enum('status', ['invited', 'active', 'declined', 'request_incoming', 'archived', 'pending'])->default('invited')->index();
            $table->json('permissions')->nullable();
            $table->enum('vault_access', ['none', 'metadata', 'scoped', 'full'])->default('none')->index();
            $table->json('responsibilities')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('review_due_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('request_sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->string('declined_reason', 255)->nullable();
            $table->timestamp('ss_acknowledged_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['plan_id', 'status'], 'ix_plansteward_plan_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_stewards');
    }
};
