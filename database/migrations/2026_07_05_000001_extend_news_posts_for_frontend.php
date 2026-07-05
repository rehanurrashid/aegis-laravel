<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends news_posts to support the full frontend feature set:
 * - post_type enum expansion (provider, question, resource, milestone, event)
 * - tags (JSON array of strings)
 * - links (JSON array of {label, url} objects)
 * - poll_question / poll_options / poll_closes_at
 * - audience (alias for role_visibility, stored separately for UI clarity)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_posts', function (Blueprint $table) {
            // Expand post_type: drop enum constraint, use string for flexibility
            $table->string('post_type', 40)->default('provider')->change();
            // Tags: ["telehealth","compliance"]
            $table->json('tags')->nullable()->after('body');
            // Links: [{"label":"Learn more","url":"https://..."}]
            $table->json('links')->nullable()->after('tags');
            // Poll support
            $table->string('poll_question', 500)->nullable()->after('links');
            $table->json('poll_options')->nullable()->after('poll_question');
            $table->timestamp('poll_closes_at')->nullable()->after('poll_options');
            // Audience (maps to role_visibility — kept separate for future granularity)
            $table->string('audience', 40)->default('all')->after('role_visibility');
        });
    }

    public function down(): void
    {
        Schema::table('news_posts', function (Blueprint $table) {
            $table->dropColumn(['tags', 'links', 'poll_question', 'poll_options', 'poll_closes_at', 'audience']);
            $table->enum('post_type', ['post', 'poll', 'announcement'])->default('post')->change();
        });
    }
};
