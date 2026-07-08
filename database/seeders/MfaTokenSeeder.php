<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class MfaTokenSeeder extends Seeder
{
    /**
     * MFA tokens are not seeded — users set up 2FA themselves via Settings → Security.
     * Both p_sarah and p_rehan have two_factor_enabled = 0 by default.
     */
    public function run(): void
    {
        // Sarah (p_sarah) has 2FA disabled in the demo — no MFA token seeded.
        // Users set up 2FA themselves via Settings → Security.
        // This seeder is intentionally empty.
    }
}
