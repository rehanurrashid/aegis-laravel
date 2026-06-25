<?php
// Domain 13 — role_permissions — UC-ADM-032

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('role_id', 36)->index();
            $table->string('permission_key', 64)->index();
            $table->tinyInteger('granted')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['role_id', 'permission_key'], 'uq_roleperm');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
