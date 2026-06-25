<?php
// Domain 12 — help_articles — UC-ADM-058,059,060

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('category', 64)->nullable()->index();
            $table->string('title', 191);
            $table->longText('body');
            $table->string('role_visibility', 40)->default('all')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->tinyInteger('published')->default(1)->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_articles');
    }
};
