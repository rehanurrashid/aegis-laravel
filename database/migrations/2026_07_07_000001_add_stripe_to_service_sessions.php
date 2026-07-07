<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('service_sessions', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id', 64)->nullable()->after('amount_cents');
            }
            if (!Schema::hasColumn('service_sessions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_sessions', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('service_sessions', 'stripe_payment_intent_id') ? 'stripe_payment_intent_id' : null,
                Schema::hasColumn('service_sessions', 'paid_at') ? 'paid_at' : null,
            ]));
        });
    }
};
