<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mfa_tokens', function (Blueprint $table) {
            // 'totp' = authenticator app, 'email' = email OTP
            $table->string('method', 10)->default('totp')->after('secret');
            // Email OTP: hashed code + expiry (null when not awaiting verification)
            $table->string('email_otp_hash')->nullable()->after('method');
            $table->timestamp('email_otp_expires_at')->nullable()->after('email_otp_hash');
        });
    }

    public function down(): void
    {
        Schema::table('mfa_tokens', function (Blueprint $table) {
            $table->dropColumn(['method', 'email_otp_hash', 'email_otp_expires_at']);
        });
    }
};
