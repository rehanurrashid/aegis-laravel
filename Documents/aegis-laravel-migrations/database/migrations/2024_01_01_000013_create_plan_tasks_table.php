<?php
// Domain 2 — plan_tasks — UC-PRV-033,054

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('plan_id', 36)->index();
            $table->enum('assigned_to', ['continuity_steward', 'support_steward'])->index();
            $table->char('steward_id', 36)->nullable()->index();
            $table->string('title', 255);
            $table->string('timeline', 191)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_tasks');
    }
};
