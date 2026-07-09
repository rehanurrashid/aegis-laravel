<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('disputer_id', 36)->index();
            $table->char('respondent_id', 36)->index();
            // Polymorphic subject: 'cs_invoice' | 'bp_invoice' | 'bp_payout' | 'session' | 'engagement'
            $table->string('subject_type', 32);
            $table->char('subject_id', 36);
            $table->enum('reason', [
                'non_delivery', 'quality_issue', 'unauthorized_charge',
                'duplicate_charge', 'wrong_amount', 'other',
            ])->index();
            $table->integer('amount_disputed_cents')->default(0);
            $table->text('description');
            $table->enum('status', [
                'open', 'awaiting_response', 'under_review', 'resolved', 'closed_no_action',
            ])->default('open')->index();
            $table->enum('resolution', [
                'refund_full', 'refund_partial', 'pay_full', 'pay_partial', 'no_action', 'stripe_dispute_escalated',
            ])->nullable();
            $table->integer('resolution_cents')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->char('resolved_by', 36)->nullable()->index();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('respondent_replied_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['subject_type', 'subject_id'], 'ix_disp_subject');
            $table->index(['status', 'opened_at'], 'ix_disp_status_opened');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
