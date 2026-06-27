<?php
// Domain 14 — profile_edit_authorizations — UC-PRV profile-edit authorization; CS/SS assisted edit

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_edit_authorizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('practitioner_id', 36)->index();
            $table->char('authorized_user_id', 36)->index();
            $table->json('scope')->nullable();
            $table->enum('status', ['active', 'revoked'])->default('active')->index();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['practitioner_id', 'authorized_user_id'], 'uq_profauth');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_edit_authorizations');
    }
};
