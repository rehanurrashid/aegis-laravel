<?php
// Domain 8 — bp_team_invitations — UC-XP-018; EMAIL T41

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_team_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('agency_id', 36)->index();
            $table->string('invitee_email', 191);
            $table->enum('permission_role', ['admin', 'manager', 'specialist', 'viewer'])->default('specialist');
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending')->index();
            $table->string('invite_token', 128)->unique('uq_team_invite_token');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_team_invitations');
    }
};
