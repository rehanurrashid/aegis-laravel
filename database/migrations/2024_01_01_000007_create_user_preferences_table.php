<?php
// Domain 1 — user_preferences — UC-PRV-019

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->index();
            $table->string('theme', 20)->default('gold');
            $table->integer('font_size')->default(100);
            $table->tinyInteger('compact')->default(0);
            $table->string('language', 10)->default('en');
            $table->string('timezone', 64)->default('America/New_York');
            $table->string('date_format', 20)->default('MM/DD/YYYY');
            $table->string('time_format', 5)->default('12h');
            $table->string('currency', 3)->default('USD');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('user_id', 'uq_user_pref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
