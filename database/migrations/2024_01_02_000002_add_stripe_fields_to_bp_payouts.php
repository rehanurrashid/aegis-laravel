<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bp_payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('bp_payouts', 'contract_id')) {
                $table->char('contract_id', 36)->nullable()->after('bp_id');
            }
            if (!Schema::hasColumn('bp_payouts', 'milestone_id')) {
                $table->char('milestone_id', 36)->nullable()->after('contract_id');
            }
            if (!Schema::hasColumn('bp_payouts', 'stripe_transfer_id')) {
                $table->string('stripe_transfer_id', 64)->nullable()->after('stripe_payout_id');
            }
            if (!Schema::hasColumn('bp_payouts', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bp_payouts', function (Blueprint $table) {
            $table->dropColumn(['contract_id', 'milestone_id', 'stripe_transfer_id', 'released_at']);
        });
    }
};
