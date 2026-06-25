<?php
// Domain 3 — incident_meta — UC-CS-041; EMAIL T30

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_meta', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('incident_id', 36)->index();
            $table->string('meta_key', 191);
            $table->longText('meta_value')->nullable();
            $table->enum('meta_type', ['string', 'int', 'boolean', 'json', 'timestamp'])->default('string');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['incident_id', 'meta_key'], 'uq_incident_meta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_meta');
    }
};
