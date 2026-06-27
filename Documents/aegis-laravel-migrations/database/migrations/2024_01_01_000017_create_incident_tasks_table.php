<?php
// Domain 3 — incident_tasks — UC-CS-031..034; UC-XP-009

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('incident_id', 36)->index();
            $table->char('assigned_to_id', 36)->nullable()->index();
            $table->enum('assigned_role', ['continuity_steward', 'support_steward'])->index();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'complete', 'exception'])->default('pending')->index();
            $table->string('timeline', 191)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('exception_reason', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['incident_id', 'status'], 'ix_inctask_incident_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_tasks');
    }
};
