<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 1 — expand bp_milestones for escrow.
 *
 * Adds:
 *   - escrow_intent_id    Stripe PaymentIntent (funds in Aegis balance)
 *   - escrow_charge_id    Stripe Charge ID after confirm
 *   - transfer_id         Stripe Transfer to BP on release
 *   - funded_at / funded_cents
 *   - released_at / released_cents
 *   - refunded_at / refunded_cents / refund_stripe_id
 *   - auto_release_at     countdown deadline; if provider no-review → auto-approve
 *   - revision_count      how many revision rounds completed
 *   - rejection_reason    why provider rejected (was missing, only in activity log)
 *   - revision_notes      provider feedback on revision request
 *
 * Expands status enum:
 *   pending_funding | funded | in_progress | submitted |
 *   revision_requested | approved | released | disputed | refunded
 *   (pending remains as legacy alias for funded/in_progress)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE bp_milestones
            MODIFY COLUMN status ENUM(
                'pending',
                'pending_funding',
                'funded',
                'in_progress',
                'submitted',
                'revision_requested',
                'approved',
                'released',
                'disputed',
                'refunded',
                'paid'
            ) NOT NULL DEFAULT 'pending'
        ");

        Schema::table('bp_milestones', function (Blueprint $table) {
            if (!Schema::hasColumn('bp_milestones', 'escrow_intent_id')) {
                $table->string('escrow_intent_id', 64)->nullable()->after('sort_order');
            }
            if (!Schema::hasColumn('bp_milestones', 'escrow_charge_id')) {
                $table->string('escrow_charge_id', 64)->nullable()->after('escrow_intent_id');
            }
            if (!Schema::hasColumn('bp_milestones', 'transfer_id')) {
                $table->string('transfer_id', 64)->nullable()->after('escrow_charge_id');
            }
            if (!Schema::hasColumn('bp_milestones', 'funded_at')) {
                $table->timestamp('funded_at')->nullable()->after('transfer_id');
            }
            if (!Schema::hasColumn('bp_milestones', 'funded_cents')) {
                $table->integer('funded_cents')->default(0)->after('funded_at');
            }
            if (!Schema::hasColumn('bp_milestones', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('funded_cents');
            }
            if (!Schema::hasColumn('bp_milestones', 'released_cents')) {
                $table->integer('released_cents')->default(0)->after('released_at');
            }
            if (!Schema::hasColumn('bp_milestones', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('released_cents');
            }
            if (!Schema::hasColumn('bp_milestones', 'refunded_cents')) {
                $table->integer('refunded_cents')->default(0)->after('refunded_at');
            }
            if (!Schema::hasColumn('bp_milestones', 'refund_stripe_id')) {
                $table->string('refund_stripe_id', 64)->nullable()->after('refunded_cents');
            }
            if (!Schema::hasColumn('bp_milestones', 'auto_release_at')) {
                $table->timestamp('auto_release_at')->nullable()->after('refund_stripe_id');
            }
            if (!Schema::hasColumn('bp_milestones', 'revision_count')) {
                $table->tinyInteger('revision_count')->default(0)->after('auto_release_at');
            }
            if (!Schema::hasColumn('bp_milestones', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('revision_count');
            }
            if (!Schema::hasColumn('bp_milestones', 'revision_notes')) {
                $table->text('revision_notes')->nullable()->after('rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bp_milestones', function (Blueprint $table) {
            $cols = [
                'escrow_intent_id', 'escrow_charge_id', 'transfer_id',
                'funded_at', 'funded_cents', 'released_at', 'released_cents',
                'refunded_at', 'refunded_cents', 'refund_stripe_id',
                'auto_release_at', 'revision_count', 'rejection_reason', 'revision_notes',
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('bp_milestones', $c)) {
                    $table->dropColumn($c);
                }
            }
        });

        DB::statement("
            ALTER TABLE bp_milestones
            MODIFY COLUMN status ENUM('pending','submitted','approved','rejected','paid')
            NOT NULL DEFAULT 'pending'
        ");
    }
};
