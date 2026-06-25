<?php
// Domain 8 — bp_proposals — UC-PRV-132,133; UC-BP submit_proposal,update_proposal

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('job_id', 36)->index();
            $table->char('bp_id', 36)->index();
            $table->text('cover_letter')->nullable();
            $table->integer('proposed_rate_cents')->nullable();
            $table->enum('proposed_rate_type', ['fixed', 'hourly', 'retainer'])->default('fixed');
            $table->enum('status', ['pending', 'under_review', 'accepted', 'declined', 'withdrawn'])->default('pending')->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['job_id', 'bp_id'], 'uq_proposal');
            $table->index(['job_id', 'status'], 'ix_prop_job_status');
            $table->index(['bp_id', 'status'], 'ix_prop_bp_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_proposals');
    }
};
