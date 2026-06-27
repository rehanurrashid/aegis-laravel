<?php
// Domain 13 — admin_audit_log — UC-ADM-023..029,031..033,042,043,045

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('admin_id', 36)->index();
            $table->string('action', 64)->index();
            $table->string('linkable_type', 64)->nullable()->index();
            $table->char('linkable_id', 36)->nullable()->index();
            $table->char('target_user_id', 36)->nullable()->index();
            $table->json('meta_json')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['admin_id', 'created_at'], 'ix_audit_admin_created');
            $table->index(['linkable_type', 'linkable_id'], 'ix_audit_linkable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_log');
    }
};
