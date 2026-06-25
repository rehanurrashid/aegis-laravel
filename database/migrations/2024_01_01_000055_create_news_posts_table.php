<?php
// Domain 11 — news_posts — UC-news feed read/post

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('author_id', 36)->nullable()->index();
            $table->string('title', 191);
            $table->text('body')->nullable();
            $table->enum('post_type', ['post', 'poll', 'announcement'])->default('post')->index();
            $table->string('role_visibility', 40)->default('all')->index();
            $table->tinyInteger('published')->default(1)->index();
            $table->tinyInteger('pinned')->default(0)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_posts');
    }
};
