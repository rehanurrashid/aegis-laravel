<?php
// Domain 7 — ceu_entries — UC-PRV-150

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ceu_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('practitioner_id', 36)->index();
            $table->string('title', 191);
            $table->string('provider_name', 191)->nullable();
            $table->decimal('credit_hours', 5, 2)->nullable();
            $table->date('completed_on')->nullable()->index();
            $table->date('expires_on')->nullable()->index();
            $table->string('certificate_ref', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['practitioner_id', 'expires_on'], 'ix_ceu_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ceu_entries');
    }
};
