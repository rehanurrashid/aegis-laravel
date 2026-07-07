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

        // ── BP Jobs (9 total — 5 for p_sarah, 2 for p_maria, 2 for p_david) ──
        $jobs = [
            // ── p_sarah jobs ─────────────────────────────────────────────
            [
                'id'                  => 'job_sarah_cred',
                'practitioner_id'     => 'p_sarah',
                'title'               => 'Insurance Credentialing — 3 New Panels',
                'category'            => 'credentialing',
                'job_type'            => 'one_time',
                'location_pref'       => 'remote',
                'description'         => 'Need credentialing assistance for Aetna, Humana, and Optum. Currently paneled with Blue Cross and Cigna. Practice in Texas. Need someone familiar with Texas mental health provider enrollment timelines.',
                'experience_level'    => 'senior',
                'partner_type_pref'   => 'freelancer',
                'requires_hipaa'      => 1,
                'budget_type'         => 'fixed',
                'budget_amount_cents' => 75000,
                'status'              => 'open',
                'is_urgent'           => 1,
                'proposals_count'     => 4,
                'posted_at'           => $now->copy()->subDays(5),
                'closes_at'           => $now->copy()->addDays(25),
                'created_at'          => $now->copy()->subDays(5),
                'updated_at'          => $now->copy()->subDays(1),
            ],
            [
                'id'                  => 'job_sarah_admin',
                'practitioner_id'     => 'p_sarah',
                'title'               => 'Virtual Administrative Support',
                'category'            => 'admin',
                'job_type'            => 'ongoing',
                'location_pref'       => 'remote',
                'description'         => 'Looking for virtual admin support — scheduling, insurance verification, client intake coordination. 10–15 hrs/week.',
                'experience_level'    => 'mid',
                'requires_hipaa'      => 1,
                'budget_type'         => 'hourly',
                'budget_amount_cents' => 4500,
                'status'              => 'paused',
                'is_urgent'           => 0,
                'proposals_count'     => 1,
                'posted_at'           => $now->copy()->subDays(14),
                'closes_at'           => null,
                'created_at'          => $now->copy()->subDays(14),
                'updated_at'          => $now->copy()->subDays(7),
            ],
            [
                'id'                  => 'job_sarah_marketing',
                'practitioner_id'     => 'p_sarah',
                'title'               => 'Practice Marketing & Patient Acquisition',
                'category'            => 'marketing',
                'job_type'            => 'ongoing',
                'location_pref'       => 'remote',
                'description'         => 'Seeking a healthcare-experienced digital marketer to grow our therapy practice. Goals: increase new patient inquiries by 30% in 6 months via SEO, Google Ads, and Psychology Today profile optimization. Must understand HIPAA marketing rules.',
                'experience_level'    => 'senior',
                'partner_type_pref'   => 'freelancer',
                'requires_hipaa'      => 1,
                'budget_type'         => 'retainer',
                'budget_amount_cents' => 200000,
                'status'              => 'open',
                'is_urgent'           => 0,
                'proposals_count'     => 2,
                'posted_at'           => $now->copy()->subDays(3),
                'closes_at'           => $now->copy()->addDays(30),
                'created_at'          => $now->copy()->subDays(3),
                'updated_at'          => $now->copy()->subDays(1),
            ],
            [
                'id'                  => 'job_sarah_it',
                'practitioner_id'     => 'p_sarah',
                'title'               => 'HIPAA IT Security Audit & EHR Setup',
                'category'            => 'it',
                'job_type'            => 'one_time',
                'location_pref'       => 'hybrid',
                'description'         => 'Need an IT specialist to conduct a HIPAA security risk assessment, set up SimplePractice EHR integration with our billing software, and implement secure remote-work policies. One-time engagement with potential ongoing support.',
                'experience_level'    => 'expert',
                'requires_hipaa'      => 1,
                'requires_baa'        => 1,
                'budget_type'         => 'fixed',
                'budget_amount_cents' => 350000,
                'status'              => 'draft',
                'is_urgent'           => 0,
                'proposals_count'     => 0,
                'posted_at'           => null,
                'closes_at'           => null,
                'created_at'          => $now->copy()->subDays(2),
                'updated_at'          => $now->copy()->subDays(2),
            ],
            [
                'id'                  => 'job_sarah_billing_old',
                'practitioner_id'     => 'p_sarah',
                'title'               => 'Billing Support — Blue Cross Panel Clean-Up',
                'category'            => 'billing',
                'job_type'            => 'one_time',
                'location_pref'       => 'remote',
                'description'         => 'One-time project to audit and resubmit 6 months of denied Blue Cross claims. Approximately 120 denied claims totaling $28,000 in outstanding AR.',
                'experience_level'    => 'senior',
                'requires_hipaa'      => 1,
                'budget_type'         => 'fixed',
                'budget_amount_cents' => 180000,
                'status'              => 'filled',
                'is_urgent'           => 0,
                'proposals_count'     => 3,
                'posted_at'           => $now->copy()->subMonths(4),
                'closes_at'           => $now->copy()->subMonths(3),
                'created_at'          => $now->copy()->subMonths(4),
                'updated_at'          => $now->copy()->subMonths(3),
            ],
            // ── p_maria jobs ─────────────────────────────────────────────
            [
                'id'                  => 'job_maria_billing',
                'practitioner_id'     => 'p_maria',
                'title'               => 'Medical Billing & Revenue Cycle Management',
                'category'            => 'billing',
                'job_type'            => 'ongoing',
                'location_pref'       => 'remote',
                'description'         => 'Seeking experienced billing specialist for busy couples therapy practice. Approximately 60 sessions/month across 3 insurance panels. Must have experience with mental health billing (CPT codes 90837, 90847).',
                'requires_hipaa'      => 1,
                'budget_type'         => 'retainer',
                'budget_amount_cents' => 150000,
                'status'              => 'filled',
                'is_urgent'           => 0,
                'proposals_count'     => 4,
                'posted_at'           => $now->copy()->subMonths(3),
                'closes_at'           => $now->copy()->subMonths(2),
                'created_at'          => $now->copy()->subMonths(3),
                'updated_at'          => $now->copy()->subMonths(2),
            ],
            [
                'id'                  => 'job_maria_consulting',
                'practitioner_id'     => 'p_maria',
                'title'               => 'Practice Operations Consulting — Growth Strategy',
                'category'            => 'consulting',
                'job_type'            => 'one_time',
                'location_pref'       => 'remote',
                'description'         => 'Looking for a healthcare practice consultant to help develop a 12-month growth roadmap. Focus areas: group therapy expansion, supervision model, and associate billing. Deliverable: written strategic plan + 1 implementation session.',
                'experience_level'    => 'expert',
                'budget_type'         => 'fixed',
                'budget_amount_cents' => 500000,
                'status'              => 'open',
                'is_urgent'           => 0,
                'proposals_count'     => 1,
                'posted_at'           => $now->copy()->subDays(7),
                'closes_at'           => $now->copy()->addDays(21),
                'created_at'          => $now->copy()->subDays(7),
                'updated_at'          => $now->copy()->subDays(2),
            ],
            // ── p_david jobs ─────────────────────────────────────────────
            [
                'id'                  => 'job_david_billing',
                'practitioner_id'     => 'p_david',
                'title'               => 'Part-Time Billing Support — Solo Practice',
                'category'            => 'billing',
                'job_type'            => 'part_time',
                'location_pref'       => 'remote',
                'description'         => 'Small solo practice needing part-time billing assistance. About 20 sessions per month, primarily self-pay with some insurance.',
                'requires_hipaa'      => 1,
                'budget_type'         => 'hourly',
                'budget_amount_cents' => 5000,
                'status'              => 'open',
                'is_urgent'           => 0,
                'proposals_count'     => 2,
                'posted_at'           => $now->copy()->subDays(8),
                'closes_at'           => $now->copy()->addDays(22),
                'created_at'          => $now->copy()->subDays(8),
                'updated_at'          => $now->copy()->subDays(2),
            ],
            [
                'id'                  => 'job_david_credentialing',
                'practitioner_id'     => 'p_david',
                'title'               => 'Medicaid Credentialing — New Mexico',
                'category'            => 'credentialing',
                'job_type'            => 'one_time',
                'location_pref'       => 'remote',
                'description'         => 'New solo practice needs Medicaid enrollment in New Mexico. No prior Medicaid panel. Need help with CAQH setup, NPI registration, and Centennial Care enrollment. Timeline is urgent — hoping to accept Medicaid within 90 days.',
                'experience_level'    => 'mid',
                'requires_hipaa'      => 1,
                'budget_type'         => 'fixed',
                'budget_amount_cents' => 60000,
                'status'              => 'open',
                'is_urgent'           => 1,
                'proposals_count'     => 0,
                'posted_at'           => $now->copy()->subDays(1),
                'closes_at'           => $now->copy()->addDays(30),
                'created_at'          => $now->copy()->subDays(1),
                'updated_at'          => $now->copy()->subDays(1),
            ],
        ];

        foreach ($jobs as $j) {
            $j = array_merge([
                'job_type'            => null,
                'location_pref'       => 'remote',
                'experience_level'    => null,
                'partner_type_pref'   => null,
                'certifications'      => null,
                'requires_hipaa'      => 0,
                'requires_nda'        => 0,
                'requires_baa'        => 0,
                'application_deadline'=> null,
                'max_applicants'      => 0,
                'payment_method'      => 'Direct Bank Transfer',
                'billing_frequency'   => 'Monthly',
                'perks'               => null,
                'is_featured'         => 0,
                'is_urgent'           => 0,
                'internal_notes'      => null,
                'start_date'          => null,
                'tags'                => null,
                'currency'            => 'usd',
                'deleted_at'          => null,
            ], $j);
            DB::table('bp_jobs')->updateOrInsert(['id' => $j['id']], $j);
        }

        // ── BP Proposals ───────────────────────────────────────────────────
        $proposals = [
            // ── job_sarah_cred (4 proposals, various pipeline stages) ────
            [
                'id'                  => 'prop_jamal_sarah_cred',
                'job_id'              => 'job_sarah_cred',
                'bp_id'               => 'bp_jamal',
                'cover_letter'        => 'I specialize in Texas mental health provider credentialing. I\'ve successfully credentialed 30+ providers with Aetna, Humana, and Optum. Timeline: 60-90 days per panel. Fixed fee includes all application fees and follow-up.',
                'proposed_rate_cents' => 70000,
                'proposed_rate_type'  => 'fixed',
                'status'              => 'accepted',
                'pipeline_stage'      => 'hired',
                'submitted_at'        => $now->copy()->subDays(3),
                'responded_at'        => $now->copy()->subDays(2),
            ],
            [
                'id'                  => 'prop_acme_sarah_cred',
                'job_id'              => 'job_sarah_cred',
                'bp_id'               => 'bp_acme',
                'cover_letter'        => 'Acme offers full credentialing services for all major Texas payers. Our credentialing team has maintained a 100% success rate for mental health providers. We can begin immediately.',
                'proposed_rate_cents' => 90000,
                'proposed_rate_type'  => 'fixed',
                'status'              => 'pending',
                'pipeline_stage'      => 'reviewed',
                'submitted_at'        => $now->copy()->subDays(4),
                'responded_at'        => null,
            ],
            [
                'id'                  => 'prop_nexus_sarah_cred',
                'job_id'              => 'job_sarah_cred',
                'bp_id'               => 'bp_team_owner',
                'cover_letter'        => 'Nexus Consulting Group has a dedicated credentialing division with 8 years of Texas payer experience. We\'ve enrolled 200+ providers with Aetna, Humana, and Optum. Our success-based model means you only pay upon panel approval. Typical timeline is 45-75 days.',
                'proposed_rate_cents' => 82500,
                'proposed_rate_type'  => 'fixed',
                'status'              => 'pending',
                'pipeline_stage'      => 'new',
                'submitted_at'        => $now->copy()->subDay(),
                'responded_at'        => null,
            ],
            [
                'id'                  => 'prop_tanya_sarah_cred',
                'job_id'              => 'job_sarah_cred',
                'bp_id'               => 'bp_team_member',
                'cover_letter'        => 'Hi Dr. Johnson, I\'m an independent credentialing specialist with 6 years handling Texas Medicaid and commercial payer enrollments. I have existing relationships with Aetna\'s provider relations team — this often cuts 2-3 weeks off the standard timeline.',
                'proposed_rate_cents' => 65000,
                'proposed_rate_type'  => 'fixed',
                'status'              => 'pending',
                'pipeline_stage'      => 'reviewed',
                'submitted_at'        => $now->copy()->subDays(2),
                'responded_at'        => null,
            ],
            // ── job_sarah_marketing (2 proposals) ────────────────────────
            [
                'id'                  => 'prop_nexus_sarah_marketing',
                'job_id'              => 'job_sarah_marketing',
                'bp_id'               => 'bp_team_owner',
                'cover_letter'        => 'Nexus has a healthcare marketing division that has grown 12 therapy practices in the last 3 years. We know Psychology Today, Alma, and Google Ads for therapists cold. Average new patient lead cost we achieve: $22.',
                'proposed_rate_cents' => 185000,
                'proposed_rate_type'  => 'retainer',
                'status'              => 'accepted',
                'pipeline_stage'      => 'hired',
                'submitted_at'        => $now->copy()->subDays(2),
                'responded_at'        => $now->copy()->subDays(1),
            ],
            [
                'id'                  => 'prop_jamal_sarah_marketing',
                'job_id'              => 'job_sarah_marketing',
                'bp_id'               => 'bp_jamal',
                'cover_letter'        => 'While my core expertise is billing, I\'ve helped 3 solo practices set up Google Business profiles and basic SEO. I work at a lower rate and can be a good fit for smaller budgets.',
                'proposed_rate_cents' => 120000,
                'proposed_rate_type'  => 'retainer',
                'status'              => 'pending',
                'pipeline_stage'      => 'new',
                'submitted_at'        => $now->copy()->subDays(1),
                'responded_at'        => null,
            ],
            // ── job_david_billing (2 proposals) ──────────────────────────
            [
                'id'                  => 'prop_jamal_david',
                'job_id'              => 'job_david_billing',
                'bp_id'               => 'bp_jamal',
                'cover_letter'        => 'I provide flexible billing support for solo practitioners. My rate includes claim submission, denial management, and monthly reporting. Minimum 10 hours/month.',
                'proposed_rate_cents' => 4800,
                'proposed_rate_type'  => 'hourly',
                'status'              => 'pending',
                'pipeline_stage'      => 'interview',
                'submitted_at'        => $now->copy()->subDays(6),
                'responded_at'        => null,
            ],
            [
                'id'                  => 'prop_tanya_david',
                'job_id'              => 'job_david_billing',
                'bp_id'               => 'bp_team_member',
                'cover_letter'        => 'I have experience supporting solo practice billing across several EHR systems. I can start immediately and am familiar with self-pay and sliding scale documentation requirements.',
                'proposed_rate_cents' => 4500,
                'proposed_rate_type'  => 'hourly',
                'status'              => 'pending',
                'pipeline_stage'      => 'new',
                'submitted_at'        => $now->copy()->subDays(4),
                'responded_at'        => null,
            ],
            // ── job_sarah_billing_old — ACCEPTED (leads to completed contract)
            [
                'id'                  => 'prop_acme_sarah_billing',
                'job_id'              => 'job_sarah_billing_old',
                'bp_id'               => 'bp_acme',
                'cover_letter'        => 'Acme specializes in denied-claim recovery. We operate on a 15% contingency model — you pay nothing unless we recover. Our average recovery rate is 68% of denied AR.',
                'proposed_rate_cents' => 180000,
                'proposed_rate_type'  => 'fixed',
                'status'              => 'accepted',
                'pipeline_stage'      => 'hired',
                'submitted_at'        => $now->copy()->subMonths(4)->addDays(3),
                'responded_at'        => $now->copy()->subMonths(3)->subDays(28),
            ],
            // ── job_maria_billing — ACCEPTED (leads to active contract) ──
            [
                'id'                  => 'prop_acme_maria',
                'job_id'              => 'job_maria_billing',
                'bp_id'               => 'bp_acme',
                'cover_letter'        => 'Acme Health Services has 15 years of experience in behavioral health billing. We have managed revenue cycles for over 50 mental health practices and maintain a 98.2% clean claims rate. Our team of 3 dedicated billers will handle your practice end-to-end.',
                'proposed_rate_cents' => 145000,
                'proposed_rate_type'  => 'retainer',
                'status'              => 'accepted',
                'pipeline_stage'      => 'hired',
                'submitted_at'        => $now->copy()->subMonths(3)->addDays(2),
                'responded_at'        => $now->copy()->subMonths(2)->subDays(25),
            ],
            // ── job_sarah_admin — DECLINED ────────────────────────────────
            [
                'id'                  => 'prop_jamal_sarah_old',
                'job_id'              => 'job_sarah_admin',
                'bp_id'               => 'bp_jamal',
                'cover_letter'        => 'I can provide virtual admin support alongside billing services.',
                'proposed_rate_cents' => 4200,
                'proposed_rate_type'  => 'hourly',
                'status'              => 'declined',
                'pipeline_stage'      => 'rejected',
                'submitted_at'        => $now->copy()->subDays(12),
                'responded_at'        => $now->copy()->subDays(9),
            ],
            // ── job_maria_consulting (1 proposal, new) ────────────────────
            [
                'id'                  => 'prop_nexus_maria_consulting',
                'job_id'              => 'job_maria_consulting',
                'bp_id'               => 'bp_team_owner',
                'cover_letter'        => 'Nexus Consulting has advised 30+ behavioral health practices on group expansion. We deliver a written growth roadmap, financial model, and facilitated implementation session. Our fee is all-inclusive.',
                'proposed_rate_cents' => 480000,
                'proposed_rate_type'  => 'fixed',
                'status'              => 'pending',
                'pipeline_stage'      => 'new',
                'submitted_at'        => $now->copy()->subDays(5),
                'responded_at'        => null,
            ],
        ];

        foreach ($proposals as $p) {
            $p = array_merge([
                'interview_type' => null,
                'interview_at'   => null,
                'decline_reason' => null,
                'internal_notes' => null,
                'created_at'     => $p['submitted_at'],
                'updated_at'     => $p['responded_at'] ?? $p['submitted_at'],
                'deleted_at'     => null,
            ], $p);
            DB::table('bp_proposals')->updateOrInsert(['id' => $p['id']], $p);
        }

        // ── BP Contracts ───────────────────────────────────────────────────
        $contracts = [
            // 1. Active — Acme × p_maria (ongoing billing, milestone-based)
            [
                'id'               => 'contract_acme_maria',
                'job_id'           => 'job_maria_billing',
                'proposal_id'      => 'prop_acme_maria',
                'practitioner_id'  => 'p_maria',
                'bp_id'            => 'bp_acme',
                'title'            => 'Revenue Cycle Management — Santos Integrative Wellness',
                'status'           => 'active',
                'total_value_cents'=> 1740000,
                'signed_at'        => $now->copy()->subMonths(2)->subDays(23),
                'started_at'       => $now->copy()->subMonths(2)->subDays(20),
                'completed_at'     => null,
                'cancelled_at'     => null,
                'created_at'       => $now->copy()->subMonths(2)->subDays(25),
                'updated_at'       => $now->copy()->subDays(1),
            ],
            // 2. Active — Jamal × p_sarah (credentialing, milestone-based) — populates Sarah's Active tab
            [
                'id'               => 'contract_jamal_sarah_cred',
                'job_id'           => 'job_sarah_cred',
                'proposal_id'      => 'prop_jamal_sarah_cred',
                'practitioner_id'  => 'p_sarah',
                'bp_id'            => 'bp_jamal',
                'title'            => 'Insurance Credentialing — Aetna, Humana & Optum',
                'status'           => 'active',
                'total_value_cents'=> 70000,
                'signed_at'        => $now->copy()->subDays(2),
                'started_at'       => $now->copy()->subDays(1),
                'completed_at'     => null,
                'cancelled_at'     => null,
                'created_at'       => $now->copy()->subDays(3),
                'updated_at'       => $now->copy()->subDays(1),
            ],
            // 3. Active — Nexus × p_sarah (marketing retainer, one-time payment type) — second active contract for Sarah
            [
                'id'               => 'contract_nexus_sarah_marketing',
                'job_id'           => 'job_sarah_marketing',
                'proposal_id'      => 'prop_nexus_sarah_marketing',
                'practitioner_id'  => 'p_sarah',
                'bp_id'            => 'bp_team_owner',
                'title'            => 'Practice Marketing & Patient Acquisition — 6-Month Retainer',
                'status'           => 'active',
                'total_value_cents'=> 1110000,
                'signed_at'        => $now->copy()->subDays(1),
                'started_at'       => $now->copy(),
                'completed_at'     => null,
                'cancelled_at'     => null,
                'created_at'       => $now->copy()->subDays(2),
                'updated_at'       => $now->copy(),
            ],
            // 4. COMPLETED — Acme × p_sarah (billing clean-up project, done) — populates Closed tab
            [
                'id'               => 'contract_acme_sarah_billing',
                'job_id'           => 'job_sarah_billing_old',
                'proposal_id'      => 'prop_acme_sarah_billing',
                'practitioner_id'  => 'p_sarah',
                'bp_id'            => 'bp_acme',
                'title'            => 'Blue Cross Denied Claims Recovery — Johnson Counseling',
                'status'           => 'completed',
                'total_value_cents'=> 180000,
                'signed_at'        => $now->copy()->subMonths(4)->subDays(25),
                'started_at'       => $now->copy()->subMonths(4)->subDays(22),
                'completed_at'     => $now->copy()->subMonths(3),
                'cancelled_at'     => null,
                'created_at'       => $now->copy()->subMonths(4)->subDays(28),
                'updated_at'       => $now->copy()->subMonths(3),
            ],
            // 5. COMPLETED — Nexus × p_david (billing setup, one-time, done)
            [
                'id'               => 'contract_nexus_david',
                'job_id'           => 'job_david_billing',
                'proposal_id'      => null,
                'practitioner_id'  => 'p_david',
                'bp_id'            => 'bp_team_owner',
                'title'            => 'Billing Setup & EHR Configuration — Rodriguez Practice',
                'status'           => 'completed',
                'total_value_cents'=> 350000,
                'signed_at'        => $now->copy()->subMonths(6)->subDays(10),
                'started_at'       => $now->copy()->subMonths(6)->subDays(7),
                'completed_at'     => $now->copy()->subMonths(5),
                'cancelled_at'     => null,
                'created_at'       => $now->copy()->subMonths(6)->subDays(12),
                'updated_at'       => $now->copy()->subMonths(5),
            ],
        ];

        foreach ($contracts as $c) {
            $c = array_merge(['deleted_at' => null, 'cancel_reason' => null], $c);
            DB::table('bp_contracts')->updateOrInsert(['id' => $c['id']], $c);
        }

        // Contract meta (use updateOrInsert keyed by contract_id + meta_key)
        $contractMeta = [
            ['contract_id' => 'contract_acme_maria',              'meta_key' => 'scope_summary', 'meta_value' => 'Monthly billing, denial management, EOB posting, monthly revenue reports, quarterly reviews.'],
            ['contract_id' => 'contract_jamal_sarah_cred',        'meta_key' => 'scope_summary', 'meta_value' => 'Full credentialing for Aetna, Humana, and Optum — Texas mental health provider. Includes all application fees, payer follow-up, and effective date confirmation.'],
            ['contract_id' => 'contract_nexus_sarah_marketing',   'meta_key' => 'scope_summary', 'meta_value' => '6-month digital marketing retainer: SEO, Google Ads, Psychology Today optimization, monthly reporting. Goal: 30% increase in new patient inquiries.'],
            ['contract_id' => 'contract_acme_sarah_billing',      'meta_key' => 'scope_summary', 'meta_value' => 'Recovery of 120 denied Blue Cross claims from Jan–Jun 2025. Contingency model.'],
            ['contract_id' => 'contract_acme_sarah_billing',      'meta_key' => 'outcome',       'meta_value' => 'Recovered $19,200 of $28,000 outstanding AR (68.6% recovery rate). Project closed.'],
            ['contract_id' => 'contract_nexus_david',             'meta_key' => 'scope_summary', 'meta_value' => 'EHR billing configuration, clearinghouse setup, and 90-day billing support.'],
        ];

        foreach ($contractMeta as $m) {
            DB::table('contract_meta')->updateOrInsert(
                ['contract_id' => $m['contract_id'], 'meta_key' => $m['meta_key']],
                array_merge($m, ['id' => (string) Str::uuid(), 'meta_type' => 'string'])
            );
        }

        // ── BP Milestones ──────────────────────────────────────────────────
        $milestones = [
            // contract_acme_maria — active, milestone-based (3 monthly milestones)
            [
                'id'           => 'ms_acme_maria_1',
                'contract_id'  => 'contract_acme_maria',
                'title'        => 'Month 1 — Onboarding & Initial Billing Submission',
                'description'  => 'Complete practice onboarding, EHR integration, and submit first batch of claims.',
                'amount_cents' => 145000,
                'status'       => 'paid',
                'due_at'       => $now->copy()->subMonths(1)->subDays(20),
                'submitted_at' => $now->copy()->subMonths(1)->subDays(22),
                'approved_at'  => $now->copy()->subMonths(1)->subDays(19),
                'paid_at'      => $now->copy()->subMonths(1)->subDays(18),
                'sort_order'   => 1,
            ],
            [
                'id'           => 'ms_acme_maria_2',
                'contract_id'  => 'contract_acme_maria',
                'title'        => 'Month 2 — Ongoing Billing + Denial Resolution',
                'description'  => 'Process all claims from month 2, resolve denials from month 1, post EOBs.',
                'amount_cents' => 145000,
                'status'       => 'submitted',
                'due_at'       => $now->copy()->subDays(5),
                'submitted_at' => $now->copy()->subDays(7),
                'approved_at'  => null,
                'paid_at'      => null,
                'sort_order'   => 2,
            ],
            [
                'id'           => 'ms_acme_maria_3',
                'contract_id'  => 'contract_acme_maria',
                'title'        => 'Month 3 — Quarterly Performance Report',
                'description'  => 'Deliver Q1 revenue cycle performance report including denial trends and collection rate.',
                'amount_cents' => 145000,
                'status'       => 'pending',
                'due_at'       => $now->copy()->addDays(20),
                'submitted_at' => null,
                'approved_at'  => null,
                'paid_at'      => null,
                'sort_order'   => 3,
            ],
            // contract_jamal_sarah_cred — active, milestone-based (3 credentialing phases)
            [
                'id'           => 'ms_jamal_sarah_1',
                'contract_id'  => 'contract_jamal_sarah_cred',
                'title'        => 'Phase 1 — CAQH Profile & Application Preparation',
                'description'  => 'Update CAQH profile, gather required documents, prepare applications for all 3 panels.',
                'amount_cents' => 20000,
                'status'       => 'submitted',
                'due_at'       => $now->copy()->addDays(7),
                'submitted_at' => $now->copy()->subDays(1),
                'approved_at'  => null,
                'paid_at'      => null,
                'sort_order'   => 1,
            ],
            [
                'id'           => 'ms_jamal_sarah_2',
                'contract_id'  => 'contract_jamal_sarah_cred',
                'title'        => 'Phase 2 — Application Submission (All 3 Payers)',
                'description'  => 'Submit completed applications to Aetna, Humana, and Optum. Confirm receipt and tracking numbers.',
                'amount_cents' => 25000,
                'status'       => 'pending',
                'due_at'       => $now->copy()->addDays(21),
                'submitted_at' => null,
                'approved_at'  => null,
                'paid_at'      => null,
                'sort_order'   => 2,
            ],
            [
                'id'           => 'ms_jamal_sarah_3',
                'contract_id'  => 'contract_jamal_sarah_cred',
                'title'        => 'Phase 3 — Panel Approval & Effective Date Confirmation',
                'description'  => 'Follow up on all three applications, resolve any payer requests, confirm effective dates.',
                'amount_cents' => 25000,
                'status'       => 'pending',
                'due_at'       => $now->copy()->addDays(75),
                'submitted_at' => null,
                'approved_at'  => null,
                'paid_at'      => null,
                'sort_order'   => 3,
            ],
            // contract_nexus_sarah_marketing — active, one-time payment (no milestones — paid upfront)
            // No milestone rows; contract shows as one-time in UI

            // contract_acme_sarah_billing — completed, 2 paid milestones
            [
                'id'           => 'ms_acme_sarah_1',
                'contract_id'  => 'contract_acme_sarah_billing',
                'title'        => 'Phase 1 — Claim Audit & Submission',
                'description'  => 'Audit 120 denied claims and submit corrected claims to Blue Cross.',
                'amount_cents' => 90000,
                'status'       => 'paid',
                'due_at'       => $now->copy()->subMonths(3)->subDays(15),
                'submitted_at' => $now->copy()->subMonths(3)->subDays(18),
                'approved_at'  => $now->copy()->subMonths(3)->subDays(14),
                'paid_at'      => $now->copy()->subMonths(3)->subDays(13),
                'sort_order'   => 1,
            ],
            [
                'id'           => 'ms_acme_sarah_2',
                'contract_id'  => 'contract_acme_sarah_billing',
                'title'        => 'Phase 2 — Appeals & Final Recovery Report',
                'description'  => 'File appeals for remaining denials and deliver final recovery report.',
                'amount_cents' => 90000,
                'status'       => 'paid',
                'due_at'       => $now->copy()->subMonths(3)->addDays(5),
                'submitted_at' => $now->copy()->subMonths(3)->addDays(3),
                'approved_at'  => $now->copy()->subMonths(3)->addDays(6),
                'paid_at'      => $now->copy()->subMonths(3)->addDays(7),
                'sort_order'   => 2,
            ],
            // contract_nexus_david — completed, 2 paid milestones
            [
                'id'           => 'ms_nexus_david_1',
                'contract_id'  => 'contract_nexus_david',
                'title'        => 'EHR & Clearinghouse Setup',
                'description'  => 'Configure SimplePractice billing module, set up Availity clearinghouse.',
                'amount_cents' => 175000,
                'status'       => 'paid',
                'due_at'       => $now->copy()->subMonths(6)->addDays(14),
                'submitted_at' => $now->copy()->subMonths(6)->addDays(12),
                'approved_at'  => $now->copy()->subMonths(6)->addDays(15),
                'paid_at'      => $now->copy()->subMonths(6)->addDays(16),
                'sort_order'   => 1,
            ],
            [
                'id'           => 'ms_nexus_david_2',
                'contract_id'  => 'contract_nexus_david',
                'title'        => '90-Day Billing Support & Handover',
                'description'  => 'Support initial billing runs and train practice staff on ongoing processes.',
                'amount_cents' => 175000,
                'status'       => 'paid',
                'due_at'       => $now->copy()->subMonths(5)->addDays(7),
                'submitted_at' => $now->copy()->subMonths(5)->addDays(5),
                'approved_at'  => $now->copy()->subMonths(5)->addDays(8),
                'paid_at'      => $now->copy()->subMonths(5)->addDays(9),
                'sort_order'   => 2,
            ],
        ];

        foreach ($milestones as $m) {
            $m = array_merge([
                'assigned_member_id' => null,
                'payout_id'          => null,
                'created_at'         => $m['due_at'],
                'updated_at'         => $m['paid_at'] ?? $m['approved_at'] ?? $m['submitted_at'] ?? $m['due_at'],
                'deleted_at'         => null,
            ], $m);
            DB::table('bp_milestones')->updateOrInsert(['id' => $m['id']], $m);
        }

        // ── BP Payouts — for all paid milestones + completed one-time contracts ──
        $hasBpPayoutContractId = \Illuminate\Support\Facades\Schema::hasColumn('bp_payouts', 'contract_id');
        $hasBpPayoutMilestoneId = \Illuminate\Support\Facades\Schema::hasColumn('bp_payouts', 'milestone_id');
        $hasBpPayoutStripeTransfer = \Illuminate\Support\Facades\Schema::hasColumn('bp_payouts', 'stripe_transfer_id');
        $hasBpPayoutReleasedAt = \Illuminate\Support\Facades\Schema::hasColumn('bp_payouts', 'released_at');
        $hasBpPayoutAmountCents = \Illuminate\Support\Facades\Schema::hasColumn('bp_payouts', 'amount_cents');

        $bpPayouts = [
            // acme_maria month 1 paid milestone
            ['id'=>'bpo_acme_maria_ms1',  'bp_id'=>'bp_acme',       'contract_id'=>'contract_acme_maria',         'milestone_id'=>'ms_acme_maria_1',  'amount'=>145000, 'desc'=>'Milestone: Month 1 — Onboarding & Initial Billing Submission',  'paid_at'=>$now->copy()->subMonths(1)->subDays(18)],
            // acme_sarah_billing two paid milestones
            ['id'=>'bpo_acme_sarah_ms1',  'bp_id'=>'bp_acme',       'contract_id'=>'contract_acme_sarah_billing',  'milestone_id'=>'ms_acme_sarah_1',  'amount'=>90000,  'desc'=>'Milestone: Phase 1 — Claim Audit & Submission',                  'paid_at'=>$now->copy()->subMonths(3)->subDays(13)],
            ['id'=>'bpo_acme_sarah_ms2',  'bp_id'=>'bp_acme',       'contract_id'=>'contract_acme_sarah_billing',  'milestone_id'=>'ms_acme_sarah_2',  'amount'=>90000,  'desc'=>'Milestone: Phase 2 — Appeals & Final Recovery Report',           'paid_at'=>$now->copy()->subMonths(3)->addDays(7)],
            // nexus_david two paid milestones
            ['id'=>'bpo_nexus_david_ms1', 'bp_id'=>'bp_team_owner', 'contract_id'=>'contract_nexus_david',         'milestone_id'=>'ms_nexus_david_1', 'amount'=>175000, 'desc'=>'Milestone: EHR & Clearinghouse Setup',                           'paid_at'=>$now->copy()->subMonths(6)->addDays(16)],
            ['id'=>'bpo_nexus_david_ms2', 'bp_id'=>'bp_team_owner', 'contract_id'=>'contract_nexus_david',         'milestone_id'=>'ms_nexus_david_2', 'amount'=>175000, 'desc'=>'Milestone: 90-Day Billing Support & Handover',                   'paid_at'=>$now->copy()->subMonths(5)->addDays(9)],
            // nexus_sarah_marketing — one-time contract, single upfront payout
            ['id'=>'bpo_nexus_sarah_mktg','bp_id'=>'bp_team_owner', 'contract_id'=>'contract_nexus_sarah_marketing','milestone_id'=>null,              'amount'=>1110000,'desc'=>'One-time payment: Practice Marketing & Patient Acquisition — 6-Month Retainer', 'paid_at'=>$now->copy()],
        ];

        foreach ($bpPayouts as $po) {
            $row = [
                'id'               => $po['id'],
                'bp_id'            => $po['bp_id'],
                'currency'         => 'USD',
                'status'           => 'paid',
                'description'      => $po['desc'],
                'stripe_payout_id' => 'tr_demo_' . substr($po['id'], -6),
                'paid_at'          => $po['paid_at']->toDateTimeString(),
                'created_at'       => $po['paid_at']->toDateTimeString(),
                'updated_at'       => $po['paid_at']->toDateTimeString(),
            ];

            // Use amount_cents if migrated, otherwise amount
            if ($hasBpPayoutAmountCents) {
                $row['amount_cents'] = $po['amount'];
            } else {
                $row['amount'] = $po['amount'] / 100;
            }
            if ($hasBpPayoutContractId)   $row['contract_id']      = $po['contract_id'];
            if ($hasBpPayoutMilestoneId)  $row['milestone_id']     = $po['milestone_id'];
            if ($hasBpPayoutStripeTransfer) $row['stripe_transfer_id'] = 'tr_demo_' . substr($po['id'], -8);
            if ($hasBpPayoutReleasedAt)   $row['released_at']      = $po['paid_at']->toDateTimeString();

            DB::table('bp_payouts')->updateOrInsert(['id' => $po['id']], $row);
        }

        // ── BP Team Members ────────────────────────────────────────────────
        DB::table('bp_team_members')->updateOrInsert(['id' => 'btm_nexus_tanya'], [
            'id'              => 'btm_nexus_tanya',
            'agency_id'       => 'bp_team_owner',
            'member_id'       => 'bp_team_member',
            'permission_role' => 'specialist',
            'department'      => 'Operations',
            'status'          => 'active',
            'joined_at'       => $now->copy()->subMonths(3),
            'created_at'      => $now->copy()->subMonths(3),
            'updated_at'      => $now->copy()->subMonths(3),
            'deleted_at'      => null,
        ]);

        // ── BP Saved Jobs ──────────────────────────────────────────────────
        DB::table('bp_saved_jobs')->updateOrInsert(
            ['bp_id' => 'bp_jamal', 'job_id' => 'job_sarah_cred'],
            ['id' => 'bsj_jamal_sarah_cred', 'bp_id' => 'bp_jamal', 'job_id' => 'job_sarah_cred', 'created_at' => $now->copy()->subDays(3)->toDateTimeString()]
        );
        DB::table('bp_saved_jobs')->updateOrInsert(
            ['bp_id' => 'bp_team_owner', 'job_id' => 'job_sarah_marketing'],
            ['id' => 'bsj_nexus_sarah_marketing', 'bp_id' => 'bp_team_owner', 'job_id' => 'job_sarah_marketing', 'created_at' => $now->copy()->subDays(2)->toDateTimeString()]
        );
    }
}
