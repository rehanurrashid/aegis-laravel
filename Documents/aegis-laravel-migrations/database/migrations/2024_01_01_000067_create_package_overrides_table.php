<?php
// Domain 13 — package_overrides — UC-ADM-011,012,013

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tier', 40)->unique('uq_package_tier');
            $table->integer('price_monthly_cents')->nullable();
            $table->integer('price_annual_cents')->nullable();
            $table->json('feature_flags')->nullable();
            $table->json('limits')->nullable();
            $table->timestamp('effective_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_overrides');
    }
};
