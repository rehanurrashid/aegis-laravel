<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practitioner_payments', function (Blueprint $table) {
            $table->string('stripe_transfer_id', 64)->nullable()->after('stripe_charge_id');
        });
    }

    public function down(): void
    {
        Schema::table('practitioner_payments', function (Blueprint $table) {
            $table->dropColumn('stripe_transfer_id');
        });
    }
};
