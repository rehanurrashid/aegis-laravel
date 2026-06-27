<?php
// Domain 8 — bp_tax_documents — UC-BP-070; update_ssn/update_ein

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_tax_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('bp_id', 36)->index();
            $table->enum('doc_type', ['w9', '1099', 'ein_doc', 'other'])->index();
            $table->enum('status', ['available', 'pending', 'verified'])->default('pending')->index();
            $table->string('download_url', 255)->nullable();
            $table->integer('year')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_tax_documents');
    }
};
