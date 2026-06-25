<?php
// Domain 4 — vault_items — UC-PRV-070..075,198..203; UC-XP-023

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('practitioner_id', 36)->index();
            $table->enum('zone', ['credentials', 'roster', 'documents', 'instructions'])->index();
            $table->string('category', 64)->nullable();
            $table->string('title', 191);
            $table->string('sub_label', 255)->nullable();
            $table->enum('status', ['vault_only', 'active', 'priority'])->default('vault_only')->index();
            $table->string('credential_username', 191)->nullable();
            $table->text('credential_password_enc')->nullable();
            $table->string('credential_url', 255)->nullable();
            $table->string('client_name', 191)->nullable();
            $table->integer('client_priority')->nullable();
            $table->string('file_ref', 255)->nullable();
            $table->json('access_grant')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['practitioner_id', 'zone'], 'ix_vault_pract_zone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_items');
    }
};
