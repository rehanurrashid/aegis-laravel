<?php
// Domain 2 — plan_incident_configs — UC-PRV-031,053

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_incident_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('plan_id', 36)->index();
            $table->string('incident_type', 64)->index();
            $table->tinyInteger('is_active')->default(0)->index();
            $table->json('docs_required')->nullable();
            $table->json('authorized_ss_ids')->nullable();
            $table->json('authorized_cs_ids')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['plan_id', 'incident_type'], 'uq_planinc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_incident_configs');
    }
};
