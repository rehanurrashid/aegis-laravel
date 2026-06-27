<?php
// Domain 4 — document_signatures — UC-PRV-035,036,191; UC-XP-002

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('document_id', 36)->index();
            $table->char('signer_id', 36)->index();
            $table->enum('signer_role', ['practitioner', 'continuity_steward'])->index();
            $table->string('signature_name', 191);
            $table->string('signature_ip', 45)->nullable();
            $table->timestamp('signed_at')->useCurrent()->index();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['document_id', 'signer_id'], 'uq_docsig');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
    }
};
