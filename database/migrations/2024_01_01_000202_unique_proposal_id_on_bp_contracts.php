<?php
// Hard guarantee against the duplicate-hire bug: one contract per proposal.
// ProposalService::accept() now also guards this at the application level.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The bug already created duplicate contracts in some environments —
        // clean those up first (keep the earliest contract per proposal) so
        // the unique index can be added safely.
        $duplicateIds = \Illuminate\Support\Facades\DB::table('bp_contracts')
            ->select('id')
            ->whereIn('id', function ($q) {
                $q->select('id')
                    ->from('bp_contracts as bc')
                    ->whereRaw('bc.id <> (
                        select min(bc2.id) from bp_contracts as bc2
                        where bc2.proposal_id = bc.proposal_id
                    )');
            })
            ->pluck('id');

        if ($duplicateIds->isNotEmpty()) {
            \Illuminate\Support\Facades\DB::table('bp_contracts')->whereIn('id', $duplicateIds)->delete();
        }

        Schema::table('bp_contracts', function (Blueprint $table) {
            $table->unique('proposal_id', 'bp_contracts_proposal_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bp_contracts', function (Blueprint $table) {
            $table->dropUnique('bp_contracts_proposal_id_unique');
        });
    }
};
