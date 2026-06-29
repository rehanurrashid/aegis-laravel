<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds provider_credentials with realistic licenses & insurance for demo users.
 * Mirrors the 5-row credential card on the provider dashboard:
 *   1. Medical / Clinical License (NY)        — Active   (~230 days remaining)
 *   2. State License (CA)                      — Critical (~20  days remaining)
 *   3. DEA Registration                        — Active   (~108 days remaining)
 *   4. Professional Liability                  — Critical (~20  days remaining)
 *   5. General Business Insurance              — Active   (~230 days remaining)
 */
class ProviderCredentialSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            // 1. Medical / Clinical License (NY) — Active
            [
                'cred_type'  => 'Medical / Clinical License',
                'icon'       => 'credit-card',
                'name'       => 'Medical / Clinical License',
                'subtitle'   => 'Psychiatrist, MD · New York',
                'issuer'     => 'New York State',
                'number'     => 'NY-MD-12345',
                'issued_on'  => $now->copy()->subYears(4)->toDateString(),
                'expires_on' => $now->copy()->addDays(230)->toDateString(),
                'sort_order' => 1,
            ],
            // 2. State License (CA) — expires in 20 days (Critical)
            [
                'cred_type'  => 'State License (CA)',
                'icon'       => 'credit-card',
                'name'       => 'State License (CA)',
                'subtitle'   => 'Psychiatrist, MD · California',
                'issuer'     => 'California',
                'number'     => 'CA-MD-67890',
                'issued_on'  => $now->copy()->subYears(2)->toDateString(),
                'expires_on' => $now->copy()->addDays(20)->toDateString(),
                'sort_order' => 2,
            ],
            // 3. DEA Registration — 108 days
            [
                'cred_type'  => 'DEA Registration',
                'icon'       => 'clipboard',
                'name'       => 'DEA Registration',
                'subtitle'   => 'Schedule II–IV',
                'issuer'     => 'U.S. Drug Enforcement Administration',
                'number'     => 'BR1234567',
                'issued_on'  => $now->copy()->subYears(3)->toDateString(),
                'expires_on' => $now->copy()->addDays(108)->toDateString(),
                'sort_order' => 3,
            ],
            // 4. Professional Liability — expires in 20 days (Critical)
            [
                'cred_type'  => 'Professional Liability',
                'icon'       => 'shield',
                'name'       => 'Professional Liability',
                'subtitle'   => 'Medical Protective · $2M / $2M',
                'issuer'     => 'Medical Protective',
                'number'     => 'MP-2024-78321',
                'issued_on'  => $now->copy()->subYears(1)->toDateString(),
                'expires_on' => $now->copy()->addDays(20)->toDateString(),
                'sort_order' => 4,
            ],
            // 5. General Business Insurance — 230 days
            [
                'cred_type'  => 'General Business Insurance',
                'icon'       => 'briefcase',
                'name'       => 'General Business Insurance',
                'subtitle'   => 'HISCOX · $1M General Liability',
                'issuer'     => 'HISCOX',
                'number'     => 'HSX-2024-44210',
                'issued_on'  => $now->copy()->subMonths(8)->toDateString(),
                'expires_on' => $now->copy()->addDays(230)->toDateString(),
                'sort_order' => 5,
            ],
        ];

        // Insert credentials for every active practitioner so demo accounts all have coverage.
        $practitionerIds = DB::table('users')
            ->where('role', 'practitioner')
            ->pluck('id')
            ->toArray();

        $inserts = [];
        foreach ($practitionerIds as $userId) {
            foreach ($rows as $r) {
                $inserts[] = array_merge($r, [
                    'id'         => (string) Str::uuid(),
                    'user_id'    => $userId,
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ]);
            }
        }

        if (!empty($inserts)) {
            // Avoid duplicate seed runs blowing up on PK collision.
            DB::table('provider_credentials')->insertOrIgnore($inserts);
        }
    }
}
