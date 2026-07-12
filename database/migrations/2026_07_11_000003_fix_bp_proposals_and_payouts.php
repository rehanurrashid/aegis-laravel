<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 1 — fix bp_proposals field mismatches + add refund tracking to bp_payouts.
 *
 * bp_proposals:
 *   - timeline_days   (FormRequest validates this, was sent as estimated_weeks — fixed in Vue)
 *   - portfolio_url   (SubmitProposalRequest supports this, was missing from schema)
 *
 * bp_payouts:
 *   - refunded_at
 *   - refunded_cents
 *   - refund_stripe_id
 *   - idempotency_key   prevent double-charges
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bp_proposals', function (Blueprint $table) {
            if (!Schema::hasColumn('bp_proposals', 'timeline_days')) {
                $table->unsignedSmallInteger('timeline_days')->nullable()->after('proposed_rate_cents');
            }
            if (!Schema::hasColumn('bp_proposals', 'portfolio_url')) {
                $table->string('portfolio_url', 500)->nullable()->after('timeline_days');
            }
        });

        Schema::table('bp_payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('bp_payouts', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('released_at');
            }
            if (!Schema::hasColumn('bp_payouts', 'refunded_cents')) {
                $table->integer('refunded_cents')->default(0)->after('refunded_at');
            }
            if (!Schema::hasColumn('bp_payouts', 'refund_stripe_id')) {
                $table->string('refund_stripe_id', 64)->nullable()->after('refunded_cents');
            }
            if (!Schema::hasColumn('bp_payouts', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('refund_stripe_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bp_proposals', function (Blueprint $table) {
            foreach (['timeline_days', 'portfolio_url'] as $c) {
                if (Schema::hasColumn('bp_proposals', $c)) $table->dropColumn($c);
            }
        });

        Schema::table('bp_payouts', function (Blueprint $table) {
            foreach (['refunded_at', 'refunded_cents', 'refund_stripe_id', 'idempotency_key'] as $c) {
                if (Schema::hasColumn('bp_payouts', $c)) $table->dropColumn($c);
            }
        });
    }
};
