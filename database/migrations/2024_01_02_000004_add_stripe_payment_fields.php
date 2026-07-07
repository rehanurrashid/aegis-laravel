<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Stripe customer + payment method fields to users.
 * Add stripe_payment_intent_id to bp_payouts for destination charge tracking.
 * Add provider_id to bp_payouts so we know who initiated the charge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Stripe Customer ID — used as the payer when Provider initiates a charge
            if (!Schema::hasColumn('users', 'stripe_id')) {
                $table->string('stripe_id', 64)->nullable()->after('stripe_account_id');
            }
            // Default saved payment method for the Provider
            if (!Schema::hasColumn('users', 'stripe_payment_method_id')) {
                $table->string('stripe_payment_method_id', 64)->nullable()->after('stripe_id');
            }
        });

        Schema::table('bp_payouts', function (Blueprint $table) {
            // Track the PaymentIntent that funded this payout (destination charge)
            if (!Schema::hasColumn('bp_payouts', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id', 64)->nullable()->after('stripe_payout_id');
            }
            // Which provider initiated this payment
            if (!Schema::hasColumn('bp_payouts', 'provider_id')) {
                $table->char('provider_id', 36)->nullable()->after('bp_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['stripe_id', 'stripe_payment_method_id']);
        });
        Schema::table('bp_payouts', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_intent_id', 'provider_id']);
        });
    }
};
