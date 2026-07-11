<?php

// Wave 1 — Clinical Services Overhaul
// Expands practitioner_payments.kind ENUM to support split deposit/balance/refund
// tracking for clinical sessions. Each charge creates a separate payment record
// so the ledger is fully auditable.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `practitioner_payments`
            MODIFY COLUMN `kind`
            ENUM(
                'subscription',
                'maat_addon',
                'cs_fee',
                'bp_invoice',
                'refund',
                'service_session',
                'service_session_deposit',
                'service_session_balance',
                'service_session_refund'
            )
            NOT NULL DEFAULT 'subscription'
        ");
    }

    public function down(): void
    {
        // Remap new kinds back to base kind before shrinking ENUM
        DB::statement("
            UPDATE `practitioner_payments`
            SET `kind` = 'service_session'
            WHERE `kind` IN (
                'service_session_deposit',
                'service_session_balance',
                'service_session_refund'
            )
        ");

        DB::statement("
            ALTER TABLE `practitioner_payments`
            MODIFY COLUMN `kind`
            ENUM(
                'subscription',
                'maat_addon',
                'cs_fee',
                'bp_invoice',
                'refund',
                'service_session'
            )
            NOT NULL DEFAULT 'subscription'
        ");
    }
};
