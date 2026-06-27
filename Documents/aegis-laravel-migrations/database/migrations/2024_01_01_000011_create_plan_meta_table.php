<?php
// Domain 2 — plan_meta — UC-PRV-030,035

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_meta', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('plan_id', 36)->index();
            $table->string('meta_key', 191);
            $table->longText('meta_value')->nullable();
            $table->enum('meta_type', ['string', 'int', 'boolean', 'json', 'timestamp'])->default('string');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['plan_id', 'meta_key'], 'uq_plan_meta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_meta');
    }
};
