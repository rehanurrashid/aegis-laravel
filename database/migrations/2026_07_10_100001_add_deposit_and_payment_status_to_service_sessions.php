<?php

// Wave 1 — Clinical Services Overhaul
// Adds deposit/balance columns, negotiated pricing, and payment_status lifecycle
// to service_sessions. Keeps amount_cents as the agreed total (negotiated or original).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_sessions', function (Blueprint $table) {
            // ── Pricing breakdown ─────────────────────────────────────────────
            // original_amount_cents: locked from service.price_cents at request time
            if (!Schema::hasColumn('service_sessions', 'original_amount_cents')) {
                $table->unsignedInteger('original_amount_cents')->default(0)
                    ->after('amount_cents')
                    ->comment('Service listing price at time of request');
            }
            // negotiated_amount_cents: set by provider at accept time; overrides amount_cents
            if (!Schema::hasColumn('service_sessions', 'negotiated_amount_cents')) {
                $table->unsignedInteger('negotiated_amount_cents')->nullable()
                    ->after('original_amount_cents')
                    ->comment('Provider-offered price if different from listing; null = use listing price');
            }

            // ── Deposit (30%) ─────────────────────────────────────────────────
            if (!Schema::hasColumn('service_sessions', 'deposit_cents')) {
                $table->unsignedInteger('deposit_cents')->default(0)
                    ->after('negotiated_amount_cents');
            }
            if (!Schema::hasColumn('service_sessions', 'deposit_charge_id')) {
                $table->string('deposit_charge_id', 64)->nullable()
                    ->after('deposit_cents')
                    ->comment('Stripe PaymentIntent ID for the 30% deposit charge');
            }
            if (!Schema::hasColumn('service_sessions', 'deposit_paid_at')) {
                $table->timestamp('deposit_paid_at')->nullable()
                    ->after('deposit_charge_id');
            }

            // ── Balance (remaining 70%) ───────────────────────────────────────
            if (!Schema::hasColumn('service_sessions', 'balance_cents')) {
                $table->unsignedInteger('balance_cents')->default(0)
                    ->after('deposit_paid_at');
            }
            if (!Schema::hasColumn('service_sessions', 'balance_charge_id')) {
                $table->string('balance_charge_id', 64)->nullable()
                    ->after('balance_cents')
                    ->comment('Stripe PaymentIntent ID for the 70% balance charge');
            }
            if (!Schema::hasColumn('service_sessions', 'balance_paid_at')) {
                $table->timestamp('balance_paid_at')->nullable()
                    ->after('balance_charge_id');
            }

            // ── Refund tracking ───────────────────────────────────────────────
            if (!Schema::hasColumn('service_sessions', 'total_refunded_cents')) {
                $table->unsignedInteger('total_refunded_cents')->default(0)
                    ->after('balance_paid_at');
            }

            // ── Payment lifecycle status ──────────────────────────────────────
            // Separate from session status (scheduled/completed/cancelled/no_show)
            // Tracks money movement independently of session lifecycle
            if (!Schema::hasColumn('service_sessions', 'payment_status')) {
                $table->enum('payment_status', [
                    'unpaid',             // accepted, no money moved yet
                    'deposit_paid',       // 30% charged, awaiting session completion
                    'paid',               // full 100% received (both charges complete)
                    'refunded',           // 100% refunded back to client
                    'partially_refunded', // some portion refunded
                ])->default('unpaid')
                    ->after('total_refunded_cents')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_sessions', function (Blueprint $table) {
            $columns = [
                'original_amount_cents',
                'negotiated_amount_cents',
                'deposit_cents',
                'deposit_charge_id',
                'deposit_paid_at',
                'balance_cents',
                'balance_charge_id',
                'balance_paid_at',
                'total_refunded_cents',
                'payment_status',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('service_sessions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
