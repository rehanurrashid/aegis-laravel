<?php
// Domain 1 — mfa_tokens — UC-PRV-002,214; EMAIL T5/T6

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mfa_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->index();
            $table->text('secret');
            $table->json('recovery_codes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('user_id', 'uq_mfa_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_tokens');
    }
};
