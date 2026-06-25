<?php
// Domain 6 — activity_events — UC-XP-001 + every fan-out UC; UC-XP-025

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->index();
            $table->enum('portal', ['provider', 'continuity_steward', 'support_steward', 'business_partner', 'admin'])->index();
            $table->enum('event_type', ['message', 'task', 'document', 'incident', 'vault', 'compliance', 'attestation', 'payment', 'account', 'system', 'referral', 'news', 'event', 'practitioner_unresponsive_flagged'])->index();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info')->index();
            $table->string('module', 64)->nullable();
            $table->string('action', 64)->nullable();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->string('linkable_type', 64)->nullable()->index();
            $table->char('linkable_id', 36)->nullable()->index();
            $table->char('scoped_provider_id', 36)->nullable()->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['user_id', 'created_at'], 'ix_act_user_created');
            $table->index(['portal', 'event_type', 'created_at'], 'ix_act_portal_type_created');
            $table->index(['linkable_type', 'linkable_id'], 'ix_act_linkable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_events');
    }
};
