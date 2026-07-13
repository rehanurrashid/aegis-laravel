<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Widen status enum to include portal-mapped values.
 * 2. Add signature columns used by DocumentService::sign/countersign.
 * 3. Add body column used by DocumentService::create.
 * 4. Add party_b_id + related wizard fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: widen enum via raw SQL (Blueprint->enum cannot modify safely cross-DB)
        DB::statement("ALTER TABLE continuity_documents MODIFY COLUMN status ENUM(
            'draft',
            'pending_sign',
            'countersign',
            'countersign_pending',
            'active',
            'fully_executed',
            'expiring',
            'expired',
            'release_pending',
            'archived',
            'terminated'
        ) NOT NULL DEFAULT 'draft'");

        Schema::table('continuity_documents', function (Blueprint $table) {
            // Body / full text of document
            if (!Schema::hasColumn('continuity_documents', 'body')) {
                $table->longText('body')->nullable()->after('doc_type');
            }
            // Signature columns (Provider signs first)
            if (!Schema::hasColumn('continuity_documents', 'signed_by_id')) {
                $table->char('signed_by_id', 36)->nullable()->after('holder_steward_id');
            }
            if (!Schema::hasColumn('continuity_documents', 'signature_name')) {
                $table->string('signature_name', 191)->nullable()->after('signed_by_id');
            }
            if (!Schema::hasColumn('continuity_documents', 'signature_ip')) {
                $table->string('signature_ip', 45)->nullable()->after('signature_name');
            }
            if (!Schema::hasColumn('continuity_documents', 'signed_at')) {
                $table->timestamp('signed_at')->nullable()->after('signature_ip');
            }
            // Countersignature columns (CS countersigns)
            if (!Schema::hasColumn('continuity_documents', 'countersigned_by_id')) {
                $table->char('countersigned_by_id', 36)->nullable()->after('signed_at');
            }
            if (!Schema::hasColumn('continuity_documents', 'countersignature_name')) {
                $table->string('countersignature_name', 191)->nullable()->after('countersigned_by_id');
            }
            if (!Schema::hasColumn('continuity_documents', 'countersignature_ip')) {
                $table->string('countersignature_ip', 45)->nullable()->after('countersignature_name');
            }
            if (!Schema::hasColumn('continuity_documents', 'countersigned_at')) {
                $table->timestamp('countersigned_at')->nullable()->after('countersignature_ip');
            }
            // Party B (steward selected in wizard)
            if (!Schema::hasColumn('continuity_documents', 'party_b_id')) {
                $table->char('party_b_id', 36)->nullable()->after('plan_id');
            }
            // Wizard fields
            if (!Schema::hasColumn('continuity_documents', 'category')) {
                $table->string('category', 32)->nullable()->after('doc_type');
            }
            if (!Schema::hasColumn('continuity_documents', 'effective_date')) {
                $table->date('effective_date')->nullable()->after('category');
            }
            if (!Schema::hasColumn('continuity_documents', 'auto_renew')) {
                $table->boolean('auto_renew')->default(false)->after('effective_date');
            }
            if (!Schema::hasColumn('continuity_documents', 'notes')) {
                $table->text('notes')->nullable()->after('body');
            }
            // Supporting doc flag
            if (!Schema::hasColumn('continuity_documents', 'is_supporting')) {
                $table->boolean('is_supporting')->default(false)->after('notes');
            }
            // Related doc for amendment
            if (!Schema::hasColumn('continuity_documents', 'related_to')) {
                $table->string('related_to', 100)->nullable()->after('is_supporting');
            }
        });
    }

    public function down(): void
    {
        Schema::table('continuity_documents', function (Blueprint $table) {
            $table->dropColumn([
                'body', 'signed_by_id', 'signature_name', 'signature_ip', 'signed_at',
                'countersigned_by_id', 'countersignature_name', 'countersignature_ip', 'countersigned_at',
                'party_b_id', 'category', 'effective_date', 'auto_renew',
                'notes', 'is_supporting', 'related_to',
            ]);
        });

        DB::statement("ALTER TABLE continuity_documents MODIFY COLUMN status ENUM(
            'draft','countersign','active','archived','release_pending'
        ) NOT NULL DEFAULT 'draft'");
    }
};
