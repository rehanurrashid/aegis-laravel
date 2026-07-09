<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extend cs_invoices.status and bp_invoices.status ENUMs to include
 * 'disputed'. When a Dispute is opened against a not-yet-paid invoice,
 * the invoice moves to `disputed` and cannot be paid until resolved.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `cs_invoices` MODIFY COLUMN `status` ENUM(
            'draft','sent','paid','overdue','void','disputed'
        ) NOT NULL DEFAULT 'draft'");

        DB::statement("ALTER TABLE `bp_invoices` MODIFY COLUMN `status` ENUM(
            'draft','sent','paid','overdue','void','disputed'
        ) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Move any 'disputed' rows back to 'sent' before shrinking the ENUM.
        DB::statement("UPDATE `cs_invoices` SET `status` = 'sent' WHERE `status` = 'disputed'");
        DB::statement("UPDATE `bp_invoices` SET `status` = 'sent' WHERE `status` = 'disputed'");

        DB::statement("ALTER TABLE `cs_invoices` MODIFY COLUMN `status` ENUM(
            'draft','sent','paid','overdue','void'
        ) NOT NULL DEFAULT 'draft'");

        DB::statement("ALTER TABLE `bp_invoices` MODIFY COLUMN `status` ENUM(
            'draft','sent','paid','overdue','void'
        ) NOT NULL DEFAULT 'draft'");
    }
};
