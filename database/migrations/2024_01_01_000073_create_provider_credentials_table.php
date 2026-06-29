<?php
// Domain 7 — provider_credentials — UC-PRV-160
// Stores a provider's licenses, certifications, and insurance policies.
// Backs the Credentials & Coverage card on the provider dashboard.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_credentials', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('user_id', 36)->index();

            // Display
            $table->string('cred_type', 64);                  // "Medical / Clinical License", "DEA Registration", "Professional Liability", etc.
            $table->string('icon', 32)->default('credit-card'); // AegisIcon name: credit-card | clipboard | shield | briefcase
            $table->string('name', 191)->nullable();           // Free-form display title
            $table->string('subtitle', 191)->nullable();       // e.g., "Psychiatrist, MD · New York"

            // Identifiers
            $table->string('issuer', 191)->nullable();         // State or carrier name
            $table->string('number', 191)->nullable();         // License/policy number

            // Dates
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable()->index();

            // Document storage path
            $table->string('document_path', 255)->nullable();

            // Sort / status
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['user_id', 'expires_on'], 'ix_provider_creds_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_credentials');
    }
};
