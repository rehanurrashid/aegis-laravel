<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_events', function (Blueprint $table) {
            $table->string('category', 40)->default('training')->after('location');
            $table->decimal('ceu_credits', 5, 2)->default(0)->after('category');
            $table->boolean('is_free')->default(true)->after('ceu_credits');
            $table->unsignedInteger('price_cents')->default(0)->after('is_free');
            $table->string('rsvp_url', 500)->nullable()->after('price_cents');
            $table->json('rsvps_json')->nullable()->after('rsvp_url');
            $table->string('organizer', 191)->nullable()->after('rsvps_json');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved')->after('organizer');
        });
    }

    public function down(): void
    {
        Schema::table('news_events', function (Blueprint $table) {
            $table->dropColumn(['category', 'ceu_credits', 'is_free', 'price_cents', 'rsvp_url', 'rsvps_json', 'organizer', 'status']);
        });
    }
};
