<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop and recreate if user_id is wrong type (bigint vs uuid string)
        if (Schema::hasTable('subscriptions')) {
            $colType = DB::select("SHOW COLUMNS FROM subscriptions LIKE 'user_id'");
            $type = $colType[0]->Type ?? '';
            // If it's bigint (Cashier default), it won't hold UUID strings — recreate
            if (str_contains(strtolower($type), 'bigint') || str_contains(strtolower($type), 'int')) {
                Schema::drop('subscriptions');
            } else {
                return; // already correct string type
            }
        }

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');          // UUID string — matches Aegis users.id
            $table->string('type');
            $table->string('stripe_id')->unique();
            $table->string('stripe_status');
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'stripe_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
