<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Support Services Rev 2 — Wave 2 Backfill
 * Populates Rev 2 columns from legacy escrow data for existing rows.
 * Idempotent — WHERE clauses prevent double-writes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // bp_jobs — set sensible defaults for existing postings
        DB::table('bp_jobs')
            ->whereNull('default_payment_structure')
            ->update([
                'default_payment_structure'  => 'per_milestone',
                'default_upfront_percentage' => 30,
                'allow_on_completion'        => 0,
            ]);

        // bp_proposals — default to per_milestone + provider_default source
        DB::table('bp_proposals')
            ->whereNull('proposed_payment_structure')
            ->update([
                'proposed_payment_structure'  => 'per_milestone',
                'proposed_upfront_percentage' => 30,
                'terms_source'                => 'provider_default',
            ]);

        // bp_contracts — map funding_mode to payment_structure for legacy rows
        // Only fill rows that don't have payment_structure set yet
        DB::statement("
            UPDATE bp_contracts
               SET payment_structure  = CASE
                                          WHEN funding_mode = 'full_upfront' THEN 'full_upfront'
                                          ELSE 'per_milestone'
                                        END,
                   upfront_percentage = CASE
                                          WHEN funding_mode = 'full_upfront' THEN 100
                                          ELSE 0
                                        END,
                   upfront_cents      = CASE
                                          WHEN funding_mode = 'full_upfront' THEN total_value_cents
                                          ELSE 0
                                        END,
                   remaining_cents    = CASE
                                          WHEN funding_mode = 'full_upfront' THEN 0
                                          ELSE total_value_cents
                                        END,
                   paid_cents         = COALESCE(escrow_released_cents, 0),
                   terms_source       = 'provider_default',
                   terms_agreed_at    = COALESCE(fully_executed_at, created_at)
             WHERE payment_structure IS NULL
        ");

        // bp_milestones — copy legacy escrow columns to direct-charge columns for released milestones
        DB::statement("
            UPDATE bp_milestones
               SET payment_intent_id = escrow_intent_id,
                   paid_at           = COALESCE(released_at, paid_at),
                   paid_cents        = COALESCE(released_cents, 0)
             WHERE status IN ('released', 'paid')
               AND payment_intent_id IS NULL
               AND escrow_intent_id IS NOT NULL
        ");

        // Snapshot bp_contract_terms for all fully-executed contracts that don't have one yet
        DB::statement("
            INSERT INTO bp_contract_terms
                (id, contract_id, payment_structure, upfront_percentage,
                 upfront_cents, remaining_cents, total_value_cents,
                 terms_note, terms_source, snapshotted_at)
            SELECT
                CONCAT('bct_', LOWER(SUBSTRING(MD5(id), 1, 12))),
                id,
                payment_structure,
                COALESCE(upfront_percentage, 0),
                COALESCE(upfront_cents, 0),
                COALESCE(remaining_cents, total_value_cents),
                total_value_cents,
                terms_note,
                COALESCE(terms_source, 'provider_default'),
                COALESCE(fully_executed_at, created_at)
              FROM bp_contracts
             WHERE fully_executed_at IS NOT NULL
               AND id NOT IN (SELECT contract_id FROM bp_contract_terms)
               AND payment_structure IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Non-destructive — no rollback for data migrations
    }
};
