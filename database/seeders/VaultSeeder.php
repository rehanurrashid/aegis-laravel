<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VaultSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── p_sarah — full vault across all 4 zones ──────────────────
        $sarahItems = [
            // CREDENTIALS zone
            [
                'id'                      => 'vi_sarah_ehr',
                'practitioner_id'         => 'p_sarah',
                'zone'                    => 'credentials',
                'category'                => 'EHR / Practice Management',
                'title'                   => 'EHR System — SimplePractice',
                'sub_label'               => 'Primary practice management system',
                'status'                  => 'vault_only',
                'description'             => '2FA via authenticator app. Support: help@simplepractice.com',
                'credential_username'     => 'sarah.johnson@claritycounseling.example',
                'credential_password_enc' => 'enc:AES256:U2FsdGVkX19xyz123abc456',
                'credential_url'          => 'https://app.simplepractice.com',
                'access_grant'            => json_encode(['cs_marcus']),
                'created_at'              => $now->copy()->subMonths(7)->toDateTimeString(),
                'updated_at'              => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'                      => 'vi_sarah_billing',
                'practitioner_id'         => 'p_sarah',
                'zone'                    => 'credentials',
                'category'                => 'Medical Billing',
                'title'                   => 'Billing Portal — Availity',
                'sub_label'               => 'Insurance claim submission portal',
                'status'                  => 'vault_only',
                'credential_username'     => 'SarahJ_Clarity',
                'credential_password_enc' => 'enc:AES256:U2FsdGVkX19billing789',
                'credential_url'          => 'https://www.availity.com',
                'access_grant'            => json_encode(['cs_marcus']),
                'created_at'              => $now->copy()->subMonths(7)->toDateTimeString(),
                'updated_at'              => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            // EMERGENCY zone
            [
                'id'              => 'vi_sarah_poa',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'emergency',
                'category'        => 'Power of Attorney',
                'title'           => 'Durable Power of Attorney',
                'sub_label'       => 'Designates Michael Johnson as healthcare POA',
                'status'          => 'vault_only',
                'description'     => 'Execute upon verified critical incident. Filed with county clerk.',
                'file_ref'        => 'vault/p_sarah/poa_2024.pdf',
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_succession',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'emergency',
                'category'        => 'Practice Succession Instructions',
                'title'           => 'Practice Closure Protocol',
                'sub_label'       => 'Step-by-step instructions for practice wind-down',
                'status'          => 'vault_only',
                'description'     => 'Outlines client notification order, records transfer procedures, and referral network contacts.',
                'file_ref'        => 'vault/p_sarah/closure_protocol.pdf',
                'access_grant'    => json_encode(['cs_marcus', 'cs_priya']),
                'created_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_emergency_contacts',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'emergency',
                'category'        => 'Emergency Contact Sheet',
                'title'           => 'Emergency Contacts & Notification Preferences',
                'sub_label'       => 'Client emergency contact information',
                'status'          => 'vault_only',
                'file_ref'        => 'vault/p_sarah/emergency_contacts.pdf',
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(5)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(5)->toDateTimeString(),
            ],
            // STANDARD zone (All Documents)
            [
                'id'              => 'vi_sarah_license',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'standard',
                'category'        => 'Licenses & Credentials',
                'title'           => 'Texas LPC License — #78234',
                'sub_label'       => 'Licensed Professional Counselor',
                'status'          => 'expiring',
                'description'     => 'Renewal deadline: December 31, 2025. CE credits required: 24hrs.',
                'file_ref'        => 'vault/p_sarah/lpc_license_tx.pdf',
                'issued_at'       => '2023-01-01',
                'expires_at'      => '2025-12-31',
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(7)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_insurance',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'standard',
                'category'        => 'Insurance Policies',
                'title'           => 'Professional Liability Insurance Policy',
                'sub_label'       => 'ProAssurance — Policy #PA-2024-78234',
                'status'          => 'active',
                'file_ref'        => 'vault/p_sarah/liability_insurance.pdf',
                'issued_at'       => '2024-01-01',
                'expires_at'      => '2026-01-01',
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(7)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_agreement',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'standard',
                'category'        => 'Agreements & Contracts',
                'title'           => 'Office Lease Agreement — Suite 302',
                'sub_label'       => 'Monthly lease for practice office',
                'status'          => 'active',
                'file_ref'        => 'vault/p_sarah/office_lease.pdf',
                'issued_at'       => '2024-06-01',
                'expires_at'      => '2026-05-31',
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(4)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(4)->toDateTimeString(),
            ],
            // ROSTER zone
            [
                'id'              => 'vi_sarah_client_high',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'roster',
                'category'        => 'high_acuity',
                'title'           => 'Client A (High Risk)',
                'status'          => 'priority',
                'client_name'     => 'Client A (High Risk)',
                'client_location' => 'Brooklyn, NY',
                'client_service'  => 'Individual Therapy',
                'client_priority' => 1,
                'client_notes'    => 'Weekly sessions. Crisis safety plan on file. Emergency contact: Guardian 555-0101.',
                'access_grant'    => json_encode(['cs_marcus', 'cs_priya']),
                'created_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_client_b',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'roster',
                'category'        => 'standard',
                'title'           => 'Client B',
                'status'          => 'active',
                'client_name'     => 'Client B',
                'client_location' => 'Manhattan, NY',
                'client_phone'    => '(555) 020-2020',
                'client_service'  => 'Couples Therapy',
                'client_priority' => 0,
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(5)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(3)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_client_c',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'roster',
                'category'        => 'priority',
                'title'           => 'Client C (Priority)',
                'status'          => 'priority',
                'client_name'     => 'Client C',
                'client_location' => 'Bronx, NY',
                'client_phone'    => '(555) 030-3030',
                'client_service'  => 'Individual Therapy — Trauma Focus',
                'client_priority' => 1,
                'client_notes'    => 'Bi-weekly sessions. EMDR protocol active.',
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(4)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_client_discharged',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'roster',
                'category'        => 'discharged',
                'title'           => 'Client D (Closed)',
                'status'          => 'vault_only',
                'client_name'     => 'Client D',
                'client_location' => 'Queens, NY',
                'client_service'  => 'Group Therapy',
                'client_priority' => 0,
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(8)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(2)->toDateTimeString(),
            ],
        ];

        $defaults = [
            'sub_label' => null, 'description' => null, 'file_name' => null,
            'file_size' => null, 'mime_type' => null, 's3_key' => null, 'file_ref' => null,
            'issued_at' => null, 'expires_at' => null, 'tags' => null,
            'credential_username' => null, 'credential_password_enc' => null, 'credential_url' => null,
            'client_name' => null, 'client_location' => null, 'client_phone' => null,
            'client_email' => null, 'client_service' => null, 'client_priority' => 0,
            'client_notes' => null, 'access_grant' => null, 'deleted_at' => null,
        ];

        foreach ($sarahItems as $item) {
            $item = array_merge($defaults, $item);
            DB::table('vault_items')->updateOrInsert(['id' => $item['id']], $item);
        }

        // ── p_david — minimal vault (early state demo) ──────────────
        DB::table('vault_items')->updateOrInsert(['id' => 'vi_david_ehr'], array_merge($defaults, [
            'id'                      => 'vi_david_ehr',
            'practitioner_id'         => 'p_david',
            'zone'                    => 'credentials',
            'category'                => 'EHR / Practice Management',
            'title'                   => 'EHR System — TherapyNotes',
            'sub_label'               => 'Practice management system',
            'status'                  => 'vault_only',
            'credential_username'     => 'david.chen@therapynotes.demo',
            'credential_password_enc' => 'enc:AES256:U2FsdGVkX19david_ehr',
            'credential_url'          => 'https://therapynotes.com',
            'created_at'              => $now->copy()->subWeeks(4)->toDateTimeString(),
            'updated_at'              => $now->copy()->subWeeks(4)->toDateTimeString(),
        ]));

        // ── Vault access log ─────────────────────────────────────────
        DB::table('vault_access_log')->insert([
            [
                'id'              => (string) Str::uuid(),
                'vault_item_id'   => 'vi_sarah_ehr',
                'practitioner_id' => 'p_sarah',
                'actor_id'        => 'cs_marcus',
                'access_type'     => 'view',
                'created_at'      => $now->copy()->subDays(2)->subHours(17)->toDateTimeString(),
            ],
            [
                'id'              => (string) Str::uuid(),
                'vault_item_id'   => 'vi_sarah_client_high',
                'practitioner_id' => 'p_sarah',
                'actor_id'        => 'cs_marcus',
                'access_type'     => 'view',
                'created_at'      => $now->copy()->subDays(2)->subHours(16)->toDateTimeString(),
            ],
        ]);
    }
}
