<?php
// Domain 1 — user_roles — UC-XP-031; UC-ADM-026

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->index();
            $table->enum('role', ['practitioner', 'continuity_steward', 'support_steward', 'business_partner', 'admin'])->index();
            $table->tinyInteger('is_default')->default(0)->index();
            $table->timestamp('enabled_at')->useCurrent();

            $table->unique(['user_id', 'role'], 'uq_user_roles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
