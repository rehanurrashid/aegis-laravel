<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 1 — expand bp_contracts for escrow + real dual-signature.
 *
 * Adds:
 *   - funding_mode           ENUM full_upfront|per_milestone
 *   - escrow_funded_cents    running total charged into Aegis platform balance
 *   - escrow_released_cents  running total transferred to BP
 *   - escrow_refunded_cents  running total refunded to provider
 *   - transfer_group         Stripe transfer_group correlation ID
 *   - terms                  plain-text contract body generated at hire time
 *   - practitioner_signed_at / practitioner_signature_name
 *   - bp_signed_at / bp_signature_name
 *   - fully_executed_at
 *
 * Expands status enum to include:
 *   pending_signature | pending_funding | disputed
 *   (draft | active | completed | cancelled remain)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Expand status enum first (MySQL requires full column redef)
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
            // Escrow tracking
            if (!Schema::hasColumn('bp_contracts', 'funding_mode')) {
                $table->enum('funding_mode', ['full_upfront', 'per_milestone'])
                      ->default('per_milestone')
                      ->after('payment_type');
            }
            if (!Schema::hasColumn('bp_contracts', 'escrow_funded_cents')) {
                $table->integer('escrow_funded_cents')->default(0)->after('funding_mode');
            }
            if (!Schema::hasColumn('bp_contracts', 'escrow_released_cents')) {
                $table->integer('escrow_released_cents')->default(0)->after('escrow_funded_cents');
            }
            if (!Schema::hasColumn('bp_contracts', 'escrow_refunded_cents')) {
                $table->integer('escrow_refunded_cents')->default(0)->after('escrow_released_cents');
            }
            if (!Schema::hasColumn('bp_contracts', 'transfer_group')) {
                $table->string('transfer_group', 64)->nullable()->after('escrow_refunded_cents');
            }
            if (!Schema::hasColumn('bp_contracts', 'terms')) {
                $table->text('terms')->nullable()->after('transfer_group');
            }

            // Real dual-signature (ContractService::sign already references these — add them)
            if (!Schema::hasColumn('bp_contracts', 'practitioner_signed_at')) {
                $table->timestamp('practitioner_signed_at')->nullable()->after('terms');
            }
            if (!Schema::hasColumn('bp_contracts', 'practitioner_signature_name')) {
                $table->string('practitioner_signature_name', 120)->nullable()->after('practitioner_signed_at');
            }
            if (!Schema::hasColumn('bp_contracts', 'bp_signed_at')) {
                $table->timestamp('bp_signed_at')->nullable()->after('practitioner_signature_name');
            }
            if (!Schema::hasColumn('bp_contracts', 'bp_signature_name')) {
                $table->string('bp_signature_name', 120)->nullable()->after('bp_signed_at');
            }
            if (!Schema::hasColumn('bp_contracts', 'fully_executed_at')) {
                $table->timestamp('fully_executed_at')->nullable()->after('bp_signature_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bp_contracts', function (Blueprint $table) {
            $cols = [
                'funding_mode', 'escrow_funded_cents', 'escrow_released_cents',
                'escrow_refunded_cents', 'transfer_group', 'terms',
                'practitioner_signed_at', 'practitioner_signature_name',
                'bp_signed_at', 'bp_signature_name', 'fully_executed_at',
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('bp_contracts', $c)) {
                    $table->dropColumn($c);
                }
            }
        });

        DB::statement("
            ALTER TABLE bp_contracts
            MODIFY COLUMN status ENUM('draft','active','completed','cancelled')
            NOT NULL DEFAULT 'draft'
        ");
    }
};
