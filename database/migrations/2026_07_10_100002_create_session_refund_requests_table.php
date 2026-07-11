<?php

// Wave 1 — Clinical Services Overhaul
// Session refund request table: client requests refund → provider reviews →
// approve/deny → if denied, client can escalate to existing dispute system.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_refund_requests', function (Blueprint $table) {
            $table->string('id', 36)->primary();  // 'srr_' + 12-char random

            // ── Foreign keys ──────────────────────────────────────────────────
            $table->string('session_id', 36)->index();
            $table->string('requested_by_id', 36)->index()
                ->comment('The client (inquirer) who paid and is requesting a refund');
            $table->string('provider_id', 36)->index()
                ->comment('The practitioner who received the payment');

            // ── Request details ───────────────────────────────────────────────
            $table->enum('reason', [
                'session_did_not_occur',
                'provider_no_show',
                'quality_issue',
                'duplicate_charge',
                'session_cancelled_by_provider',
                'other',
            ]);
            $table->text('reason_detail')->nullable()
                ->comment('Free-text elaboration; required when reason = other');

            $table->enum('refund_type', [
                'deposit_only',   // only the 30% deposit back
                'balance_only',   // only the 70% balance back
                'full',           // both charges refunded
            ]);

            $table->unsignedInteger('amount_requested_cents')
                ->comment('Total cents the client expects back');

            // ── Lifecycle ─────────────────────────────────────────────────────
            $table->enum('status', [
                'pending_review',         // waiting for provider to respond
                'approved',               // provider approved; Stripe refund issued
                'denied',                 // provider denied; client can escalate
                'auto_approved',          // system auto-approved per platform policy
                'escalated_to_dispute',   // client escalated to formal dispute
            ])->default('pending_review')->index();

            // ── Provider response ─────────────────────────────────────────────
            $table->text('provider_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('provider_deadline_at')->nullable()
                ->comment('Provider must respond by this time; set from DISPUTE_RESPONDENT_REPLY_DAYS env');

            // ── Stripe execution ──────────────────────────────────────────────
            $table->string('stripe_refund_id', 64)->nullable()
                ->comment('Stripe Refund ID once issued; supports both deposit and balance refunds');
            $table->string('stripe_refund_id_balance', 64)->nullable()
                ->comment('Second Stripe Refund ID when full refund requires two separate API calls');
            $table->unsignedInteger('refunded_cents')->default(0)
                ->comment('Actual amount refunded after Stripe confirms');

            // ── Dispute escalation ────────────────────────────────────────────
            $table->string('escalated_dispute_id', 36)->nullable()
                ->comment('FK to disputes.id if client escalated');

            // ── Timestamps ────────────────────────────────────────────────────
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // ── Indexes ───────────────────────────────────────────────────────
            $table->index(['session_id', 'status'], 'ix_srr_session_status');
            $table->index(['provider_id', 'status'], 'ix_srr_provider_status');
            $table->index(['requested_by_id', 'status'], 'ix_srr_requester_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_refund_requests');
    }
};
