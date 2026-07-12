<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 1 — new tables for proposal attachments and milestone submission audit trail.
 *
 * bp_proposal_attachments:
 *   Proposal files uploaded by BP at submit time.
 *
 * bp_milestone_submissions:
 *   Immutable audit log per submit cycle. Each time BP submits (or resubmits after
 *   revision request), a new row is written. Provider review action + notes stored here.
 *   This gives full history: submission 1 → revision requested → submission 2 → approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bp_proposal_attachments')) {
            Schema::create('bp_proposal_attachments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->char('proposal_id', 36)->index();
                $table->string('filename', 191);
                $table->string('path', 500);
                $table->string('mime_type', 80)->nullable();
                $table->unsignedInteger('size_bytes')->default(0);
                $table->timestamp('uploaded_at')->useCurrent();

                $table->index(['proposal_id'], 'ix_prop_att_proposal');

                $table->foreign('proposal_id')
                      ->references('id')->on('bp_proposals')
                      ->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('bp_milestone_submissions')) {
            Schema::create('bp_milestone_submissions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->char('milestone_id', 36)->index();
                $table->char('contract_id', 36)->index();
                $table->char('submitted_by', 36)->index();   // BP user id
                $table->text('submission_notes')->nullable();
                $table->json('attachments')->nullable();     // [{filename, path, size, mime}]
                $table->decimal('hours_logged', 6, 2)->nullable();
                // Provider review
                $table->char('reviewed_by', 36)->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->enum('review_action', ['approved', 'revision_requested', 'rejected'])->nullable();
                $table->text('review_notes')->nullable();
                // Timestamps
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index(['milestone_id', 'created_at'], 'ix_ms_sub_milestone_created');

                $table->foreign('milestone_id')
                      ->references('id')->on('bp_milestones')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_milestone_submissions');
        Schema::dropIfExists('bp_proposal_attachments');
    }
};
