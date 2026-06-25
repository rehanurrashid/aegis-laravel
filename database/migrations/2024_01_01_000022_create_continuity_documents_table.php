<?php
// Domain 4 — continuity_documents — UC-PRV-080..082,190..197; UC-XP-002

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('continuity_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('plan_id', 36)->nullable()->index();
            $table->char('practitioner_id', 36)->index();
            $table->string('reference', 64)->nullable()->unique('uq_doc_reference');
            $table->string('title', 191);
            $table->string('doc_type', 64)->nullable()->index();
            $table->enum('status', ['draft', 'countersign', 'active', 'archived', 'release_pending'])->default('draft')->index();
            $table->char('amends_document_id', 36)->nullable()->index();
            $table->char('holder_steward_id', 36)->nullable()->index();
            $table->string('file_ref', 255)->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['practitioner_id', 'status'], 'ix_doc_pract_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('continuity_documents');
    }
};
