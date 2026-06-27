<?php
// Domain 10 — ss_provider_checkins — UC-SS log_checkin; UC-SS-015

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ss_provider_checkins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('ss_id', 36)->index();
            $table->char('practitioner_id', 36)->index();
            $table->enum('status', ['ok', 'concern', 'unreachable'])->default('ok')->index();
            $table->text('note')->nullable();
            $table->timestamp('checked_at')->useCurrent()->index();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['practitioner_id', 'checked_at'], 'ix_checkin_pract');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ss_provider_checkins');
    }
};
