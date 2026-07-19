<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Practice CS Add-On: mirrors maat_addon pattern.
            // true  = Practice tier user has purchased the CS Add-On (+$25/mo / +$250/yr)
            //         unlocks provider-as-CS cap of 43 practitioners.
            // false = standard Practice cap (3) or Access cap (1).
            // Synced from Stripe webhook subscription.updated via StripeEventListener.
            $table->boolean('cs_addon')->default(false)->after('maat_addon');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cs_addon');
        });
    }
};
