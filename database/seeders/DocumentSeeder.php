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

        // ── continuity_documents ────────────────────────────────────────────
        $documents = [
            // p_sarah — Active + fully executed (primary agreement, countersigned)
            [
                'id'                  => 'doc_sarah_designation',
                'plan_id'             => 'plan_sarah',
                'practitioner_id'     => 'p_sarah',
                'party_b_id'          => 'cs_marcus',
                'reference'           => 'MSA-2024-001',
                'title'               => 'Continuity Steward Designation Agreement',
                'doc_type'            => 'MSA',
                'category'            => 'pe',
                'status'              => 'fully_executed',
                'holder_steward_id'   => 'cs_marcus',
                'signed_by_id'        => 'p_sarah',
                'signature_name'      => 'Dr. Sarah Johnson, LPC',
                'signature_ip'        => '192.168.1.10',
                'signed_at'           => $now->copy()->subMonths(6)->subDays(2)->toDateTimeString(),
                'countersigned_by_id' => 'cs_marcus',
                'countersignature_name' => 'Marcus Williams',
                'countersignature_ip' => '192.168.2.20',
                'countersigned_at'    => $now->copy()->subMonths(6)->toDateTimeString(),
                'file_ref'            => 'docs/plan_sarah/steward_designation_v3.pdf',
                'issued_at'           => $now->copy()->subMonths(6)->toDateTimeString(),
                'expires_at'          => $now->copy()->addMonths(6)->toDateTimeString(),
                'created_at'          => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'          => $now->copy()->subMonths(6)->toDateTimeString(),
                'is_supporting'       => 0,
                'auto_renew'          => 0,
            ],

            // p_sarah — Active NDA
            [
                'id'                  => 'doc_sarah_nda',
                'plan_id'             => 'plan_sarah',
                'practitioner_id'     => 'p_sarah',
                'party_b_id'          => 'cs_marcus',
                'reference'           => 'NDA-2024-002',
                'title'               => 'Mutual Non-Disclosure Agreement',
                'doc_type'            => 'NDA',
                'category'            => 'pe',
                'status'              => 'fully_executed',
                'holder_steward_id'   => 'cs_marcus',
                'signed_by_id'        => 'p_sarah',
                'signature_name'      => 'Dr. Sarah Johnson, LPC',
                'signature_ip'        => '192.168.1.10',
                'signed_at'           => $now->copy()->subMonths(5)->subDay()->toDateTimeString(),
                'countersigned_by_id' => 'cs_marcus',
                'countersignature_name' => 'Marcus Williams',
                'countersignature_ip' => '192.168.2.20',
                'countersigned_at'    => $now->copy()->subMonths(5)->toDateTimeString(),
                'issued_at'           => $now->copy()->subMonths(5)->toDateTimeString(),
                'expires_at'          => null,
                'created_at'          => $now->copy()->subMonths(5)->toDateTimeString(),
                'updated_at'          => $now->copy()->subMonths(5)->toDateTimeString(),
                'is_supporting'       => 0,
                'auto_renew'          => 0,
            ],

            // p_sarah — Pending signature by provider (primary_action = sign)
            [
                'id'              => 'doc_sarah_baa',
                'plan_id'         => 'plan_sarah',
                'practitioner_id' => 'p_sarah',
                'party_b_id'      => 'cs_marcus',
                'reference'       => 'BAA-2024-003',
                'title'           => 'Business Associate Agreement (HIPAA)',
                'doc_type'        => 'BAA',
                'category'        => 'pe',
                'status'          => 'pending_sign',
                'holder_steward_id' => 'cs_marcus',
                'issued_at'       => $now->copy()->subDays(3)->toDateTimeString(),
                'expires_at'      => null,
                'created_at'      => $now->copy()->subDays(3)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(3)->toDateTimeString(),
                'is_supporting'   => 0,
                'auto_renew'      => 0,
            ],

            // p_sarah — Awaiting countersignature from CS (primary_action = default for provider)
            [
                'id'              => 'doc_sarah_sow',
                'plan_id'         => 'plan_sarah',
                'practitioner_id' => 'p_sarah',
                'party_b_id'      => 'cs_priya',
                'reference'       => 'SOW-2024-004',
                'title'           => 'Statement of Work — Continuity Coverage Q4',
                'doc_type'        => 'SOW',
                'category'        => 'pe',
                'status'          => 'countersign_pending',
                'holder_steward_id' => 'cs_priya',
                'signed_by_id'    => 'p_sarah',
                'signature_name'  => 'Dr. Sarah Johnson, LPC',
                'signature_ip'    => '192.168.1.10',
                'signed_at'       => $now->copy()->subDays(2)->toDateTimeString(),
                'issued_at'       => $now->copy()->subDays(2)->toDateTimeString(),
                'expires_at'      => null,
                'created_at'      => $now->copy()->subDays(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->toDateTimeString(),
                'is_supporting'   => 0,
                'auto_renew'      => 0,
            ],

            // p_sarah — Draft (primary_action = edit)
            [
                'id'              => 'doc_sarah_ica_draft',
                'plan_id'         => 'plan_sarah',
                'practitioner_id' => 'p_sarah',
                'party_b_id'      => null,
                'reference'       => 'ICA-DRAFT-005',
                'title'           => 'Independent Contractor Agreement — Draft',
                'doc_type'        => 'ICA',
                'category'        => 'pe',
                'status'          => 'draft',
                'holder_steward_id' => null,
                'notes'           => 'Needs review of compensation clause before sending.',
                'created_at'      => $now->copy()->subDays(7)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(1)->toDateTimeString(),
                'is_supporting'   => 0,
                'auto_renew'      => 0,
            ],

            // p_sarah — CS Engagement Agreement (fully executed, satisfies Section 7 — Documents)
            [
                'id'                  => 'doc_sarah_cs_engagement',
                'plan_id'             => 'plan_sarah',
                'practitioner_id'     => 'p_sarah',
                'party_b_id'          => 'cs_marcus',
                'reference'           => 'CEA-2024-001',
                'title'               => 'CS Engagement Agreement — Marcus Williams',
                'doc_type'            => 'cs_engagement_agreement',
                'category'            => 'pe',
                'status'              => 'fully_executed',
                'holder_steward_id'   => 'cs_marcus',
                'signed_by_id'        => 'p_sarah',
                'signature_name'      => 'Dr. Sarah Johnson, LPC',
                'signature_ip'        => '127.0.0.1',
                'signed_at'           => $now->copy()->subMonths(6)->toDateTimeString(),
                'countersigned_by_id' => 'cs_marcus',
                'countersignature_name' => 'Marcus Williams, MSW, LICSW',
                'countersignature_ip' => '127.0.0.1',
                'countersigned_at'    => $now->copy()->subMonths(6)->addDay()->toDateTimeString(),
                'body'                => null,
                'notes'               => 'CS engagement agreement covering primary steward role for Dr. Sarah Johnson.',
                'is_supporting'       => 0,
                'auto_renew'          => 0,
                'created_at'          => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'          => $now->copy()->subMonths(6)->addDay()->toDateTimeString(),
            ],

            // p_sarah — Expiring soon (primary_action = renew)
            [
                'id'                  => 'doc_sarah_sla_expiring',
                'plan_id'             => 'plan_sarah',
                'practitioner_id'     => 'p_sarah',
                'party_b_id'          => 'ss_linda',
                'reference'           => 'SLA-2023-006',
                'title'               => 'Support Steward Service Level Agreement',
                'doc_type'            => 'SLA',
                'category'            => 'pd',
                'status'              => 'active',
                'holder_steward_id'   => 'ss_linda',
                'signed_by_id'        => 'p_sarah',
                'signature_name'      => 'Dr. Sarah Johnson, LPC',
                'signed_at'           => $now->copy()->subMonths(11)->toDateTimeString(),
                'countersigned_at'    => $now->copy()->subMonths(11)->addDays(1)->toDateTimeString(),
                'issued_at'           => $now->copy()->subMonths(11)->toDateTimeString(),
                'expires_at'          => $now->copy()->addDays(18)->toDateTimeString(),  // expiring in 18 days
                'created_at'          => $now->copy()->subMonths(12)->toDateTimeString(),
                'updated_at'          => $now->copy()->subMonths(11)->toDateTimeString(),
                'is_supporting'       => 0,
                'auto_renew'          => 0,
            ],

            // p_sarah — Archived
            [
                'id'              => 'doc_sarah_archived',
                'plan_id'         => 'plan_sarah',
                'practitioner_id' => 'p_sarah',
                'reference'       => 'DOC-2023-001',
                'title'           => 'Original Steward Designation — Version 1',
                'doc_type'        => 'MSA',
                'category'        => 'pe',
                'status'          => 'archived',
                'archived_at'     => $now->copy()->subMonths(6)->toDateTimeString(),
                'created_at'      => $now->copy()->subMonths(8)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'is_supporting'   => 0,
                'auto_renew'      => 0,
            ],

            // p_sarah — Supporting document (is_supporting = 1)
            [
                'id'              => 'doc_sarah_support_1',
                'plan_id'         => 'plan_sarah',
                'practitioner_id' => 'p_sarah',
                'reference'       => 'SUP-2024-001',
                'title'           => 'Continuity Plan Amendment — Compensation Addendum',
                'doc_type'        => 'plan_amendment',
                'category'        => null,
                'status'          => 'active',
                'holder_steward_id' => 'cs_marcus',
                'related_to'      => 'Continuity Steward Agreement',
                'is_supporting'   => 1,
                'file_ref'        => 'docs/plan_sarah/supporting/amendment_comp.pdf',
                'created_at'      => $now->copy()->subDays(14)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(14)->toDateTimeString(),
                'auto_renew'      => 0,
            ],

            // p_sarah — Supporting document 2
            [
                'id'              => 'doc_sarah_support_2',
                'plan_id'         => 'plan_sarah',
                'practitioner_id' => 'p_sarah',
                'reference'       => 'SUP-2024-002',
                'title'           => 'Client Notification Letter Template',
                'doc_type'        => 'client_notification',
                'category'        => null,
                'status'          => 'active',
                'holder_steward_id' => 'cs_marcus',
                'related_to'      => 'Continuity Plan',
                'is_supporting'   => 1,
                'file_ref'        => 'docs/plan_sarah/supporting/client_notification.pdf',
                'created_at'      => $now->copy()->subMonths(5)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(5)->toDateTimeString(),
                'auto_renew'      => 0,
            ],

            // p_maria — Draft
            [
                'id'                => 'doc_maria_draft',
                'plan_id'           => 'plan_maria',
                'practitioner_id'   => 'p_maria',
                'reference'         => 'DOC-2024-010',
                'title'             => 'Annual Plan Review Documentation',
                'doc_type'          => 'review_record',
                'category'          => 'pe',
                'status'            => 'draft',
                'holder_steward_id' => 'cs_priya',
                'created_at'        => $now->copy()->subDays(7)->toDateTimeString(),
                'updated_at'        => $now->copy()->subDays(3)->toDateTimeString(),
                'is_supporting'     => 0,
                'auto_renew'        => 0,
            ],
        ];

        $defaults = [
            'party_b_id'             => null,
            'reference'              => null,
            'category'               => null,
            'body'                   => null,
            'notes'                  => null,
            'amends_document_id'     => null,
            'holder_steward_id'      => null,
            'file_ref'               => null,
            'is_supporting'          => 0,
            'related_to'             => null,
            'auto_renew'             => 0,
            'effective_date'         => null,
            'issued_at'              => null,
            'expires_at'             => null,
            'archived_at'            => null,
            'signed_by_id'           => null,
            'signature_name'         => null,
            'signature_ip'           => null,
            'signed_at'              => null,
            'countersigned_by_id'    => null,
            'countersignature_name'  => null,
            'countersignature_ip'    => null,
            'countersigned_at'       => null,
            'deleted_at'             => null,
        ];

        foreach ($documents as $doc) {
            $doc = array_merge($defaults, $doc);
            DB::table('continuity_documents')->updateOrInsert(['id' => $doc['id']], $doc);
        }

        // ── document_signatures ─────────────────────────────────────────────
        // Columns: id, document_id, signer_id, signer_role, signature_name, signature_ip, signed_at, created_at
        DB::table('document_signatures')->whereIn('document_id', array_column($documents, 'id'))->delete();

        $signatures = [
            [
                'id'             => (string) Str::uuid(),
                'document_id'    => 'doc_sarah_designation',
                'signer_id'      => 'p_sarah',
                'signer_role'    => 'practitioner',
                'signature_name' => 'Dr. Sarah Johnson, LPC',
                'signature_ip'   => '192.168.1.10',
                'signed_at'      => $now->copy()->subMonths(6)->subDays(2)->toDateTimeString(),
                'created_at'     => $now->copy()->subMonths(6)->subDays(2)->toDateTimeString(),
            ],
            [
                'id'             => (string) Str::uuid(),
                'document_id'    => 'doc_sarah_designation',
                'signer_id'      => 'cs_marcus',
                'signer_role'    => 'continuity_steward',
                'signature_name' => 'Marcus Williams',
                'signature_ip'   => '192.168.2.20',
                'signed_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'created_at'     => $now->copy()->subMonths(6)->toDateTimeString(),
            ],
            [
                'id'             => (string) Str::uuid(),
                'document_id'    => 'doc_sarah_nda',
                'signer_id'      => 'p_sarah',
                'signer_role'    => 'practitioner',
                'signature_name' => 'Dr. Sarah Johnson, LPC',
                'signature_ip'   => '192.168.1.10',
                'signed_at'      => $now->copy()->subMonths(5)->subDay()->toDateTimeString(),
                'created_at'     => $now->copy()->subMonths(5)->subDay()->toDateTimeString(),
            ],
            [
                'id'             => (string) Str::uuid(),
                'document_id'    => 'doc_sarah_nda',
                'signer_id'      => 'cs_marcus',
                'signer_role'    => 'continuity_steward',
                'signature_name' => 'Marcus Williams',
                'signature_ip'   => '192.168.2.20',
                'signed_at'      => $now->copy()->subMonths(5)->toDateTimeString(),
                'created_at'     => $now->copy()->subMonths(5)->toDateTimeString(),
            ],
            [
                'id'             => (string) Str::uuid(),
                'document_id'    => 'doc_sarah_sow',
                'signer_id'      => 'p_sarah',
                'signer_role'    => 'practitioner',
                'signature_name' => 'Dr. Sarah Johnson, LPC',
                'signature_ip'   => '192.168.1.10',
                'signed_at'      => $now->copy()->subDays(2)->toDateTimeString(),
                'created_at'     => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'             => (string) Str::uuid(),
                'document_id'    => 'doc_sarah_sla_expiring',
                'signer_id'      => 'p_sarah',
                'signer_role'    => 'practitioner',
                'signature_name' => 'Dr. Sarah Johnson, LPC',
                'signature_ip'   => '192.168.1.10',
                'signed_at'      => $now->copy()->subMonths(11)->toDateTimeString(),
                'created_at'     => $now->copy()->subMonths(11)->toDateTimeString(),
            ],
        ];

        foreach ($signatures as $sig) {
            DB::table('document_signatures')->insertOrIgnore($sig);
        }
    }
}
