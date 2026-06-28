<?php
// Widen mfa_tokens.secret from VARCHAR(255) to TEXT.
// Laravel's Crypt::encryptString() output (IV + HMAC-SHA256 + AES-256-CBC, base64-encoded)
// routinely exceeds 255 chars. The original VARCHAR(255) was too narrow.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mfa_tokens', function (Blueprint $table) {
            $table->text('secret')->change();
        });
    }

    public function down(): void
    {
        Schema::table('mfa_tokens', function (Blueprint $table) {
            $table->string('secret', 255)->change();
        });
    }
};
