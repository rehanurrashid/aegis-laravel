<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support Services Rev 2 — Wave 1
 * Adds default payment term columns to bp_jobs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bp_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('bp_jobs', 'default_payment_structure')) {
                $table->enum('default_payment_structure', [
                    'full_upfront', 'split', 'per_milestone', 'on_completion',
                ])->default('per_milestone')->after('billing_frequency');
            }
            if (!Schema::hasColumn('bp_jobs', 'default_upfront_percentage')) {
                $table->tinyInteger('default_upfront_percentage')->unsigned()->default(30)
                      ->after('default_payment_structure');
            }
            if (!Schema::hasColumn('bp_jobs', 'default_terms_note')) {
                $table->text('default_terms_note')->nullable()->after('default_upfront_percentage');
            }
            if (!Schema::hasColumn('bp_jobs', 'allow_on_completion')) {
                $table->boolean('allow_on_completion')->default(false)->after('default_terms_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bp_jobs', function (Blueprint $table) {
            $cols = ['default_payment_structure', 'default_upfront_percentage', 'default_terms_note', 'allow_on_completion'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('bp_jobs', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
