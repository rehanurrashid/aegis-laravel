<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support Services Rev 2 — Wave 1
 * Adds proposed payment term columns to bp_proposals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bp_proposals', function (Blueprint $table) {
            if (!Schema::hasColumn('bp_proposals', 'proposed_payment_structure')) {
                $table->enum('proposed_payment_structure', [
                    'full_upfront', 'split', 'per_milestone', 'on_completion',
                ])->default('per_milestone')->after('decline_reason');
            }
            if (!Schema::hasColumn('bp_proposals', 'proposed_upfront_percentage')) {
                $table->tinyInteger('proposed_upfront_percentage')->unsigned()->default(30)
                      ->after('proposed_payment_structure');
            }
            if (!Schema::hasColumn('bp_proposals', 'proposed_terms_note')) {
                $table->text('proposed_terms_note')->nullable()->after('proposed_upfront_percentage');
            }
            if (!Schema::hasColumn('bp_proposals', 'terms_source')) {
                $table->enum('terms_source', ['provider_default', 'bp_proposed'])
                      ->default('provider_default')->after('proposed_terms_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bp_proposals', function (Blueprint $table) {
            $cols = ['proposed_payment_structure', 'proposed_upfront_percentage', 'proposed_terms_note', 'terms_source'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('bp_proposals', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
