<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bp_milestones', function (Blueprint $table) {
            if (!Schema::hasColumn('bp_milestones', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('bp_milestones', 'payout_id')) {
                $table->char('payout_id', 36)->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bp_milestones', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'payout_id']);
        });
    }
};
