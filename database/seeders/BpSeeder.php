<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BpSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── BP Jobs ────────────────────────────────────────────────────────
        $jobs = [
            [
                'id'                 => 'job_maria_billing',
                'practitioner_id'    => 'p_maria',
                'title'              => 'Medical Billing & Revenue Cycle Management',
                'category'           => 'billing',
                'description'        => 'Seeking experienced billing specialist for busy couples therapy practice. Approximately 60 sessions/month across 3 insurance panels. Must have experience with mental health billing (CPT codes 90837, 90847).',
                'budget_type'        => 'retainer',
                'budget_amount_cents'=> 150000,
                'location_pref'      => 'remote',
                'status'             => 'filled',
                'is_urgent'          => 0,
                'proposals_count'    => 4,
                'posted_at'          => $now->copy()->subMonths(3),
                'closes_at'          => $now->copy()->subMonths(2),
                'created_at'         => $now->copy()->subMonths(3),
                'updated_at'         => $now->copy()->subMonths(2),
            ],
            [
                'id'                 => 'job_sarah_cred',
                'practitioner_id'    => 'p_sarah',
                'title'              => 'Insurance Credentialing — 3 New Panels',
                'category'           => 'credentialing',
                'description'        => 'Need credentialing assistance for Aetna, Humana, and Optum. Currently paneled with Blue Cross and Cigna. Practice in Texas.',
                'budget_type'        => 'fixed',
                'budget_amount_cents'=> 75000,
                'location_pref'      => 'remote',
                'status'             => 'open',
                'is_urgent'          => 1,
                'proposals_count'    => 2,
                'posted_at'          => $now->copy()->subDays(5),
                'closes_at'          => $now->copy()->addDays(25),
                'created_at'         => $now->copy()->subDays(5),
                'updated_at'         => $now->copy()->subDays(1),
            ],
            [
                'id'                 => 'job_david_billing',
                'practitioner_id'    => 'p_david',
                'title'              => 'Part-Time Billing Support — Solo Practice',
                'category'           => 'billing',
                'description'        => 'Small solo practice needing part-time billing assistance. About 20 sessions per month, primarily self-pay with some insurance.',
                'budget_type'        => 'hourly',
                'budget_amount_cents'=> 5000,
                'location_pref'      => 'remote',
                'status'             => 'open',
                'is_urgent'          => 0,
                'proposals_count'    => 1,
                'posted_at'          => $now->copy()->subDays(8),
                'closes_at'          => $now->copy()->addDays(22),
                'created_at'         => $now->copy()->subDays(8),
                'updated_at'         => $now->copy()->subDays(2),
            ],
            [
                'id'                 => 'job_sarah_admin',
                'practitioner_id'    => 'p_sarah',
                'title'              => 'Virtual Administrative Support',
                'category'           => 'admin',
                'description'        => 'Looking for virtual admin support — scheduling, insurance verification, client intake coordination.',
                'budget_type'        => 'hourly',
                'budget_amount_cents'=> 4500,
                'location_pref'      => 'remote',
                'status'             => 'paused',
                'is_urgent'          => 0,
                'proposals_count'    => 0,
                'posted_at'          => $now->copy()->subDays(14),
                'closes_at'          => null,
                'created_at'         => $now->copy()->subDays(14),
                'updated_at'         => $now->copy()->subDays(7),
            ],
        ];

        foreach ($jobs as $j) {
            $j['deleted_at'] = null;
            DB::table('bp_jobs')->updateOrInsert(['id' => $j['id']], $j);
        }

        // ── BP Proposals ───────────────────────────────────────────────────
        $proposals = [
            // bp_acme × job_maria_billing — ACCEPTED (leads to contract)
            [
                'id'                  => 'prop_acme_maria',
                'job_id'              => 'job_maria_billing',
                'bp_id'               => 'bp_acme',
                'cover_letter'        => 'Acme Health Services has 15 years of experience in behavioral health billing. We have managed revenue cycles for over 50 mental health practices and maintain a 98.2% clean claims rate. Our team of 3 dedicated billers will handle your practice end-to-end.',
                'proposed_rate_cents' => 145000,
                'proposed_rate_type'  => 'retainer',
                'status'              => 'accepted',
                'submitted_at'        => $now->copy()->subMonths(3)->addDays(2),
                'responded_at'        => $now->copy()->subMonths(2)->subDays(25),
                'created_at'          => $now->copy()->subMonths(3)->addDays(2),
                'updated_at'          => $now->copy()->subMonths(2)->subDays(25),
            ],
            // bp_jamal × job_sarah_cred — pending
            [
                'id'                  => 'prop_jamal_sarah_cred',
                'job_id'              => 'job_sarah_cred',
                'bp_id'               => 'bp_jamal',
                'cover_letter'        => 'I specialize in Texas mental health provider credentialing. I\'ve successfully credentialed 30+ providers with Aetna, Humana, and Optum. Timeline: 60-90 days per panel. Fixed fee includes all application fees and follow-up.',
                'proposed_rate_cents' => 70000,
                'proposed_rate_type'  => 'fixed',
                'status'              => 'pending',
                'submitted_at'        => $now->copy()->subDays(3),
                'responded_at'        => null,
                'created_at'          => $now->copy()->subDays(3),
                'updated_at'          => $now->copy()->subDays(3),
            ],
            // bp_acme × job_sarah_cred — under_review
            [
                'id'                  => 'prop_acme_sarah_cred',
                'job_id'              => 'job_sarah_cred',
                'bp_id'               => 'bp_acme',
                'cover_letter'        => 'Acme offers full credentialing services for all major Texas payers. Our credentialing team has maintained a 100% success rate for mental health providers. We can begin immediately.',
                'proposed_rate_cents' => 90000,
                'proposed_rate_type'  => 'fixed',
                'status'              => 'under_review',
                'submitted_at'        => $now->copy()->subDays(4),
                'responded_at'        => null,
                'created_at'          => $now->copy()->subDays(4),
                'updated_at'          => $now->copy()->subDays(2),
            ],
            // bp_jamal × job_david_billing — pending
            [
                'id'                  => 'prop_jamal_david',
                'job_id'              => 'job_david_billing',
                'bp_id'               => 'bp_jamal',
                'cover_letter'        => 'I provide flexible billing support for solo practitioners. My rate includes claim submission, denial management, and monthly reporting. Minimum 10 hours/month.',
                'proposed_rate_cents' => 4800,
                'proposed_rate_type'  => 'hourly',
                'status'              => 'pending',
                'submitted_at'        => $now->copy()->subDays(6),
                'responded_at'        => null,
                'created_at'          => $now->copy()->subDays(6),
                'updated_at'          => $now->copy()->subDays(6),
            ],
            // bp_jamal × old job — declined (edge case)
            [
                'id'                  => 'prop_jamal_sarah_old',
                'job_id'              => 'job_sarah_admin',
                'bp_id'               => 'bp_jamal',
                'cover_letter'        => 'I can provide virtual admin support alongside billing services.',
                'proposed_rate_cents' => 4200,
                'proposed_rate_type'  => 'hourly',
                'status'              => 'declined',
                'submitted_at'        => $now->copy()->subDays(12),
                'responded_at'        => $now->copy()->subDays(9),
                'created_at'          => $now->copy()->subDays(12),
                'updated_at'          => $now->copy()->subDays(9),
            ],
        ];

        foreach ($proposals as $p) {
            $p['deleted_at'] = null;
            DB::table('bp_proposals')->updateOrInsert(['id' => $p['id']], $p);
        }

        // ── BP Contracts ───────────────────────────────────────────────────
        $contracts = [
            // bp_acme × p_maria — active
            [
                'id'               => 'contract_acme_maria',
                'job_id'           => 'job_maria_billing',
                'proposal_id'      => 'prop_acme_maria',
                'practitioner_id'  => 'p_maria',
                'bp_id'            => 'bp_acme',
                'title'            => 'Revenue Cycle Management — Santos Integrative Wellness',
                'status'           => 'active',
                'total_value_cents'=> 1740000, // 12 months × $1,450/mo
                'signed_at'        => $now->copy()->subMonths(2)->subDays(23),
                'started_at'       => $now->copy()->subMonths(2)->subDays(20),
                'completed_at'     => null,
                'cancelled_at'     => null,
                'created_at'       => $now->copy()->subMonths(2)->subDays(25),
                'updated_at'       => $now->copy()->subDays(1),
            ],
        ];

        foreach ($contracts as $c) {
            $c['deleted_at'] = null;
            DB::table('bp_contracts')->updateOrInsert(['id' => $c['id']], $c);
        }

        // Contract meta
        DB::table('contract_meta')->insert([
            'id'          => (string) Str::uuid(),
            'contract_id' => 'contract_acme_maria',
            'meta_key'    => 'scope_summary',
            'meta_value'  => 'Monthly billing, denial management, EOB posting, monthly revenue reports, quarterly reviews.',
            'meta_type'   => 'string',
        ]);

        // ── BP Milestones ──────────────────────────────────────────────────
        $milestones = [
            // approved (paid out)
            [
                'id'                => 'ms_acme_maria_1',
                'contract_id'       => 'contract_acme_maria',
                'title'             => 'Month 1 — Onboarding & Initial Billing Submission',
                'description'       => 'Complete practice onboarding, EHR integration, and submit first batch of claims.',
                'amount_cents'      => 145000,
                'status'            => 'approved',
                'due_at'            => $now->copy()->subMonths(1)->subDays(20),
                'submitted_at'      => $now->copy()->subMonths(1)->subDays(22),
                'approved_at'       => $now->copy()->subMonths(1)->subDays(19),
                'sort_order'        => 1,
                'created_at'        => $now->copy()->subMonths(2)->subDays(20),
                'updated_at'        => $now->copy()->subMonths(1)->subDays(19),
            ],
            // submitted (pending approval)
            [
                'id'                => 'ms_acme_maria_2',
                'contract_id'       => 'contract_acme_maria',
                'title'             => 'Month 2 — Ongoing Billing + Denial Resolution',
                'description'       => 'Process all claims from month 2, resolve denials from month 1, post EOBs.',
                'amount_cents'      => 145000,
                'status'            => 'submitted',
                'due_at'            => $now->copy()->subDays(5),
                'submitted_at'      => $now->copy()->subDays(7),
                'approved_at'       => null,
                'sort_order'        => 2,
                'created_at'        => $now->copy()->subMonths(2)->subDays(20),
                'updated_at'        => $now->copy()->subDays(7),
            ],
            // pending — OVERDUE
            [
                'id'                => 'ms_acme_maria_3',
                'contract_id'       => 'contract_acme_maria',
                'title'             => 'Month 3 — Quarterly Performance Report',
                'description'       => 'Deliver Q1 revenue cycle performance report including denial rate, collection rate, and AR aging.',
                'amount_cents'      => 145000,
                'status'            => 'pending',
                'due_at'            => $now->copy()->subDays(3), // OVERDUE
                'submitted_at'      => null,
                'approved_at'       => null,
                'sort_order'        => 3,
                'created_at'        => $now->copy()->subMonths(2)->subDays(20),
                'updated_at'        => $now->copy()->subMonths(2)->subDays(20),
            ],
        ];

        foreach ($milestones as $m) {
            $m = array_merge(['assigned_member_id' => null, 'deleted_at' => null], $m);
            DB::table('bp_milestones')->updateOrInsert(['id' => $m['id']], $m);
        }

        // ── BP Team Members ────────────────────────────────────────────────
        DB::table('bp_team_members')->updateOrInsert(['id' => 'btm_nexus_tanya'], [
            'id'              => 'btm_nexus_tanya',
            'agency_id'       => 'bp_team_owner',
            'member_id'       => 'bp_team_member',
            'permission_role' => 'specialist',
            'department'      => 'Operations',
            'status'          => 'active',
            'joined_at'       => now()->subMonths(3),
            'created_at'      => now()->subMonths(3),
            'updated_at'      => now()->subMonths(3),
            'deleted_at'      => null,
        ]);

        // Also seed acme's own hypothetical internal member (bp_jamal as contractor)
        DB::table('bp_saved_jobs')->insert([
            'id'         => (string) Str::uuid(),
            'bp_id'      => 'bp_jamal',
            'job_id'     => 'job_sarah_cred',
            'created_at' => now()->subDays(3)->toDateTimeString(),
        ]);
    }
}
