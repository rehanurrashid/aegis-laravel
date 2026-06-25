<?php
// Domain 3 — incident_updates — UC-XP-004..007

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('incident_id', 36)->index();
            $table->char('actor_id', 36)->nullable()->index();
            $table->enum('update_type', ['reported', 'verified', 'activated', 'vault_unsealed', 'ss_notified', 'task_added', 'escalated', 'closed'])->index();
            $table->string('message', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['incident_id', 'created_at'], 'ix_incupd_incident_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_updates');
    }
};
