<?php

/**
 * DocumentSeederAddendum
 *
 * Run AFTER DocumentSeeder. Adds the Wave 9 scenario docs:
 *  - doc_sarah_fee_amendment   — fee_amendment, countersign_pending (awaiting Marcus)
 *  - doc_sarah_ss_auth         — ss_authorization_agreement, fully_executed (Linda)
 *  - doc_sarah_plan_agreement  — plan_agreement, fully_executed (self)
 *
 * Usage:
 *   DB::table('continuity_documents') already has rows for p_sarah from DocumentSeeder.
 *   These rows complement (not replace) the existing seed.
 *
 * To run standalone:
 *   php artisan db:seed --class=DocumentSeederAddendum
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ContinuityPlan;

class DocumentSeederAddendum extends Seeder
{
    public function run(): void
    {
        $now = now();
        $planSarah = ContinuityPlan::where('practitioner_id', 'p_sarah')
            ->orderByDesc('created_at')->value('id');

        if (!$planSarah) {
            $this->command->warn('No plan found for p_sarah — skipping DocumentSeederAddendum.');
            return;
        }

        $docs = [
            // Fee amendment — countersign_pending (awaiting Marcus)
            [
                'id'                  => 'doc_sarah_fee_amendment',
                'plan_id'             => $planSarah,
                'practitioner_id'     => 'p_sarah',
                'party_b_id'          => 'cs_marcus',
                'reference'           => 'AMN-2025-001',
                'title'               => 'Fee Amendment — Marcus Williams',
                'doc_type'            => 'fee_amendment',
                'category'            => 'pe',
                'status'              => 'countersign_pending',
                'holder_steward_id'   => 'cs_marcus',
                'amends_document_id'  => 'doc_sarah_cs_engagement',
                'signed_by_id'        => 'p_sarah',
                'signature_name'      => 'Dr. Sarah Johnson, LPC',
                'signature_ip'        => '127.0.0.1',
                'signed_at'           => $now->copy()->subDays(3)->toDateTimeString(),
                'notes'               => 'Proposed fee adjustment from $2,500/mo to $2,800/mo effective next quarter.',
                'effective_date'      => $now->copy()->addDays(28)->toDateString(),
                'is_supporting'       => 0,
                'auto_renew'          => 0,
                'created_at'          => $now->copy()->subDays(3)->toDateTimeString(),
                'updated_at'          => $now->copy()->subDays(3)->toDateTimeString(),
            ],

            // SS Authorization — fully_executed (Linda)
            [
                'id'                  => 'doc_sarah_ss_auth',
                'plan_id'             => $planSarah,
                'practitioner_id'     => 'p_sarah',
                'party_b_id'          => 'ss_linda',
                'reference'           => 'SSA-2024-001',
                'title'               => 'Support Steward Authorization — Linda Carter',
                'doc_type'            => 'ss_authorization_agreement',
                'category'            => 'pd',
                'status'              => 'fully_executed',
                'holder_steward_id'   => 'ss_linda',
                'signed_by_id'        => 'p_sarah',
                'signature_name'      => 'Dr. Sarah Johnson, LPC',
                'signature_ip'        => '127.0.0.1',
                'signed_at'           => $now->copy()->subMonths(5)->toDateTimeString(),
                'countersigned_by_id' => 'ss_linda',
                'countersignature_name' => 'Linda Carter, MA',
                'countersignature_ip' => '127.0.0.1',
                'countersigned_at'    => $now->copy()->subMonths(5)->addDay()->toDateTimeString(),
                'notes'               => 'Authorization for Linda Carter to act as Primary Support Steward.',
                'effective_date'      => $now->copy()->subMonths(5)->toDateString(),
                'is_supporting'       => 0,
                'auto_renew'          => 0,
                'created_at'          => $now->copy()->subMonths(5)->toDateTimeString(),
                'updated_at'          => $now->copy()->subMonths(5)->addDay()->toDateTimeString(),
            ],

            // Plan Agreement — fully_executed (auto-generated when plan signed)
            [
                'id'                  => 'doc_sarah_plan_agreement',
                'plan_id'             => $planSarah,
                'practitioner_id'     => 'p_sarah',
                'party_b_id'          => null,
                'reference'           => 'PLA-2024-001',
                'title'               => 'Continuity Plan Agreement — Dr. Sarah Johnson',
                'doc_type'            => 'plan_agreement',
                'category'            => 'pe',
                'status'              => 'fully_executed',
                'holder_steward_id'   => null,
                'signed_by_id'        => 'p_sarah',
                'signature_name'      => 'Dr. Sarah Johnson, LPC',
                'signature_ip'        => '127.0.0.1',
                'signed_at'           => $now->copy()->subMonths(7)->toDateTimeString(),
                'countersigned_by_id' => null,
                'notes'               => 'Auto-generated upon plan activation. Provider attestation that the continuity plan is accurate and current.',
                'effective_date'      => $now->copy()->subMonths(7)->toDateString(),
                'is_supporting'       => 0,
                'auto_renew'          => 1,
                'created_at'          => $now->copy()->subMonths(7)->toDateTimeString(),
                'updated_at'          => $now->copy()->subMonths(7)->toDateTimeString(),
            ],
        ];

        $defaults = [
            'body' => null, 'notes' => null, 'file_ref' => null, 'related_to' => null,
            'amends_document_id' => null, 'countersigned_by_id' => null,
            'countersignature_name' => null, 'countersignature_ip' => null,
            'countersigned_at' => null, 'issued_at' => null, 'expires_at' => null,
            'archived_at' => null, 'deleted_at' => null, 'party_b_id' => null,
            'holder_steward_id' => null, 'signature_ip' => null,
        ];

        foreach ($docs as $doc) {
            $doc = array_merge($defaults, $doc);
            DB::table('continuity_documents')->updateOrInsert(['id' => $doc['id']], $doc);
        }

        $this->command->info('DocumentSeederAddendum: 3 additional docs seeded for p_sarah.');
    }
}
