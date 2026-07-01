<?php
// Fixes a Section-21 contract bug: ContractService::cancel() writes
// 'cancel_reason' but the column never existed on bp_contracts.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bp_contracts', function (Blueprint $table) {
            $table->text('cancel_reason')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('bp_contracts', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
    }
};
