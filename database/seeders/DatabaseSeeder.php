<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Layer 1 — identity (no FKs)
            RoleSeeder::class,
            UserSeeder::class,
            BpDirectorySeeder::class,
            MfaTokenSeeder::class,
            UserMetaSeeder::class,
            UserSessionSeeder::class,
            SubscriptionSeeder::class,

            // Layer 2 — plans + stewards
            PlanSeeder::class,
            StewardSeeder::class,
            PlanTaskSeeder::class,
            IncidentConfigSeeder::class,

            // Layer 3 — incidents
            IncidentSeeder::class,

            // Layer 4 — vault (depends on incidents for access log)
            VaultSeeder::class,

            // Layer 5 — documents
            DocumentSeeder::class,

            // Layer 6 — network
            NetworkSeeder::class,
            SarahConnectionRequestsSeeder::class,
            NetworkRecommendationSeeder::class,
            FixNinaConnectionSeeder::class,
            ReferralSeeder::class,

            // Layer 7 — messaging
            MessageSeeder::class,

            // Layer 8 — services
            ServiceSeeder::class,
            CeuSeeder::class,
            ProviderCredentialSeeder::class,
            CeuRequirementSeeder::class,

            // Layer 9 — BP ecosystem
            BpSeeder::class,
            InvoiceSeeder::class,
            PayoutSeeder::class,

            // Layer 10 — provider check-ins (CS + SS, uses provider_checkins table per migration 000072)
            ProviderCheckinSeeder::class,

            // Layer 11 — news
            NewsSeeder::class,

            // Layer 12 — support
            SupportSeeder::class,

            // Layer 13 — admin + system
            AdminSeeder::class,
            PackageSeeder::class,

            // Layer 14 — activity fan-out (references all above)
            ActivitySeeder::class,

            // Layer 15 — activity read receipts (must run after ActivitySeeder)
            ActivityReadSeeder::class,
        ]);
    }
}
