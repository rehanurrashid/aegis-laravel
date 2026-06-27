<?php
// Domain 11 — news_trending_topics — UC-news trending

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_trending_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('topic', 191)->unique('uq_trending_topic');
            $table->integer('score')->default(0)->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_trending_topics');
    }
};
