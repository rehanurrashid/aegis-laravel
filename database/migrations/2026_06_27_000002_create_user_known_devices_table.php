<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * user_known_devices — stores a fingerprint per user+device so the login flow
 * can detect and alert on new device logins (auth.07-new-device-login template).
 * Fingerprint = SHA-256 of: user_id + User-Agent + /24 subnet of IP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_known_devices', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('user_id', 36)->index();
            $table->char('fingerprint', 64)->comment('SHA-256(user_id|ua|ip_subnet)');
            $table->string('device_label', 191)->nullable()->comment('Parsed UA string, e.g. Chrome on macOS');
            $table->string('location_label', 191)->nullable()->comment('City/Country from IP geo, or IP fallback');
            $table->string('ip', 45)->nullable();
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['user_id', 'fingerprint'], 'uq_known_device');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_known_devices');
    }
};
