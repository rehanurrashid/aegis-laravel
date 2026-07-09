<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds Stripe reference columns to cs_invoices so
 * Provider/FinancesController::payCSInvoice can persist the
 * PaymentIntent + transfer identifiers returned by
 * PayoutService::chargeProviderToCs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cs_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('cs_invoices', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id', 64)->nullable()->after('currency');
            }
            if (!Schema::hasColumn('cs_invoices', 'stripe_transfer_id')) {
                $table->string('stripe_transfer_id', 64)->nullable()->after('stripe_payment_intent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cs_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('cs_invoices', 'stripe_transfer_id')) {
                $table->dropColumn('stripe_transfer_id');
            }
            if (Schema::hasColumn('cs_invoices', 'stripe_payment_intent_id')) {
                $table->dropColumn('stripe_payment_intent_id');
            }
        });
    }
};
