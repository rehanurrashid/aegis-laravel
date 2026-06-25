<?php
// Domain 4 — vault_item_meta — UC-PRV-070,072

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_item_meta', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('vault_item_id', 36)->index();
            $table->string('meta_key', 191);
            $table->longText('meta_value')->nullable();
            $table->enum('meta_type', ['string', 'int', 'boolean', 'json', 'timestamp'])->default('string');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['vault_item_id', 'meta_key'], 'uq_vaultitem_meta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_item_meta');
    }
};
