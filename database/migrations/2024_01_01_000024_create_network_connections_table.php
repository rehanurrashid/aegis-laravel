<?php
// Domain 5 — network_connections — UC-PRV-100,101; UC-XP-027,030

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->index();
            $table->char('connected_user_id', 36)->index();
            $table->enum('connection_type', ['practitioner', 'business_partner'])->default('practitioner')->index();
            $table->enum('status', ['active', 'archived'])->default('active')->index();
            $table->timestamp('connected_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['user_id', 'connected_user_id'], 'uq_netconn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_connections');
    }
};
