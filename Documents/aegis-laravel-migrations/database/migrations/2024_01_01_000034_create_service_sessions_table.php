<?php
// Domain 7 — service_sessions — UC-PRV-124

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('service_request_id', 36)->index();
            $table->char('service_id', 36)->index();
            $table->char('practitioner_id', 36)->index();
            $table->char('client_id', 36)->nullable()->index();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled')->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('amount_cents')->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_sessions');
    }
};
