<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Wave 5 — extend disputes.subject_type to support bp_milestone.
 *
 * bp_milestone disputes use EscrowService for the money side-effect:
 *   - refund_full / refund_partial → EscrowService::refundMilestone()
 *   - pay_full (release) → EscrowService::releaseMilestone()
 *   - split → EscrowService::splitResolution()
 *
 * Also adds 'milestone_dispute' to the bp_milestones.status enum
 * (already added as 'disputed' in Wave 1 migration — this is additive).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE disputes
            MODIFY COLUMN subject_type ENUM(
                'cs_invoice',
                'bp_invoice',
                'bp_payout',
                'bp_milestone',
                'session',
                'engagement'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE disputes
            MODIFY COLUMN subject_type ENUM(
                'cs_invoice',
                'bp_invoice',
                'bp_payout',
                'session',
                'engagement'
            ) NOT NULL
        ");
    }
};
