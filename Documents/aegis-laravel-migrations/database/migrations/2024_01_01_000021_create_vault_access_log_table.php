<?php
// Domain 4 — vault_access_log — UC-PRV-075,201..203; UC-XP-023

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_access_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('vault_item_id', 36)->nullable()->index();
            $table->char('practitioner_id', 36)->index();
            $table->char('actor_id', 36)->index();
            $table->enum('access_type', ['reveal', 'download', 'export', 'share', 'view'])->index();
            $table->char('recipient_id', 36)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['practitioner_id', 'created_at'], 'ix_vaultlog_pract_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_access_log');
    }
};
