<?php
// Backfill: proposals that have a contract but were left with status != 'accepted'
// due to the idempotency guard bug in ProposalService::accept().

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Find proposals linked to a contract but whose status/pipeline_stage
        // was never updated because the guard returned before the update() call.
        DB::statement("
            UPDATE bp_proposals p
            INNER JOIN bp_contracts c ON c.proposal_id = p.id
            SET p.status = 'accepted',
                p.pipeline_stage = 'hired',
                p.responded_at = COALESCE(p.responded_at, c.signed_at, NOW())
            WHERE p.status != 'accepted'
               OR p.pipeline_stage != 'hired'
        ");
    }

    public function down(): void
    {
        // Irreversible backfill — cannot restore which proposals were stale vs correct
    }
};
