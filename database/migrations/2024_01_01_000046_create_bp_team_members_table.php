<?php
// Domain 8 — bp_team_members — UC-XP-018; UC-BP team (remove_member)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_team_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('agency_id', 36)->index();
            $table->char('member_id', 36)->index();
            $table->enum('permission_role', ['admin', 'manager', 'specialist', 'viewer'])->default('specialist')->index();
            $table->string('department', 64)->nullable();
            $table->enum('status', ['active', 'idle', 'inactive'])->default('active')->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['agency_id', 'member_id'], 'uq_team_member');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_team_members');
    }
};
