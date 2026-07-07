<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practitioner_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('practitioner_payments', 'session_id')) {
                $table->string('session_id', 36)->nullable()->after('practitioner_id')->index();
            }
            if (!Schema::hasColumn('practitioner_payments', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id', 64)->nullable()->after('stripe_charge_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('practitioner_payments', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('practitioner_payments', 'session_id') ? 'session_id' : null,
                Schema::hasColumn('practitioner_payments', 'stripe_payment_intent_id') ? 'stripe_payment_intent_id' : null,
            ]));
        });
    }
};
