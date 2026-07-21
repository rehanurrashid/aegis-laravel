<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Support Services Rev 2 — Wave 1
 * Adds direct-charge tracking columns to bp_milestones.
 * Expands status enum to add Rev 2 lifecycle states.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Expand milestone status enum for Rev 2 lifecycle
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
                'paid',
                'prepaid',
                'payment_failed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");

        Schema::table('bp_milestones', function (Blueprint $table) {
            if (!Schema::hasColumn('bp_milestones', 'payment_intent_id')) {
                $table->string('payment_intent_id', 64)->nullable()->after('revision_notes');
            }
            if (!Schema::hasColumn('bp_milestones', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_intent_id');
            }
            if (!Schema::hasColumn('bp_milestones', 'paid_cents')) {
                $table->unsignedInteger('paid_cents')->default(0)->after('paid_at');
            }
            if (!Schema::hasColumn('bp_milestones', 'auto_approve_at')) {
                $table->timestamp('auto_approve_at')->nullable()->after('paid_cents');
            }
            if (!Schema::hasColumn('bp_milestones', 'payment_failed_at')) {
                $table->timestamp('payment_failed_at')->nullable()->after('auto_approve_at');
            }
            if (!Schema::hasColumn('bp_milestones', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('payment_failed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bp_milestones', function (Blueprint $table) {
            $cols = ['payment_intent_id', 'paid_at', 'paid_cents', 'auto_approve_at', 'payment_failed_at', 'cancelled_at'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('bp_milestones', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
