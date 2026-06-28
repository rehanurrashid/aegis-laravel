<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class MfaTokenSeeder extends Seeder
{
    /**
     * Seed MFA tokens for demo users with two_factor_enabled = 1.
     *
     * Secret below is a valid base32 TOTP secret for Sarah (p_sarah).
     * Add this secret to your Authenticator app (Google Authenticator, Authy, etc.):
     *   Issuer : Aegis
     *   Account: sarah@demo.aegis
     *   Secret : VHBD3ABYS4O24S55AMIEPDME7EYFDMKJ
     *
     * After migrate:fresh --seed, the same secret is always re-seeded
     * so the authenticator app entry never goes stale.
     */
    public function run(): void
    {
        $now = now();

        // Encrypt secrets — mfa_tokens.secret has an 'encrypted' cast on the model.
        // We insert via DB facade so we must encrypt manually here.
        DB::table('mfa_tokens')->upsert([
            [
                'id'           => 'mfa_sarah_demo_00001',
                'user_id'      => 'p_sarah',
                'secret'       => Crypt::encryptString('VHBD3ABYS4O24S55AMIEPDME7EYFDMKJ'),
                'recovery_codes' => null,
                'confirmed_at' => $now,
                'disabled_at'  => null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ], ['user_id'], ['secret', 'confirmed_at', 'disabled_at', 'updated_at']);
    }
}
