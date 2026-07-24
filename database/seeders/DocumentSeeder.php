<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\ContinuityPlan;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $planSarah = ContinuityPlan::where('practitioner_id', 'p_sarah')->orderByDesc('created_at')->value('id');
        $planMaria = ContinuityPlan::where('practitioner_id', 'p_maria')->orderByDesc('created_at')->value('id');
        $planDavid = ContinuityPlan::where('practitioner_id', 'p_david')->orderByDesc('created_at')->value('id');

        // ── Clause body helper ─────────────────────────────────────────────────
        $body = function(array $clauses, ?string $notes = null): string {
            $parts = [];
            foreach ($clauses as $num => $clause) {
                $lines = ["{$num}. {$clause['title']}"];
                foreach ($clause['fields'] as $label => $value) {
                    if ($value) $lines[] = "   {$label}: {$value}";
                }
                $parts[] = implode("\n", $lines);
            }
            if ($notes) $parts[] = "Additional Notes:\n   {$notes}";
            return implode("\n\n", $parts);
        };

        // Standard clause sets
        $clauses_pe_standard = fn(array $overrides = []) => $body([
            1 => ['title' => 'Scope of Services & Delegation of Authority', 'fields' => [
                'Authorized activities' => $overrides['auth_activities'] ?? 'CS authorized to manage client referrals, schedule appointments, triage intake calls, and coordinate coverage during provider absence.',
                'Explicit exclusions' => $overrides['exclusions'] ?? 'CS may not modify clinical treatment plans, prescribe medications, act as clinical supervisor, or sign insurance claims on behalf of provider.',
            ]],
            2 => ['title' => 'Confidentiality & PHI Obligations (HIPAA)', 'fields' => [
                'PHI Access Level' => $overrides['phi'] ?? 'Read-Only',
                'Confidentiality Duration' => $overrides['conf_duration'] ?? 'Duration of agreement only',
            ]],
            3 => ['title' => 'Compensation & Fee Structure', 'fields' => [
                'Model' => $overrides['comp_model'] ?? 'Fixed Monthly Retainer',
                'Amount / Rate' => $overrides['comp_rate'] ?? '$2,500/mo',
                'Payment Cycle' => $overrides['payment_cycle'] ?? 'Monthly',
            ]],
            4 => ['title' => 'Termination & Exit Provisions', 'fields' => [
                'Notice Period' => '30 days',
                'Immediate Termination Grounds' => 'HIPAA breach, fraud, gross negligence, loss of licensure',
            ]],
            5 => ['title' => 'Liability, Indemnification & Insurance', 'fields' => [
                'Liability Cap' => 'Capped at 3 months fees paid',
                'Insurance Requirement' => 'Professional Liability min $1M / $3M aggregate',
            ]],
        ], $overrides['notes'] ?? null);

        $clauses_pd_standard = fn(array $overrides = []) => $body([
            1 => ['title' => 'Scope of Services & Delegation of Authority', 'fields' => [
                'Authorized activities' => $overrides['auth_activities'] ?? 'SS authorized to handle billing inquiries, appointment scheduling support, and administrative coordination during continuity event.',
                'Explicit exclusions' => 'SS may not provide clinical services, access treatment records without explicit authorization, or represent provider in legal matters.',
            ]],
            2 => ['title' => 'Confidentiality & PHI Obligations (HIPAA)', 'fields' => [
                'PHI Access Level' => $overrides['phi'] ?? 'No PHI Access',
                'Confidentiality Duration' => '2 years post-termination',
            ]],
            3 => ['title' => 'Compensation & Fee Structure', 'fields' => [
                'Model' => $overrides['comp_model'] ?? 'Hourly Rate',
                'Amount / Rate' => $overrides['comp_rate'] ?? '$85/hr',
                'Payment Cycle' => 'Bi-Weekly',
            ]],
            4 => ['title' => 'Termination & Exit Provisions', 'fields' => [
                'Notice Period' => '14 days',
                'Immediate Termination Grounds' => 'HIPAA breach, fraud, misrepresentation, loss of required credentials',
            ]],
            5 => ['title' => 'Liability, Indemnification & Insurance', 'fields' => [
                'Liability Cap' => 'Capped at total contract value',
                'Insurance Requirement' => 'General Liability min $1M / $2M aggregate',
            ]],
        ], $overrides['notes'] ?? null);

        $clauses_tri = fn(array $overrides = []) => $body([
            1 => ['title' => 'Scope of Services & Delegation of Authority', 'fields' => [
                'Authorized activities' => 'CS: Clinical coverage, client triage, emergency referrals. SS: Administrative coordination, scheduling, billing support. All parties operate within defined role boundaries.',
                'Explicit exclusions' => 'No party may act outside their licensed scope. CS cannot delegate clinical authority to SS. SS cannot access clinical records without provider authorization.',
            ]],
            2 => ['title' => 'Confidentiality & PHI Obligations (HIPAA)', 'fields' => [
                'PHI Access Level' => 'Read-Only',
                'Confidentiality Duration' => '5 years post-termination',
            ]],
            3 => ['title' => 'Compensation & Fee Structure', 'fields' => [
                'Model' => 'Fixed Monthly Retainer',
                'Amount / Rate' => $overrides['comp_rate'] ?? 'CS: $2,500/mo | SS: $1,200/mo',
                'Payment Cycle' => 'Monthly',
            ]],
            4 => ['title' => 'Termination & Exit Provisions', 'fields' => [
                'Notice Period' => '30 days',
                'Immediate Termination Grounds' => 'HIPAA breach, fraud, gross negligence, loss of licensure by any party',
            ]],
            5 => ['title' => 'Liability, Indemnification & Insurance', 'fields' => [
                'Liability Cap' => 'Capped at $1,000,000',
                'Insurance Requirement' => 'Professional Liability min $2M / $5M aggregate (all parties)',
            ]],
            6 => ['title' => 'Coordination Protocol (SS & CS)', 'fields' => [
                'Primary contact method' => 'Portal Messaging',
                'Escalation path' => 'SS contacts CS within 2 hours → CS contacts Provider within 4 hours → Provider responds within 8 hours.',
            ]],
        ], $overrides['notes'] ?? null);

        // ──────────────────────────────────────────────────────────────────────
        // SARAH'S DOCUMENTS — comprehensive coverage of all statuses/categories
        // ──────────────────────────────────────────────────────────────────────
        $documents = [

            // ── pe: Provider & CS ──────────────────────────────────────────────

            // 1. Fully executed MSA (pe) — Marcus — long running
            [
                'id'                   => 'doc_sarah_designation',
                'plan_id'              => $planSarah,
                'practitioner_id'      => 'p_sarah',
                'party_b_id'           => 'cs_marcus',
                'party_c_id'           => null,
                'reference'            => 'MSA-2024-001',
                'title'                => 'Continuity Steward Master Service Agreement',
                'doc_type'             => 'MSA',
                'category'             => 'pe',
                'status'               => 'fully_executed',
                'body'                 => $clauses_pe_standard(['comp_rate' => '$2,500/mo', 'phi' => 'Read/Write', 'notes' => 'Marcus serves as primary CS. Monthly check-ins required. Emergency availability confirmed.']),
                'holder_steward_id'    => 'cs_marcus',
                'signed_by_id'         => 'p_sarah',
                'signature_name'       => 'Dr. Sarah Johnson, LPC',
                'signature_ip'         => '192.168.1.10',
                'signed_at'            => $now->copy()->subMonths(6)->subDays(2)->toDateTimeString(),
                'countersigned_by_id'  => 'cs_marcus',
                'countersignature_name'=> 'Marcus Williams, MSW, LICSW',
                'countersignature_ip'  => '192.168.2.20',
                'countersigned_at'     => $now->copy()->subMonths(6)->toDateTimeString(),
                'file_ref'             => 'docs/plan_sarah/msa_marcus_v3.pdf',
                'issued_at'            => $now->copy()->subMonths(6)->toDateTimeString(),
                'effective_date'       => $now->copy()->subMonths(6)->toDateString(),
                'expires_at'           => $now->copy()->addMonths(6)->toDateTimeString(),
                'created_at'           => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'           => $now->copy()->subMonths(6)->toDateTimeString(),
                'is_supporting'        => 0,
                'auto_renew'           => 0,
                'notes'                => 'Primary continuity steward agreement. Auto-renew pending annual review.',
            ],

            // 2. Fully executed NDA (pe) — Marcus
            [
                'id'                   => 'doc_sarah_nda',
                'plan_id'              => $planSarah,
                'practitioner_id'      => 'p_sarah',
                'party_b_id'           => 'cs_marcus',
                'reference'            => 'NDA-2024-002',
                'title'                => 'Mutual Non-Disclosure Agreement — Marcus Williams',
                'doc_type'             => 'NDA',
                'category'             => 'pe',
                'status'               => 'fully_executed',
                'body'                 => $body([
                    1 => ['title' => 'Scope of Confidentiality', 'fields' => [
                        'Authorized activities' => 'Exchange of confidential clinical, business, and personal information in furtherance of the continuity arrangement.',
                        'Explicit exclusions' => 'Publicly available information and information independently developed by either party.',
                    ]],
                    2 => ['title' => 'Confidentiality & PHI Obligations (HIPAA)', 'fields' => [
                        'PHI Access Level' => 'Read-Only',
                        'Confidentiality Duration' => 'Perpetual',
                    ]],
                    3 => ['title' => 'Compensation & Fee Structure', 'fields' => [
                        'Model' => 'No compensation — mutual non-disclosure only',
                        'Amount / Rate' => '—',
                        'Payment Cycle' => '—',
                    ]],
                    4 => ['title' => 'Termination & Exit Provisions', 'fields' => [
                        'Notice Period' => '30 days',
                        'Immediate Termination Grounds' => 'Material breach of confidentiality obligations',
                    ]],
                    5 => ['title' => 'Liability, Indemnification & Insurance', 'fields' => [
                        'Liability Cap' => 'Capped at $1,000,000',
                        'Insurance Requirement' => 'Professional Liability min $1M / $3M aggregate',
                    ]],
                ], 'Perpetual confidentiality obligation survives termination of any other agreement.'),
                'holder_steward_id'    => 'cs_marcus',
                'signed_by_id'         => 'p_sarah',
                'signature_name'       => 'Dr. Sarah Johnson, LPC',
                'signature_ip'         => '192.168.1.10',
                'signed_at'            => $now->copy()->subMonths(5)->subDay()->toDateTimeString(),
                'countersigned_by_id'  => 'cs_marcus',
                'countersignature_name'=> 'Marcus Williams, MSW, LICSW',
                'countersignature_ip'  => '192.168.2.20',
                'countersigned_at'     => $now->copy()->subMonths(5)->toDateTimeString(),
                'issued_at'            => $now->copy()->subMonths(5)->toDateTimeString(),
                'effective_date'       => $now->copy()->subMonths(5)->toDateString(),
                'expires_at'           => null,
                'created_at'           => $now->copy()->subMonths(5)->toDateTimeString(),
                'updated_at'           => $now->copy()->subMonths(5)->toDateTimeString(),
                'is_supporting'        => 0,
                'auto_renew'           => 0,
                'notes'                => 'No expiry — perpetual mutual NDA.',
            ],

            // 3. Awaiting provider signature (pe) — BAA with Marcus
            [
                'id'               => 'doc_sarah_baa',
                'plan_id'          => $planSarah,
                'practitioner_id'  => 'p_sarah',
                'party_b_id'       => 'cs_marcus',
                'reference'        => 'BAA-2025-003',
                'title'            => 'Business Associate Agreement (HIPAA) — Marcus Williams',
                'doc_type'         => 'BAA',
                'category'         => 'pe',
                'status'           => 'pending_sign',
                'body'             => $clauses_pe_standard(['phi' => 'Read/Write', 'comp_model' => 'No compensation — BAA only', 'comp_rate' => '—', 'payment_cycle' => '—', 'notes' => 'Required under HIPAA for CS access to EHR system. Annual renewal required.']),
                'holder_steward_id'=> 'cs_marcus',
                'effective_date'   => $now->copy()->addDays(7)->toDateString(),
                'issued_at'        => $now->copy()->subDays(3)->toDateTimeString(),
                'created_at'       => $now->copy()->subDays(3)->toDateTimeString(),
                'updated_at'       => $now->copy()->subDays(3)->toDateTimeString(),
                'is_supporting'    => 0,
                'auto_renew'       => 0,
                'notes'            => 'Annual BAA renewal. Required for EHR access.',
            ],

            // 4. Awaiting countersignature (pe) — SOW with Priya
            [
                'id'               => 'doc_sarah_sow',
                'plan_id'          => $planSarah,
                'practitioner_id'  => 'p_sarah',
                'party_b_id'       => 'cs_priya',
                'reference'        => 'SOW-2025-004',
                'title'            => 'Statement of Work — Continuity Coverage Q4 2025',
                'doc_type'         => 'SOW',
                'category'         => 'pe',
                'status'           => 'countersign_pending',
                'body'             => $clauses_pe_standard(['auth_activities' => 'Priya to cover client sessions via telehealth during Sarah\'s medical leave Oct–Dec 2025. Max 15 client hours/week.', 'phi' => 'Read/Write', 'comp_model' => 'Per-Task Fee', 'comp_rate' => '$125/session', 'payment_cycle' => 'Bi-Weekly', 'notes' => 'Coverage period: Oct 1 – Dec 31, 2025.']),
                'holder_steward_id'=> 'cs_priya',
                'signed_by_id'     => 'p_sarah',
                'signature_name'   => 'Dr. Sarah Johnson, LPC',
                'signature_ip'     => '192.168.1.10',
                'signed_at'        => $now->copy()->subDays(2)->toDateTimeString(),
                'effective_date'   => $now->copy()->addDays(14)->toDateString(),
                'expires_at'       => $now->copy()->addMonths(5)->endOfMonth()->toDateTimeString(),
                'issued_at'        => $now->copy()->subDays(2)->toDateTimeString(),
                'created_at'       => $now->copy()->subDays(2)->toDateTimeString(),
                'updated_at'       => $now->copy()->subDays(2)->toDateTimeString(),
                'is_supporting'    => 0,
                'auto_renew'       => 0,
                'notes'            => 'Q4 coverage SOW. Priya countersignature pending.',
            ],

            // 5. Pending sign — ICA with Priya
            [
                'id'               => 'doc_sarah_ica_priya',
                'plan_id'          => $planSarah,
                'practitioner_id'  => 'p_sarah',
                'party_b_id'       => 'cs_priya',
                'reference'        => 'ICA-2025-005',
                'title'            => 'Independent Contractor Agreement — Priya Raman',
                'doc_type'         => 'ICA',
                'category'         => 'pe',
                'status'           => 'pending_sign',
                'body'             => $clauses_pe_standard(['phi' => 'Read-Only', 'comp_model' => 'Fixed Monthly Retainer', 'comp_rate' => '$1,800/mo', 'notes' => 'Alternate CS arrangement. Priya acts as backup when Marcus is unavailable.']),
                'holder_steward_id'=> 'cs_priya',
                'effective_date'   => $now->copy()->addDays(3)->toDateString(),
                'issued_at'        => $now->copy()->subDay()->toDateTimeString(),
                'created_at'       => $now->copy()->subDay()->toDateTimeString(),
                'updated_at'       => $now->copy()->subDay()->toDateTimeString(),
                'is_supporting'    => 0,
                'auto_renew'       => 0,
                'notes'            => 'Alternate CS ICA for backup coverage.',
            ],

            // ── pd: Provider & SS ──────────────────────────────────────────────

            // 6. Expiring soon SLA (pd) — Linda
            [
                'id'                   => 'doc_sarah_sla_expiring',
                'plan_id'              => $planSarah,
                'practitioner_id'      => 'p_sarah',
                'party_b_id'           => 'ss_linda',
                'reference'            => 'SLA-2024-006',
                'title'                => 'Support Steward Service Level Agreement — Linda Foster',
                'doc_type'             => 'SLA',
                'category'             => 'pd',
                'status'               => 'active',
                'body'                 => $clauses_pd_standard(['phi' => 'No PHI Access', 'comp_model' => 'Fixed Monthly Retainer', 'comp_rate' => '$1,200/mo', 'notes' => 'Linda handles all administrative functions. KPIs: 2-hour response time during business hours.']),
                'holder_steward_id'    => 'ss_linda',
                'signed_by_id'         => 'p_sarah',
                'signature_name'       => 'Dr. Sarah Johnson, LPC',
                'signature_ip'         => '192.168.1.10',
                'signed_at'            => $now->copy()->subMonths(11)->toDateTimeString(),
                'countersigned_by_id'  => 'ss_linda',
                'countersignature_name'=> 'Linda Foster, RN',
                'countersignature_ip'  => '192.168.3.30',
                'countersigned_at'     => $now->copy()->subMonths(11)->addDay()->toDateTimeString(),
                'issued_at'            => $now->copy()->subMonths(11)->toDateTimeString(),
                'effective_date'       => $now->copy()->subMonths(11)->toDateString(),
                'expires_at'           => $now->copy()->addDays(18)->toDateTimeString(),
                'created_at'           => $now->copy()->subMonths(12)->toDateTimeString(),
                'updated_at'           => $now->copy()->subMonths(11)->toDateTimeString(),
                'is_supporting'        => 0,
                'auto_renew'           => 0,
                'notes'                => 'Expiring soon — renewal required before end of month.',
            ],

            // 7. Fully executed NDA (pd) — Linda
            [
                'id'                   => 'doc_sarah_nda_linda',
                'plan_id'              => $planSarah,
                'practitioner_id'      => 'p_sarah',
                'party_b_id'           => 'ss_linda',
                'reference'            => 'NDA-2024-007',
                'title'                => 'Non-Disclosure Agreement — Linda Foster',
                'doc_type'             => 'NDA',
                'category'             => 'pd',
                'status'               => 'fully_executed',
                'body'                 => $clauses_pd_standard(['phi' => 'No PHI Access', 'comp_model' => 'No compensation — NDA only', 'comp_rate' => '—', 'notes' => 'Covers all business and operational information shared during engagement.']),
                'holder_steward_id'    => 'ss_linda',
                'signed_by_id'         => 'p_sarah',
                'signature_name'       => 'Dr. Sarah Johnson, LPC',
                'signature_ip'         => '192.168.1.10',
                'signed_at'            => $now->copy()->subMonths(11)->subDays(2)->toDateTimeString(),
                'countersigned_by_id'  => 'ss_linda',
                'countersignature_name'=> 'Linda Foster, RN',
                'countersignature_ip'  => '192.168.3.30',
                'countersigned_at'     => $now->copy()->subMonths(11)->toDateTimeString(),
                'issued_at'            => $now->copy()->subMonths(11)->toDateTimeString(),
                'effective_date'       => $now->copy()->subMonths(11)->toDateString(),
                'created_at'           => $now->copy()->subMonths(11)->toDateTimeString(),
                'updated_at'           => $now->copy()->subMonths(11)->toDateTimeString(),
                'is_supporting'        => 0,
                'auto_renew'           => 0,
                'notes'                => 'Mutual NDA signed alongside SLA.',
            ],

            // 8. Awaiting countersignature (pd) — SLA with James Carter
            [
                'id'               => 'doc_sarah_sla_james',
                'plan_id'          => $planSarah,
                'practitioner_id'  => 'p_sarah',
                'party_b_id'       => 'ss_james',
                'reference'        => 'SLA-2025-008',
                'title'            => 'Support Steward SLA — James Carter',
                'doc_type'         => 'SLA',
                'category'         => 'pd',
                'status'           => 'countersign_pending',
                'body'             => $clauses_pd_standard(['comp_rate' => '$950/mo', 'notes' => 'James handles billing inquiries and insurance verification. Backup SS role.']),
                'holder_steward_id'=> 'ss_james',
                'signed_by_id'     => 'p_sarah',
                'signature_name'   => 'Dr. Sarah Johnson, LPC',
                'signature_ip'     => '192.168.1.10',
                'signed_at'        => $now->copy()->subDays(4)->toDateTimeString(),
                'effective_date'   => $now->copy()->addDays(10)->toDateString(),
                'expires_at'       => $now->copy()->addYear()->toDateTimeString(),
                'issued_at'        => $now->copy()->subDays(4)->toDateTimeString(),
                'created_at'       => $now->copy()->subDays(4)->toDateTimeString(),
                'updated_at'       => $now->copy()->subDays(4)->toDateTimeString(),
                'is_supporting'    => 0,
                'auto_renew'       => 0,
                'notes'            => 'Backup SS arrangement. James countersignature pending.',
            ],

            // ── tri: Tri-Party ─────────────────────────────────────────────────

            // 9. Fully executed tri-party MSA — Marcus + Linda
            [
                'id'                   => 'doc_sarah_tri_msa',
                'plan_id'              => $planSarah,
                'practitioner_id'      => 'p_sarah',
                'party_b_id'           => 'cs_marcus',
                'party_c_id'           => 'ss_linda',
                'reference'            => 'MSA-TRI-2024-009',
                'title'                => 'Tri-Party Master Service Agreement — Marcus Williams & Linda Foster',
                'doc_type'             => 'MSA',
                'category'             => 'tri',
                'status'               => 'fully_executed',
                'body'                 => $clauses_tri(['comp_rate' => 'CS: $2,500/mo | SS: $1,200/mo', 'notes' => 'Binding agreement establishing roles and responsibilities for all three parties during any continuity event.']),
                'holder_steward_id'    => 'cs_marcus',
                'signed_by_id'         => 'p_sarah',
                'signature_name'       => 'Dr. Sarah Johnson, LPC',
                'signature_ip'         => '192.168.1.10',
                'signed_at'            => $now->copy()->subMonths(4)->subDays(3)->toDateTimeString(),
                'countersigned_by_id'  => 'cs_marcus',
                'countersignature_name'=> 'Marcus Williams, MSW, LICSW',
                'countersignature_ip'  => '192.168.2.20',
                'countersigned_at'     => $now->copy()->subMonths(4)->toDateTimeString(),
                'issued_at'            => $now->copy()->subMonths(4)->toDateTimeString(),
                'effective_date'       => $now->copy()->subMonths(4)->toDateString(),
                'expires_at'           => $now->copy()->addMonths(8)->toDateTimeString(),
                'created_at'           => $now->copy()->subMonths(4)->toDateTimeString(),
                'updated_at'           => $now->copy()->subMonths(4)->toDateTimeString(),
                'is_supporting'        => 0,
                'auto_renew'           => 0,
                'notes'                => 'Comprehensive tri-party agreement covering all continuity scenarios.',
            ],

            // 10. Awaiting countersign — Tri-party NDA — Marcus + Linda
            [
                'id'               => 'doc_sarah_tri_nda',
                'plan_id'          => $planSarah,
                'practitioner_id'  => 'p_sarah',
                'party_b_id'       => 'cs_marcus',
                'party_c_id'       => 'ss_linda',
                'reference'        => 'NDA-TRI-2025-010',
                'title'            => 'Tri-Party Non-Disclosure Agreement',
                'doc_type'         => 'NDA',
                'category'         => 'tri',
                'status'           => 'countersign_pending',
                'body'             => $clauses_tri(['notes' => 'Tri-party NDA covering confidential information shared among all three parties.']),
                'holder_steward_id'=> 'cs_marcus',
                'signed_by_id'     => 'p_sarah',
                'signature_name'   => 'Dr. Sarah Johnson, LPC',
                'signature_ip'     => '192.168.1.10',
                'signed_at'        => $now->copy()->subDays(5)->toDateTimeString(),
                'effective_date'   => $now->copy()->addDays(5)->toDateString(),
                'issued_at'        => $now->copy()->subDays(5)->toDateTimeString(),
                'created_at'       => $now->copy()->subDays(5)->toDateTimeString(),
                'updated_at'       => $now->copy()->subDays(5)->toDateTimeString(),
                'is_supporting'    => 0,
                'auto_renew'       => 0,
                'notes'            => 'Tri-party NDA. Awaiting Marcus and Linda countersignatures.',
            ],

            // ── Amendment ─────────────────────────────────────────────────────

            // 11. Fee amendment to the MSA (pe) — Marcus
            [
                'id'                   => 'doc_sarah_fee_amendment',
                'plan_id'              => $planSarah,
                'practitioner_id'      => 'p_sarah',
                'party_b_id'           => 'cs_marcus',
                'reference'            => 'AMN-2025-001',
                'title'                => 'Fee Amendment — MSA-2024-001 (Marcus Williams)',
                'doc_type'             => 'fee_amendment',
                'category'             => 'pe',
                'status'               => 'countersign_pending',
                'body'                 => $body([
                    1 => ['title' => 'Scope of Services & Delegation of Authority', 'fields' => [
                        'Authorized activities' => 'Amendment to compensation clause of MSA-2024-001. All other terms remain unchanged.',
                        'Explicit exclusions' => 'This amendment covers compensation only — no scope changes.',
                    ]],
                    2 => ['title' => 'Confidentiality & PHI Obligations (HIPAA)', 'fields' => [
                        'PHI Access Level' => 'Read/Write (unchanged)',
                        'Confidentiality Duration' => 'Duration of agreement only (unchanged)',
                    ]],
                    3 => ['title' => 'Compensation & Fee Structure', 'fields' => [
                        'Model' => 'Fixed Monthly Retainer',
                        'Amount / Rate' => '$2,800/mo (revised from $2,500/mo — effective next quarter)',
                        'Payment Cycle' => 'Monthly',
                    ]],
                    4 => ['title' => 'Termination & Exit Provisions', 'fields' => [
                        'Notice Period' => '30 days (unchanged)',
                        'Immediate Termination Grounds' => 'HIPAA breach, fraud, gross negligence, loss of licensure',
                    ]],
                    5 => ['title' => 'Liability, Indemnification & Insurance', 'fields' => [
                        'Liability Cap' => 'Capped at 3 months fees paid (unchanged)',
                        'Insurance Requirement' => 'Professional Liability min $1M / $3M aggregate (unchanged)',
                    ]],
                ], 'Fee increase of $300/mo effective next billing cycle. All other terms of MSA-2024-001 remain in full force.'),
                'amends_document_id'   => 'doc_sarah_designation',
                'holder_steward_id'    => 'cs_marcus',
                'signed_by_id'         => 'p_sarah',
                'signature_name'       => 'Dr. Sarah Johnson, LPC',
                'signature_ip'         => '127.0.0.1',
                'signed_at'            => $now->copy()->subDays(3)->toDateTimeString(),
                'effective_date'       => $now->copy()->addDays(28)->toDateString(),
                'issued_at'            => $now->copy()->subDays(3)->toDateTimeString(),
                'created_at'           => $now->copy()->subDays(3)->toDateTimeString(),
                'updated_at'           => $now->copy()->subDays(3)->toDateTimeString(),
                'is_supporting'        => 0,
                'auto_renew'           => 0,
                'notes'                => 'Fee amendment Q1 2026. Marcus agreed in principle. Formal countersignature pending.',
            ],

            // ── Terminated ────────────────────────────────────────────────────

            // 12. Terminated agreement — old CS arrangement
            [
                'id'               => 'doc_sarah_terminated',
                'plan_id'          => $planSarah,
                'practitioner_id'  => 'p_sarah',
                'party_b_id'       => 'cs_alternate',
                'reference'        => 'MSA-2023-TERM',
                'title'            => 'Continuity Agreement — James Wilson (Terminated)',
                'doc_type'         => 'MSA',
                'category'         => 'pe',
                'status'           => 'terminated',
                'body'             => $clauses_pe_standard(['comp_rate' => '$1,800/mo', 'notes' => 'Original arrangement with James Wilson prior to Marcus engagement.']),
                'holder_steward_id'=> 'cs_alternate',
                'signed_by_id'     => 'p_sarah',
                'signature_name'   => 'Dr. Sarah Johnson, LPC',
                'signature_ip'     => '192.168.1.10',
                'signed_at'        => $now->copy()->subYears(1)->subDays(5)->toDateTimeString(),
                'countersigned_by_id'  => 'cs_alternate',
                'countersignature_name'=> 'James Wilson',
                'countersignature_ip'  => '192.168.5.50',
                'countersigned_at'     => $now->copy()->subYears(1)->toDateTimeString(),
                'issued_at'            => $now->copy()->subYears(1)->toDateTimeString(),
                'effective_date'       => $now->copy()->subYears(1)->toDateString(),
                'archived_at'          => $now->copy()->subMonths(7)->toDateTimeString(),
                'created_at'           => $now->copy()->subYears(1)->toDateTimeString(),
                'updated_at'           => $now->copy()->subMonths(7)->toDateTimeString(),
                'is_supporting'        => 0,
                'auto_renew'           => 0,
                'notes'                => 'Terminated due to James Wilson resignation. Replaced by Marcus Williams MSA.',
            ],

            // 13. Archived — old version 1 designation
            [
                'id'               => 'doc_sarah_archived',
                'plan_id'          => $planSarah,
                'practitioner_id'  => 'p_sarah',
                'reference'        => 'DOC-2023-001',
                'title'            => 'Original Steward Designation — Version 1 (Archived)',
                'doc_type'         => 'MSA',
                'category'         => 'pe',
                'status'           => 'archived',
                'body'             => $clauses_pe_standard(['comp_rate' => '$2,000/mo', 'notes' => 'Version 1. Superseded by MSA-2024-001.']),
                'archived_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'created_at'       => $now->copy()->subMonths(8)->toDateTimeString(),
                'updated_at'       => $now->copy()->subMonths(6)->toDateTimeString(),
                'is_supporting'    => 0,
                'auto_renew'       => 0,
                'notes'            => 'Archived — superseded by MSA-2024-001.',
            ],

            // ── CS Engagement (required by plan) ──────────────────────────────

            // 14. CS Engagement Agreement — fully executed
            [
                'id'                   => 'doc_sarah_cs_engagement',
                'plan_id'              => $planSarah,
                'practitioner_id'      => 'p_sarah',
                'party_b_id'           => 'cs_marcus',
                'reference'            => 'CEA-2024-001',
                'title'                => 'CS Engagement Agreement — Marcus Williams',
                'doc_type'             => 'cs_engagement_agreement',
                'category'             => 'pe',
                'status'               => 'fully_executed',
                'body'                 => $clauses_pe_standard(['phi' => 'Read/Write', 'comp_rate' => '$2,500/mo', 'notes' => 'Official CS engagement. Satisfies Plan Section 7 requirements.']),
                'holder_steward_id'    => 'cs_marcus',
                'signed_by_id'         => 'p_sarah',
                'signature_name'       => 'Dr. Sarah Johnson, LPC',
                'signature_ip'         => '127.0.0.1',
                'signed_at'            => $now->copy()->subMonths(6)->toDateTimeString(),
                'countersigned_by_id'  => 'cs_marcus',
                'countersignature_name'=> 'Marcus Williams, MSW, LICSW',
                'countersignature_ip'  => '127.0.0.1',
                'countersigned_at'     => $now->copy()->subMonths(6)->addDay()->toDateTimeString(),
                'issued_at'            => $now->copy()->subMonths(6)->toDateTimeString(),
                'effective_date'       => $now->copy()->subMonths(6)->toDateString(),
                'created_at'           => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'           => $now->copy()->subMonths(6)->addDay()->toDateTimeString(),
                'is_supporting'        => 0,
                'auto_renew'           => 0,
                'notes'                => 'CS engagement agreement covering primary steward role.',
            ],

            // ── Supporting Documents ───────────────────────────────────────────

            // 15. Supporting — compensation addendum
            [
                'id'               => 'doc_sarah_support_1',
                'plan_id'          => $planSarah,
                'practitioner_id'  => 'p_sarah',
                'reference'        => 'SUP-2024-001',
                'title'            => 'Continuity Plan Amendment — Compensation Addendum',
                'doc_type'         => 'plan_amendment',
                'status'           => 'active',
                'holder_steward_id'=> 'cs_marcus',
                'related_to'       => 'Continuity Steward Agreement',
                'is_supporting'    => 1,
                'file_ref'         => 'docs/plan_sarah/supporting/amendment_comp.pdf',
                'created_at'       => $now->copy()->subDays(14)->toDateTimeString(),
                'updated_at'       => $now->copy()->subDays(14)->toDateTimeString(),
                'auto_renew'       => 0,
                'notes'            => 'Addendum approved by both parties. Pending formal execution.',
            ],

            // 16. Supporting — client notification template
            [
                'id'               => 'doc_sarah_support_2',
                'plan_id'          => $planSarah,
                'practitioner_id'  => 'p_sarah',
                'reference'        => 'SUP-2024-002',
                'title'            => 'Client Notification Letter Template',
                'doc_type'         => 'client_notification',
                'status'           => 'active',
                'holder_steward_id'=> 'cs_marcus',
                'related_to'       => 'Continuity Plan',
                'is_supporting'    => 1,
                'file_ref'         => 'docs/plan_sarah/supporting/client_notification.pdf',
                'created_at'       => $now->copy()->subMonths(5)->toDateTimeString(),
                'updated_at'       => $now->copy()->subMonths(5)->toDateTimeString(),
                'auto_renew'       => 0,
                'notes'            => 'Template for notifying clients during any continuity event.',
            ],

            // 17. Supporting — attorney letter
            [
                'id'               => 'doc_sarah_support_3',
                'plan_id'          => $planSarah,
                'practitioner_id'  => 'p_sarah',
                'reference'        => 'SUP-2025-003',
                'title'            => 'Attorney Letter — Authorization for Steward Access',
                'doc_type'         => 'attorney_letter',
                'status'           => 'active',
                'related_to'       => 'Continuity Steward Agreement',
                'is_supporting'    => 1,
                'file_ref'         => 'docs/plan_sarah/supporting/attorney_letter.pdf',
                'created_at'       => $now->copy()->subMonths(3)->toDateTimeString(),
                'updated_at'       => $now->copy()->subMonths(3)->toDateTimeString(),
                'auto_renew'       => 0,
                'notes'            => 'Legal counsel authorization for steward access to practice records.',
            ],

            // ── p_maria documents ──────────────────────────────────────────────

            // 18. Maria — pending sign MSA
            [
                'id'               => 'doc_maria_msa',
                'plan_id'          => $planMaria,
                'practitioner_id'  => 'p_maria',
                'party_b_id'       => 'cs_priya',
                'reference'        => 'MSA-2025-M01',
                'title'            => 'Continuity Steward MSA — Priya Raman',
                'doc_type'         => 'MSA',
                'category'         => 'pe',
                'status'           => 'pending_sign',
                'body'             => $clauses_pe_standard(['comp_rate' => '$2,200/mo', 'phi' => 'Read-Only']),
                'holder_steward_id'=> 'cs_priya',
                'effective_date'   => $now->copy()->addDays(5)->toDateString(),
                'issued_at'        => $now->copy()->subDays(7)->toDateTimeString(),
                'created_at'       => $now->copy()->subDays(7)->toDateTimeString(),
                'updated_at'       => $now->copy()->subDays(3)->toDateTimeString(),
                'is_supporting'    => 0,
                'auto_renew'       => 0,
                'notes'            => 'New CS arrangement for Maria.',
            ],

            // ── p_david documents ──────────────────────────────────────────────

            // 19. David — fully executed MSA
            [
                'id'                   => 'doc_david_msa',
                'plan_id'              => $planDavid,
                'practitioner_id'      => 'p_david',
                'party_b_id'           => 'cs_marcus',
                'reference'            => 'MSA-2024-D01',
                'title'                => 'CS Master Service Agreement — Marcus Williams',
                'doc_type'             => 'MSA',
                'category'             => 'pe',
                'status'               => 'fully_executed',
                'body'                 => $clauses_pe_standard(['comp_rate' => '$3,000/mo', 'phi' => 'Read-Only', 'notes' => 'David Chen practice. Marcus covers both David and Sarah — separate agreements.']),
                'holder_steward_id'    => 'cs_marcus',
                'signed_by_id'         => 'p_david',
                'signature_name'       => 'David Chen, PhD',
                'signature_ip'         => '192.168.4.40',
                'signed_at'            => $now->copy()->subMonths(3)->subDays(2)->toDateTimeString(),
                'countersigned_by_id'  => 'cs_marcus',
                'countersignature_name'=> 'Marcus Williams, MSW, LICSW',
                'countersignature_ip'  => '192.168.2.20',
                'countersigned_at'     => $now->copy()->subMonths(3)->toDateTimeString(),
                'issued_at'            => $now->copy()->subMonths(3)->toDateTimeString(),
                'effective_date'       => $now->copy()->subMonths(3)->toDateString(),
                'expires_at'           => $now->copy()->addMonths(9)->toDateTimeString(),
                'created_at'           => $now->copy()->subMonths(3)->toDateTimeString(),
                'updated_at'           => $now->copy()->subMonths(3)->toDateTimeString(),
                'is_supporting'        => 0,
                'auto_renew'           => 0,
                'notes'                => 'David Chen CS agreement.',
            ],
        ];

        $defaults = [
            'party_b_id'             => null,
            'party_c_id'             => null,
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
            if (empty($doc['plan_id'])) continue;
            $doc = array_merge($defaults, $doc);
            DB::table('continuity_documents')->updateOrInsert(['id' => $doc['id']], $doc);
        }

        // ── document_signatures ────────────────────────────────────────────────
        $docIds = array_column($documents, 'id');
        DB::table('document_signatures')->whereIn('document_id', $docIds)->delete();

        if (!$planSarah) return;

        $sigs = [];
        $sigDocs = [
            ['doc_id' => 'doc_sarah_designation',   'p_signed' => $now->copy()->subMonths(6)->subDays(2), 'cs_id' => 'cs_marcus', 'cs_name' => 'Marcus Williams, MSW, LICSW', 'cs_signed' => $now->copy()->subMonths(6)],
            ['doc_id' => 'doc_sarah_nda',            'p_signed' => $now->copy()->subMonths(5)->subDay(),   'cs_id' => 'cs_marcus', 'cs_name' => 'Marcus Williams, MSW, LICSW', 'cs_signed' => $now->copy()->subMonths(5)],
            ['doc_id' => 'doc_sarah_sow',            'p_signed' => $now->copy()->subDays(2)],
            ['doc_id' => 'doc_sarah_ica_priya',      'p_signed' => $now->copy()->subDay()],
            ['doc_id' => 'doc_sarah_baa',            'p_signed' => null],
            ['doc_id' => 'doc_sarah_sla_expiring',   'p_signed' => $now->copy()->subMonths(11), 'cs_id' => 'ss_linda', 'cs_name' => 'Linda Foster, RN', 'cs_signed' => $now->copy()->subMonths(11)->addDay()],
            ['doc_id' => 'doc_sarah_nda_linda',      'p_signed' => $now->copy()->subMonths(11)->subDays(2), 'cs_id' => 'ss_linda', 'cs_name' => 'Linda Foster, RN', 'cs_signed' => $now->copy()->subMonths(11)],
            ['doc_id' => 'doc_sarah_sla_james',      'p_signed' => $now->copy()->subDays(4)],
            ['doc_id' => 'doc_sarah_tri_msa',        'p_signed' => $now->copy()->subMonths(4)->subDays(3), 'cs_id' => 'cs_marcus', 'cs_name' => 'Marcus Williams, MSW, LICSW', 'cs_signed' => $now->copy()->subMonths(4)],
            ['doc_id' => 'doc_sarah_tri_nda',        'p_signed' => $now->copy()->subDays(5)],
            ['doc_id' => 'doc_sarah_fee_amendment',  'p_signed' => $now->copy()->subDays(3)],
            ['doc_id' => 'doc_sarah_terminated',     'p_signed' => $now->copy()->subYears(1)->subDays(5), 'cs_id' => 'cs_alternate', 'cs_name' => 'James Wilson', 'cs_signed' => $now->copy()->subYears(1)],
            ['doc_id' => 'doc_sarah_cs_engagement',  'p_signed' => $now->copy()->subMonths(6), 'cs_id' => 'cs_marcus', 'cs_name' => 'Marcus Williams, MSW, LICSW', 'cs_signed' => $now->copy()->subMonths(6)->addDay()],
            ['doc_id' => 'doc_david_msa',            'p_id' => 'p_david', 'p_name' => 'David Chen, PhD', 'p_signed' => $now->copy()->subMonths(3)->subDays(2), 'cs_id' => 'cs_marcus', 'cs_name' => 'Marcus Williams, MSW, LICSW', 'cs_signed' => $now->copy()->subMonths(3)],
        ];

        foreach ($sigDocs as $s) {
            if (!empty($s['p_signed'])) {
                $sigs[] = [
                    'id'             => (string) Str::uuid(),
                    'document_id'    => $s['doc_id'],
                    'signer_id'      => $s['p_id'] ?? 'p_sarah',
                    'signer_role'    => 'practitioner',
                    'signature_name' => $s['p_name'] ?? 'Dr. Sarah Johnson, LPC',
                    'signature_ip'   => '192.168.1.10',
                    'signed_at'      => $s['p_signed']->toDateTimeString(),
                    'created_at'     => $s['p_signed']->toDateTimeString(),
                ];
            }
            if (!empty($s['cs_id']) && !empty($s['cs_signed'])) {
                $sigs[] = [
                    'id'             => (string) Str::uuid(),
                    'document_id'    => $s['doc_id'],
                    'signer_id'      => $s['cs_id'],
                    'signer_role'    => 'continuity_steward',
                    'signature_name' => $s['cs_name'],
                    'signature_ip'   => '192.168.2.20',
                    'signed_at'      => $s['cs_signed']->toDateTimeString(),
                    'created_at'     => $s['cs_signed']->toDateTimeString(),
                ];
            }
        }

        foreach ($sigs as $sig) {
            DB::table('document_signatures')->insertOrIgnore($sig);
        }
    }
}
