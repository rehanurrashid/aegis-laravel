<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bp_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('bp_contracts', 'payment_type')) {
                $table->enum('payment_type', ['milestone', 'one_time'])->default('one_time')->after('total_value_cents');
            }
            if (!Schema::hasColumn('bp_contracts', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bp_contracts', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'ended_at']);
        });
    }
};
