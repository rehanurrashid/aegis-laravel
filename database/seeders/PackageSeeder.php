<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Package overrides (testing packages.php admin feature flag toggles)
        $overrides = [
            [
                'id'                  => (string) Str::uuid(),
                'tier'                => 'access',
                'price_monthly_cents' => 4900,
                'price_annual_cents'  => 47040, // 20% off
                'feature_flags'       => json_encode([
                    'advanced_vault'          => false,
                    'unlimited_stewards'      => false,
                    'bp_marketplace'          => true,
                    'news_feed'               => true,
                    'services_mode'           => false,
                    'maat_addon_eligible'     => false,
                    'analytics_dashboard'     => false,
                    'priority_support'        => false,
                ]),
                'limits'              => json_encode([
                    'vault_items'          => 10,
                    'cs_slots'             => 1,
                    'ss_slots'             => 1,
                    'document_storage_mb'  => 500,
                    'ceu_entries'          => 20,
                ]),
                'effective_at'        => $now->copy()->subMonths(6),
                'created_at'          => $now->copy()->subMonths(6),
                'updated_at'          => $now->copy()->subMonths(1),
            ],
            [
                'id'                  => (string) Str::uuid(),
                'tier'                => 'practice',
                'price_monthly_cents' => 9900,
                'price_annual_cents'  => 95040, // 20% off
                'feature_flags'       => json_encode([
                    'advanced_vault'          => true,
                    'unlimited_stewards'      => true,
                    'bp_marketplace'          => true,
                    'news_feed'               => true,
                    'services_mode'           => true,
                    'maat_addon_eligible'     => true,
                    'analytics_dashboard'     => true,
                    'priority_support'        => true,
                ]),
                'limits'              => json_encode([
                    'vault_items'          => -1, // unlimited
                    'cs_slots'             => -1,
                    'ss_slots'             => -1,
                    'document_storage_mb'  => 5000,
                    'ceu_entries'          => -1,
                ]),
                'effective_at'        => $now->copy()->subMonths(6),
                'created_at'          => $now->copy()->subMonths(6),
                'updated_at'          => $now->copy()->subMonths(1),
            ],
        ];

        foreach ($overrides as $o) {
            DB::table('package_overrides')->updateOrInsert(['tier' => $o['tier']], $o);
        }
    }
}
