<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $documents = [
            [
                'id'                => 'doc_sarah_designation',
                'plan_id'           => 'plan_sarah',
                'practitioner_id'   => 'p_sarah',
                'reference'         => 'DOC-2024-001',
                'title'             => 'Continuity Steward Designation Agreement',
                'doc_type'          => 'steward_designation',
                'status'            => 'active',
                'holder_steward_id' => 'cs_marcus',
                'file_ref'          => 'docs/plan_sarah/steward_designation_v3.pdf',
                'issued_at'         => $now->copy()->subMonths(6)->toDateTimeString(),
                'expires_at'        => $now->copy()->addMonths(6)->toDateTimeString(),
                'created_at'        => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'        => $now->copy()->subMonths(6)->toDateTimeString(),
            ],
            [
                'id'                => 'doc_sarah_notification',
                'plan_id'           => 'plan_sarah',
                'practitioner_id'   => 'p_sarah',
                'reference'         => 'DOC-2024-002',
                'title'             => 'Client Notification Letter — Practice Closure',
                'doc_type'          => 'client_notification',
                'status'            => 'active',
                'holder_steward_id' => 'cs_marcus',
                'file_ref'          => 'docs/plan_sarah/client_notification_template.pdf',
                'issued_at'         => $now->copy()->subMonths(5)->toDateTimeString(),
                'expires_at'        => null,
                'created_at'        => $now->copy()->subMonths(5)->toDateTimeString(),
                'updated_at'        => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'                 => 'doc_sarah_updated',
                'plan_id'            => 'plan_sarah',
                'practitioner_id'    => 'p_sarah',
                'reference'          => 'DOC-2024-003',
                'title'              => 'Updated Continuity Plan Amendment — 2024',
                'doc_type'           => 'plan_amendment',
                'status'             => 'countersign',
                'holder_steward_id'  => 'cs_marcus',
                'amends_document_id' => 'doc_sarah_designation',
                'file_ref'           => 'docs/plan_sarah/amendment_2024.pdf',
                'issued_at'          => $now->copy()->subDays(5)->toDateTimeString(),
                'expires_at'         => null,
                'created_at'         => $now->copy()->subDays(5)->toDateTimeString(),
                'updated_at'         => $now->copy()->subDays(5)->toDateTimeString(),
            ],
            [
                'id'                => 'doc_sarah_release',
                'plan_id'           => 'plan_sarah',
                'practitioner_id'   => 'p_sarah',
                'reference'         => 'DOC-2024-004',
                'title'             => 'Emergency Records Release Authorization',
                'doc_type'          => 'records_release',
                'status'            => 'release_pending',
                'holder_steward_id' => 'cs_marcus',
                'file_ref'          => 'docs/plan_sarah/records_release.pdf',
                'issued_at'         => $now->copy()->subDays(2)->toDateTimeString(),
                'expires_at'        => $now->copy()->addDays(28)->toDateTimeString(),
                'created_at'        => $now->copy()->subDays(2)->toDateTimeString(),
                'updated_at'        => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'              => 'doc_sarah_archived',
                'plan_id'         => 'plan_sarah',
                'practitioner_id' => 'p_sarah',
                'reference'       => 'DOC-2023-001',
                'title'           => 'Original Steward Designation — Version 1',
                'doc_type'        => 'steward_designation',
                'status'          => 'archived',
                'archived_at'     => $now->copy()->subMonths(6)->toDateTimeString(),
                'created_at'      => $now->copy()->subMonths(8)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
            ],
            [
                'id'                => 'doc_maria_draft',
                'plan_id'           => 'plan_maria',
                'practitioner_id'   => 'p_maria',
                'reference'         => 'DOC-2024-010',
                'title'             => 'Annual Plan Review Documentation',
                'doc_type'          => 'review_record',
                'status'            => 'draft',
                'holder_steward_id' => 'cs_priya',
                'created_at'        => $now->copy()->subDays(7)->toDateTimeString(),
                'updated_at'        => $now->copy()->subDays(3)->toDateTimeString(),
            ],
        ];

        foreach ($documents as $doc) {
            $doc = array_merge([
                'plan_id'            => null,
                'reference'          => null,
                'amends_document_id' => null,
                'holder_steward_id'  => null,
                'file_ref'           => null,
                'issued_at'          => null,
                'expires_at'         => null,
                'archived_at'        => null,
                'deleted_at'         => null,
            ], $doc);
            DB::table('continuity_documents')->updateOrInsert(['id' => $doc['id']], $doc);
        }

        // document_signatures columns: id, document_id, signer_id, signer_role, signature_name, signature_ip, signed_at, created_at
        // Removed: signature_type (doesn't exist), ip_address → signature_ip
        // signature_name is NOT NULL — must be provided
        $signatures = [
            [
                'id'             => (string) Str::uuid(),
                'document_id'    => 'doc_sarah_designation',
                'signer_id'      => 'p_sarah',
                'signer_role'    => 'practitioner',
                'signature_name' => 'Sarah Johnson',
                'signature_ip'   => '192.168.1.10',
                'signed_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'created_at'     => $now->copy()->subMonths(6)->toDateTimeString(),
            ],
            [
                'id'             => (string) Str::uuid(),
                'document_id'    => 'doc_sarah_designation',
                'signer_id'      => 'cs_marcus',
                'signer_role'    => 'continuity_steward',
                'signature_name' => 'Marcus Williams',
                'signature_ip'   => '192.168.2.20',
                'signed_at'      => $now->copy()->subMonths(6)->addDays(2)->toDateTimeString(),
                'created_at'     => $now->copy()->subMonths(6)->addDays(2)->toDateTimeString(),
            ],
            [
                'id'             => (string) Str::uuid(),
                'document_id'    => 'doc_sarah_notification',
                'signer_id'      => 'p_sarah',
                'signer_role'    => 'practitioner',
                'signature_name' => 'Sarah Johnson',
                'signature_ip'   => '192.168.1.10',
                'signed_at'      => $now->copy()->subMonths(5)->toDateTimeString(),
                'created_at'     => $now->copy()->subMonths(5)->toDateTimeString(),
            ],
        ];
        foreach ($signatures as $sig) {
            DB::table('document_signatures')->insert($sig);
        }
    }
}
