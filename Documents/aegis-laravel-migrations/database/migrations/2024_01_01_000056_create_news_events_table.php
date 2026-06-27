<?php
// Domain 11 — news_events — UC-PRV events register/cancel

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->string('location', 191)->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable();
            $table->string('role_visibility', 40)->default('all')->index();
            $table->tinyInteger('published')->default(1)->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_events');
    }
};
