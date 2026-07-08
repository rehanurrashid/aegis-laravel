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
        // Sarah (p_sarah) has 2FA disabled in the demo — no MFA token seeded.
        // Users set up 2FA themselves via Settings → Security.
        // This seeder is intentionally empty.
    }
}
