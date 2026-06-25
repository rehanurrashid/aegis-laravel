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

        $sarahItems = [
            [
                'id'                      => 'vi_sarah_ehr',
                'practitioner_id'         => 'p_sarah',
                'zone'                    => 'credentials',
                'category'                => 'ehr',
                'title'                   => 'EHR System — SimplePractice',
                'sub_label'               => 'Primary practice management system',
                'status'                  => 'active',
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
                'category'                => 'billing',
                'title'                   => 'Billing Portal — Availity',
                'sub_label'               => 'Insurance claim submission portal',
                'status'                  => 'active',
                'credential_username'     => 'SarahJ_Clarity',
                'credential_password_enc' => 'enc:AES256:U2FsdGVkX19billing789',
                'credential_url'          => 'https://www.availity.com',
                'access_grant'            => json_encode(['cs_marcus']),
                'created_at'              => $now->copy()->subMonths(7)->toDateTimeString(),
                'updated_at'              => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_client_high',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'roster',
                'category'        => 'high_acuity',
                'title'           => 'High-Acuity Client Record',
                'sub_label'       => 'Requires immediate contact upon incident',
                'status'          => 'priority',
                'client_name'     => 'Client A (High Risk)',
                'client_priority' => 1,
                'access_grant'    => json_encode(['cs_marcus', 'cs_priya']),
                'created_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_client_mid',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'roster',
                'category'        => 'standard',
                'title'           => 'Active Client Roster — Full List',
                'sub_label'       => '22 active clients with contact information',
                'status'          => 'active',
                'file_ref'        => 'vault/p_sarah/roster_active_2024.pdf',
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(5)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(3)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_client_emergency',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'roster',
                'category'        => 'emergency_contacts',
                'title'           => 'Emergency Contacts & Notification Preferences',
                'sub_label'       => 'Client emergency contact information',
                'status'          => 'vault_only',
                'file_ref'        => 'vault/p_sarah/emergency_contacts.pdf',
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(5)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(5)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_license',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'documents',
                'category'        => 'license',
                'title'           => 'Texas LPC License — #78234',
                'sub_label'       => 'Expires Dec 31, 2025',
                'status'          => 'active',
                'file_ref'        => 'vault/p_sarah/lpc_license_tx.pdf',
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(7)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_insurance',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'documents',
                'category'        => 'malpractice',
                'title'           => 'Professional Liability Insurance Policy',
                'sub_label'       => 'HPSO Policy — Active',
                'status'          => 'vault_only',
                'file_ref'        => 'vault/p_sarah/malpractice_policy.pdf',
                'access_grant'    => json_encode(['cs_marcus']),
                'created_at'      => $now->copy()->subMonths(7)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(7)->toDateTimeString(),
            ],
            [
                'id'              => 'vi_sarah_instructions',
                'practitioner_id' => 'p_sarah',
                'zone'            => 'instructions',
                'category'        => 'closing_protocol',
                'title'           => 'Practice Closure Protocol',
                'sub_label'       => 'Step-by-step instructions for practice wind-down',
                'status'          => 'vault_only',
                'file_ref'        => 'vault/p_sarah/closure_instructions.pdf',
                'access_grant'    => json_encode(['cs_marcus', 'cs_priya']),
                'created_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
            ],
        ];

        foreach ($sarahItems as $item) {
            $item = array_merge([
                'credential_username'     => null,
                'credential_password_enc' => null,
                'credential_url'          => null,
                'client_name'             => null,
                'client_priority'         => null,
                'file_ref'                => null,
                'deleted_at'              => null,
            ], $item);
            DB::table('vault_items')->updateOrInsert(['id' => $item['id']], $item);
        }

        // p_david partial vault
        DB::table('vault_items')->updateOrInsert(['id' => 'vi_david_ehr'], [
            'id'                      => 'vi_david_ehr',
            'practitioner_id'         => 'p_david',
            'zone'                    => 'credentials',
            'category'                => 'ehr',
            'title'                   => 'EHR System — TherapyNotes',
            'sub_label'               => 'Practice management system',
            'status'                  => 'vault_only',
            'credential_username'     => 'david.chen@therapynotes.demo',
            'credential_password_enc' => 'enc:AES256:U2FsdGVkX19david_ehr',
            'credential_url'          => 'https://therapynotes.com',
            'access_grant'            => null,
            'created_at'              => now()->subWeeks(4)->toDateTimeString(),
            'updated_at'              => now()->subWeeks(4)->toDateTimeString(),
            'deleted_at'              => null,
        ]);

        // vault_access_log columns: id, vault_item_id, practitioner_id, actor_id, access_type, recipient_id, ip_address, created_at
        // Removed: accessed_by_id→actor_id, incident_id (doesn't exist), accessed_at→created_at
        // Added: practitioner_id (NOT NULL)
        $accessLog = [
            [
                'id'            => (string) Str::uuid(),
                'vault_item_id' => 'vi_sarah_ehr',
                'practitioner_id' => 'p_sarah',
                'actor_id'      => 'cs_marcus',
                'access_type'   => 'view',
                'created_at'    => $now->copy()->subDays(2)->subHours(17)->toDateTimeString(),
            ],
            [
                'id'            => (string) Str::uuid(),
                'vault_item_id' => 'vi_sarah_client_high',
                'practitioner_id' => 'p_sarah',
                'actor_id'      => 'cs_marcus',
                'access_type'   => 'view',
                'created_at'    => $now->copy()->subDays(2)->subHours(16)->toDateTimeString(),
            ],
            [
                'id'            => (string) Str::uuid(),
                'vault_item_id' => 'vi_sarah_client_mid',
                'practitioner_id' => 'p_sarah',
                'actor_id'      => 'cs_marcus',
                'access_type'   => 'download',
                'created_at'    => $now->copy()->subDays(1)->subHours(3)->toDateTimeString(),
            ],
        ];
        foreach ($accessLog as $log) {
            DB::table('vault_access_log')->insert($log);
        }

        // vault_item_meta columns: id, vault_item_id, meta_key, meta_value, meta_type, created_at, updated_at
        DB::table('vault_item_meta')->insert([
            'id'            => (string) Str::uuid(),
            'vault_item_id' => 'vi_sarah_ehr',
            'meta_key'      => 'last_verified_at',
            'meta_value'    => $now->copy()->subMonths(5)->toISOString(),
            'meta_type'     => 'timestamp',
            'created_at'    => $now->copy()->subMonths(5)->toDateTimeString(),
            'updated_at'    => $now->copy()->subMonths(5)->toDateTimeString(),
        ]);
        DB::table('vault_item_meta')->insert([
            'id'            => (string) Str::uuid(),
            'vault_item_id' => 'vi_sarah_license',
            'meta_key'      => 'expiry_date',
            'meta_value'    => '2025-12-31',
            'meta_type'     => 'string',
            'created_at'    => $now->copy()->subMonths(7)->toDateTimeString(),
            'updated_at'    => $now->copy()->subMonths(7)->toDateTimeString(),
        ]);
    }
}
