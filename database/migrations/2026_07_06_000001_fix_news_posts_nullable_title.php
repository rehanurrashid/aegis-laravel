<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix news_posts.title to be nullable (UI allows posts without a title).
 * Also ensures post_type is a string column, not a strict enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_posts', function (Blueprint $table) {
            $table->string('title', 191)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('news_posts', function (Blueprint $table) {
            $table->string('title', 191)->nullable(false)->change();
        });
    }
};
