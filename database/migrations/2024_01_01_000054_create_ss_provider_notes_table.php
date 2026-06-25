<?php
// Domain 10 — ss_provider_notes — UC-SS add_incident_note / provider notes

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ss_provider_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('ss_id', 36)->index();
            $table->char('practitioner_id', 36)->index();
            $table->text('body');
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ss_provider_notes');
    }
};
