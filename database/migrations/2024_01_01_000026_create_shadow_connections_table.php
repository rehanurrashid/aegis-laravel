<?php
// Domain 5 — shadow_connections — UC-PRV-100

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shadow_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->index();
            $table->char('shadow_user_id', 36)->nullable()->index();
            $table->string('shadow_name', 191)->nullable();
            $table->string('source', 64)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shadow_connections');
    }
};
