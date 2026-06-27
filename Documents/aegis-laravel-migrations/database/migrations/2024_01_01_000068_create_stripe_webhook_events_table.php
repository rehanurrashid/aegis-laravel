<?php
// Domain 13 — stripe_webhook_events — UC-ADM-046

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('stripe_event_id', 64)->unique('uq_webhook_event');
            $table->string('event_type', 64)->index();
            $table->json('payload_json')->nullable();
            $table->tinyInteger('processed')->default(0)->index();
            $table->timestamp('received_at')->useCurrent()->index();
            $table->timestamp('processed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
    }
};
