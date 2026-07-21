<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Support Services Rev 2 — Wave 1
 * Adds committed payment term columns + direct-charge tracking to bp_contracts.
 * Expands status enum to add 'pending_signature' (kept pending_funding for legacy).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Expand status enum — idempotent via MODIFY; preserves existing values
        DB::statement("
            ALTER TABLE bp_contracts
            MODIFY COLUMN status ENUM(
                'draft',
                'pending_signature',
                'pending_funding',
                'active',
                'completed',
                'cancelled',
                'disputed'
            ) NOT NULL DEFAULT 'draft'
        ");

        Schema::table('bp_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('bp_contracts', 'payment_structure')) {
                $table->enum('payment_structure', [
                    'full_upfront', 'split', 'per_milestone', 'on_completion',
                ])->nullable()->after('funding_mode');
            }
            if (!Schema::hasColumn('bp_contracts', 'upfront_percentage')) {
                $table->tinyInteger('upfront_percentage')->unsigned()->default(0)
                      ->after('payment_structure');
            }
            if (!Schema::hasColumn('bp_contracts', 'upfront_cents')) {
                $table->unsignedInteger('upfront_cents')->default(0)->after('upfront_percentage');
            }
            if (!Schema::hasColumn('bp_contracts', 'remaining_cents')) {
                $table->unsignedInteger('remaining_cents')->default(0)->after('upfront_cents');
            }
            if (!Schema::hasColumn('bp_contracts', 'terms_note')) {
                $table->text('terms_note')->nullable()->after('remaining_cents');
            }
            if (!Schema::hasColumn('bp_contracts', 'terms_source')) {
                $table->enum('terms_source', ['provider_default', 'bp_proposed', 'provider_countered'])
                      ->nullable()->after('terms_note');
            }
            if (!Schema::hasColumn('bp_contracts', 'terms_agreed_at')) {
                $table->timestamp('terms_agreed_at')->nullable()->after('terms_source');
            }
            if (!Schema::hasColumn('bp_contracts', 'paid_cents')) {
                $table->unsignedInteger('paid_cents')->default(0)->after('terms_agreed_at');
            }
            if (!Schema::hasColumn('bp_contracts', 'upfront_charge_intent_id')) {
                $table->string('upfront_charge_intent_id', 64)->nullable()->after('paid_cents');
            }
            if (!Schema::hasColumn('bp_contracts', 'upfront_charged_at')) {
                $table->timestamp('upfront_charged_at')->nullable()->after('upfront_charge_intent_id');
            }
            if (!Schema::hasColumn('bp_contracts', 'completion_charge_intent_id')) {
                $table->string('completion_charge_intent_id', 64)->nullable()->after('upfront_charged_at');
            }
            if (!Schema::hasColumn('bp_contracts', 'completion_charged_at')) {
                $table->timestamp('completion_charged_at')->nullable()->after('completion_charge_intent_id');
            }
            if (!Schema::hasColumn('bp_contracts', 'payment_failed_at')) {
                $table->timestamp('payment_failed_at')->nullable()->after('completion_charged_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bp_contracts', function (Blueprint $table) {
            $cols = [
                'payment_structure', 'upfront_percentage', 'upfront_cents',
                'remaining_cents', 'terms_note', 'terms_source', 'terms_agreed_at',
                'paid_cents', 'upfront_charge_intent_id', 'upfront_charged_at',
                'completion_charge_intent_id', 'completion_charged_at', 'payment_failed_at',
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('bp_contracts', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
