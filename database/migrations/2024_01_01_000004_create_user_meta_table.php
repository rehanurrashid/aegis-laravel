<?php
// Domain 1 — user_meta — UC-PRV-012,016,019; EMAIL_TEMPLATES §B

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_meta', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->index();
            $table->string('meta_key', 191);
            $table->longText('meta_value')->nullable();
            $table->enum('meta_type', ['string', 'int', 'boolean', 'json', 'timestamp'])->default('string');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['user_id', 'meta_key'], 'uq_user_meta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_meta');
    }
};
