<?php
// Domain 11 — news_poll_votes — UC-news poll vote

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_poll_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('post_id', 36)->index();
            $table->char('user_id', 36)->index();
            $table->string('option_key', 64);
            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['post_id', 'user_id'], 'uq_news_poll');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_poll_votes');
    }
};
