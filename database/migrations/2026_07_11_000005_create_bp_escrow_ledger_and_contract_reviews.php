<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 1 — escrow ledger + post-completion review tables.
 *
 * bp_escrow_ledger:
 *   Immutable double-entry ledger. Every Stripe operation (fund, release, refund,
 *   dispute_hold, split) writes one row. Never updated — only inserted.
 *   Provides a complete audit trail reconcilable against Stripe.
 *
 * bp_contract_reviews:
 *   Post-completion ratings. Each party (provider + BP) submits one review per contract.
 *   Unique constraint on (contract_id, reviewer_id) enforces one review per party.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bp_escrow_ledger')) {
            Schema::create('bp_escrow_ledger', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->char('contract_id', 36)->index();
                $table->char('milestone_id', 36)->nullable()->index();
                $table->enum('kind', [
                    'fund',             // provider → Aegis platform balance
                    'release',          // Aegis balance → BP Connect
                    'refund',           // Aegis balance → provider card
                    'dispute_hold',     // funds frozen in escrow
                    'dispute_release',  // held funds → BP (dispute resolved in BP's favor)
                    'dispute_refund',   // held funds → provider (dispute resolved in provider's favor)
                    'split_release',    // partial → BP
                    'split_refund',     // partial → provider
                ])->index();
                $table->integer('amount_cents');
                $table->char('provider_id', 36)->index();
                $table->char('bp_id', 36)->index();
                $table->string('stripe_object_id', 64)->nullable();   // pi_ | tr_ | re_
                $table->string('stripe_object_type', 20)->nullable(); // payment_intent | transfer | refund
                $table->string('description', 255)->nullable();
                $table->char('actor_id', 36)->nullable();             // who triggered; null = system
                // Immutable — no updated_at
                $table->timestamp('created_at')->useCurrent()->index();

                $table->index(['contract_id', 'created_at'], 'ix_escrow_contract_created');
                $table->index(['provider_id', 'kind'], 'ix_escrow_provider_kind');
                $table->index(['bp_id', 'kind'],       'ix_escrow_bp_kind');
            });
        }

        if (!Schema::hasTable('bp_contract_reviews')) {
            Schema::create('bp_contract_reviews', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->char('contract_id', 36)->index();
                $table->char('reviewer_id', 36)->index();
                $table->char('reviewee_id', 36)->index();
                // Ratings 1-5
                $table->tinyInteger('rating')->default(0);          // overall
                $table->tinyInteger('communication')->default(0);
                $table->tinyInteger('quality')->default(0);
                $table->tinyInteger('timeliness')->default(0);
                $table->text('review_text')->nullable();
                $table->boolean('is_public')->default(true);
                $table->boolean('review_dismissed')->default(false); // reviewer skipped
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                // One review per party per contract
                $table->unique(['contract_id', 'reviewer_id'], 'uq_review_contract_reviewer');

                $table->index(['reviewee_id', 'is_public'], 'ix_review_reviewee_public');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_contract_reviews');
        Schema::dropIfExists('bp_escrow_ledger');
    }
};
