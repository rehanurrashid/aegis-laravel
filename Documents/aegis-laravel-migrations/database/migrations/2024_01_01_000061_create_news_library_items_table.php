<?php
// Domain 11 — news_library_items — UC-news library

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_library_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 191);
            $table->string('category', 64)->nullable()->index();
            $table->string('url', 255)->nullable();
            $table->string('file_ref', 255)->nullable();
            $table->string('role_visibility', 40)->default('all')->index();
            $table->integer('sort_order')->default(0);
            $table->tinyInteger('published')->default(1)->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_library_items');
    }
};
