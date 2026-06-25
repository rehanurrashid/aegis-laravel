<?php
// Domain 7 — services — UC-PRV-121,122,127

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('practitioner_id', 36)->index();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->string('category', 64)->nullable()->index();
            $table->integer('price_cents')->nullable();
            $table->enum('price_type', ['fixed', 'hourly', 'session', 'inquiry'])->default('inquiry');
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->tinyInteger('is_public')->default(0)->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['practitioner_id', 'status'], 'ix_svc_pract');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
