<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_recommendations', function (Blueprint $table) {
            $table->char('id', 36)->primary();

            // Owner practitioner this recommendation is surfaced to.
            // NULL == a global default recommendation (fallback for new users).
            $table->char('user_id', 36)->nullable()->index();

            // Kind: partner_category  → rnp-card (Search Providers tab, top row)
            //       shadow_provider   → rsp-card (Search Providers tab, second row)
            $table->enum('kind', ['partner_category', 'shadow_provider'])->index();

            // Display fields — used by both kinds
            $table->string('label', 120);
            $table->string('description', 255)->nullable();
            $table->string('icon', 60)->nullable();

            // partner_category fields
            $table->unsignedSmallInteger('nearby_count')->nullable();
            $table->enum('priority', ['high', 'medium', 'biz'])->nullable();

            // shadow_provider fields — points at another user
            $table->char('provider_user_id', 36)->nullable()->index();
            $table->unsignedTinyInteger('match_score')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('provider_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['user_id', 'kind', 'sort_order'], 'nr_owner_kind_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_recommendations');
    }
};
