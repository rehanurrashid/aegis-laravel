<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support Services Rev 2 — Wave 1
 * Immutable snapshot of committed payment terms at the moment both parties sign.
 * No timestamps columns (snapshotted_at is the single write-once timestamp).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_contract_terms', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('contract_id', 36)->unique()->index();
            $table->enum('payment_structure', ['full_upfront', 'split', 'per_milestone', 'on_completion']);
            $table->tinyInteger('upfront_percentage')->unsigned()->default(0);
            $table->unsignedInteger('upfront_cents')->default(0);
            $table->unsignedInteger('remaining_cents')->default(0);
            $table->unsignedInteger('total_value_cents')->default(0);
            $table->text('terms_note')->nullable();
            $table->enum('terms_source', ['provider_default', 'bp_proposed', 'provider_countered'])
                  ->default('provider_default');
            $table->timestamp('snapshotted_at');

            $table->foreign('contract_id')->references('id')->on('bp_contracts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_contract_terms');
    }
};
