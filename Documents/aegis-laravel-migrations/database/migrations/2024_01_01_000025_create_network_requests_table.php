<?php
// Domain 5 — network_requests — UC-PRV-100,101,103

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('requester_id', 36)->index();
            $table->char('recipient_id', 36)->nullable()->index();
            $table->string('recipient_email', 191)->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled'])->default('pending')->index();
            $table->string('message', 255)->nullable();
            $table->string('invite_token', 128)->nullable()->unique('uq_netreq_token');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_requests');
    }
};
