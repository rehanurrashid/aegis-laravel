<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CS Engagement Contract fields on plan_stewards.
 *
 * When a Practitioner designates a Business CS with a pre-agreed fee per
 * activation, these columns store the commercial terms:
 *   - fee_cents:  agreed compensation per incident activation
 *   - payment_terms: when the invoice is due (on_close = immediately, net_30/60 for institutional)
 *   - auto_charge: if 1, the Provider's default card is charged automatically on incident close
 *   - engagement_document_id: FK to the countersigned engagement agreement in continuity_documents
 *
 * Invited CS (unpaid family/colleague) defaults to fee_cents=0 and behaves
 * exactly as today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_stewards', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_stewards', 'fee_cents')) {
                $table->integer('fee_cents')->default(0)->after('responsibilities');
            }
            if (!Schema::hasColumn('plan_stewards', 'payment_terms')) {
                $table->enum('payment_terms', ['on_close', 'net_30', 'net_60'])->default('on_close')->after('fee_cents');
            }
            if (!Schema::hasColumn('plan_stewards', 'auto_charge')) {
                $table->boolean('auto_charge')->default(false)->after('payment_terms');
            }
            if (!Schema::hasColumn('plan_stewards', 'engagement_document_id')) {
                $table->char('engagement_document_id', 36)->nullable()->after('auto_charge');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plan_stewards', function (Blueprint $table) {
            foreach (['engagement_document_id', 'auto_charge', 'payment_terms', 'fee_cents'] as $col) {
                if (Schema::hasColumn('plan_stewards', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
