<?php
// Domain 11 — news_comments — UC-news comment

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('post_id', 36)->index();
            $table->char('author_id', 36)->index();
            $table->text('body');
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['post_id', 'created_at'], 'ix_newscom_post_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_comments');
    }
};
