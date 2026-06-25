<?php
// Domain 3 — critical_incidents — UC-PRV-090; UC-SS-030; UC-CS-041; UC-XP-004..007,024; UC-ADM-004

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('critical_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('plan_id', 36)->index();
            $table->char('practitioner_id', 36)->index();
            $table->char('reported_by_id', 36)->index();
            $table->string('incident_type', 64)->index();
            $table->enum('status', ['reported', 'verified', 'active', 'closed'])->default('reported')->index();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('critical')->index();
            $table->timestamp('reported_at')->useCurrent()->index();
            $table->timestamp('verified_at')->nullable();
            $table->char('verified_by_id', 36)->nullable()->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['status', 'reported_at'], 'ix_incident_status_reported');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('critical_incidents');
    }
};
