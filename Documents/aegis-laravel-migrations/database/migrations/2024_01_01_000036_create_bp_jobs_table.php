<?php
// Domain 8 — bp_jobs — UC-PRV-130,134; UC-BP find-jobs

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('practitioner_id', 36)->index();
            $table->string('title', 191);
            $table->string('category', 64)->index();
            $table->text('description')->nullable();
            $table->enum('budget_type', ['fixed', 'hourly', 'retainer'])->default('fixed')->index();
            $table->integer('budget_amount_cents')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('location_pref', 40)->nullable();
            $table->enum('status', ['draft', 'open', 'paused', 'closed', 'filled', 'cancelled'])->default('open')->index();
            $table->tinyInteger('is_urgent')->default(0)->index();
            $table->integer('proposals_count')->default(0);
            $table->timestamp('posted_at')->nullable()->index();
            $table->timestamp('closes_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['status', 'category'], 'ix_job_status_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_jobs');
    }
};
