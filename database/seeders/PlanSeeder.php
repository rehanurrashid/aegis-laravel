<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── Consistent date helpers ──────────────────────────────────────────
        // Rule: annual_review_date = signed_at + 1 year, expires_at = signed_at + 1 year
        // vault_attested_at = signed_at + 30 days, last_review_at = signed_at - 1 year

        $plans = [
            // UC-PRV: p_sarah — active, signed 6 months ago, review due in 6 months
            [
                'id'                    => 'plan_sarah',
                'practitioner_id'       => 'p_sarah',
                'status'                => 'active',
                'plan_version'          => 3,
                'signed_at'             => $now->copy()->subMonths(6),
                'signature_name'        => 'Sarah Johnson',
                'signature_title'       => 'Licensed Professional Counselor',
                'signature_ip'          => '192.168.1.10',
                'expires_at'            => $now->copy()->subMonths(6)->addYear(),       // signed_at + 1yr
                'annual_review_date'    => $now->copy()->subMonths(6)->addYear(),       // signed_at + 1yr
                'last_review_at'        => $now->copy()->subMonths(6)->subYear(),       // signed_at - 1yr (previous review)
                'vault_attested_at'     => $now->copy()->subMonths(6)->addDays(30),    // signed_at + 30d
                'vault_attestation_note'=> 'All vault items verified and up to date as of this date.',
                'created_at'            => $now->copy()->subMonths(8),
                'updated_at'            => $now->copy()->subDays(1),
            ],

            // p_david — draft, unsigned
            [
                'id'                 => 'plan_david',
                'practitioner_id'    => 'p_david',
                'status'             => 'draft',
                'plan_version'       => 1,
                'signed_at'          => null,
                'expires_at'         => null,
                'annual_review_date' => null,
                'vault_attested_at'  => null,
                'created_at'         => $now->copy()->subWeeks(6),
                'updated_at'         => $now->copy()->subDays(5),
            ],

            // p_maria — annual_review_due: signed 13 months ago, so annual_review_date = 13mo ago + 1yr = 1 month ago (overdue)
            [
                'id'                    => 'plan_maria',
                'practitioner_id'       => 'p_maria',
                'status'                => 'annual_review_due',
                'plan_version'          => 2,
                'signed_at'             => $now->copy()->subMonths(13),
                'signature_name'        => 'Maria Santos',
                'signature_title'       => 'Licensed Marriage and Family Therapist',
                'signature_ip'          => '192.168.1.20',
                'expires_at'            => $now->copy()->subMonths(13)->addYear(),      // signed_at + 1yr → 1 month ago
                'annual_review_date'    => $now->copy()->subMonths(13)->addYear(),      // signed_at + 1yr → overdue
                'last_review_at'        => $now->copy()->subMonths(13)->subYear(),      // previous review
                'vault_attested_at'     => $now->copy()->subMonths(13)->addDays(30),   // signed_at + 30d
                'vault_attestation_note'=> 'Initial vault attestation at plan signing.',
                'created_at'            => $now->copy()->subMonths(14),
                'updated_at'            => $now->copy()->subDays(3),
            ],

            // p_access_only — draft, not started
            [
                'id'                 => 'plan_access_only',
                'practitioner_id'    => 'p_access_only',
                'status'             => 'draft',
                'plan_version'       => 1,
                'signed_at'          => null,
                'vault_attested_at'  => null,
                'created_at'         => $now->copy()->subWeeks(1),
                'updated_at'         => $now->copy()->subDays(1),
            ],

            // p_deactivated — expired: signed 14 months ago, annual_review_date = signed_at + 1yr = 2 months ago
            [
                'id'                 => 'plan_deactivated',
                'practitioner_id'    => 'p_deactivated',
                'status'             => 'expired',
                'plan_version'       => 1,
                'signed_at'          => $now->copy()->subMonths(14),
                'signature_name'     => 'Sam Park',
                'signature_ip'       => '192.168.1.30',
                'expires_at'         => $now->copy()->subMonths(14)->addYear(),         // signed_at + 1yr → 2mo ago
                'annual_review_date' => $now->copy()->subMonths(14)->addYear(),         // signed_at + 1yr
                'vault_attested_at'  => $now->copy()->subMonths(14)->addDays(30),      // signed_at + 30d
                'created_at'         => $now->copy()->subMonths(15),
                'updated_at'         => $now->copy()->subDays(30),
            ],
        ];

        foreach ($plans as $plan) {
            $plan = array_merge([
                'plan_version'           => 1,
                'signed_at'              => null,
                'signature_name'         => null,
                'signature_title'        => null,
                'signature_ip'           => null,
                'expires_at'             => null,
                'annual_review_date'     => null,
                'last_review_at'         => null,
                'annual_review_notes'    => null,
                'vault_attested_at'      => null,
                'vault_attestation_note' => null,
                'deleted_at'             => null,
            ], $plan);

            DB::table('continuity_plans')->updateOrInsert(['id' => $plan['id']], $plan);
        }

        // plan_meta — sparse plan attributes (template choice, custom fields)
        $planMeta = [
            ['plan_id' => 'plan_sarah', 'meta_key' => 'template_key',          'meta_value' => 'standard',           'meta_type' => 'string'],
            ['plan_id' => 'plan_sarah', 'meta_key' => 'plan_jurisdiction',     'meta_value' => 'Texas',              'meta_type' => 'string'],
            ['plan_id' => 'plan_sarah', 'meta_key' => 'practice_modality',     'meta_value' => 'solo_private',       'meta_type' => 'string'],
            ['plan_id' => 'plan_maria', 'meta_key' => 'template_key',          'meta_value' => 'standard',           'meta_type' => 'string'],
            ['plan_id' => 'plan_maria', 'meta_key' => 'plan_jurisdiction',     'meta_value' => 'California',         'meta_type' => 'string'],
            ['plan_id' => 'plan_david', 'meta_key' => 'template_key',          'meta_value' => 'minimal',            'meta_type' => 'string'],
        ];
        foreach ($planMeta as $m) {
            DB::table('plan_meta')->updateOrInsert(
                ['plan_id' => $m['plan_id'], 'meta_key' => $m['meta_key']],
                [
                    'id'         => (string) \Illuminate\Support\Str::uuid(),
                    'plan_id'    => $m['plan_id'],
                    'meta_key'   => $m['meta_key'],
                    'meta_value' => $m['meta_value'],
                    'meta_type'  => $m['meta_type'],
                ]
            );
        }
    }
}
