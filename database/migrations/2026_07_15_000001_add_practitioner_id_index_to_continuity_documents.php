<?php
// Ensure practitioner_id index exists on continuity_documents for direct scoping
// Column already present in original migration — this adds index only if missing

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // practitioner_id column and its index were created in the original migration.
        // This migration is a no-op guard — safe to run multiple times.
        // If for any reason the column is missing (older deploys), add it.
        if (!Schema::hasColumn('continuity_documents', 'practitioner_id')) {
            Schema::table('continuity_documents', function (Blueprint $table) {
                $table->char('practitioner_id', 36)->nullable()->after('plan_id')->index();
                $table->index(['practitioner_id', 'status'], 'ix_doc_pract_status_v2');
            });
        }
    }

    public function down(): void
    {
        // intentional no-op — do not drop practitioner_id
    }
};
