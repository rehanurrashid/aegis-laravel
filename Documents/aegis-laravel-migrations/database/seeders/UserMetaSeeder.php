<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserMetaSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [];

        // ── p_sarah — full profile + all notify prefs ──────────────────────
        $sarahMeta = [
            // Profile
            ['specialties',         json_encode(['Trauma & PTSD', 'Anxiety Disorders', 'Life Transitions', 'Grief & Loss']), 'json'],
            ['services_offered',    json_encode(['Individual Therapy', 'Group Therapy', 'Crisis Support', 'Telehealth']),    'json'],
            ['approaches',          json_encode(['EMDR', 'CBT', 'Somatic Therapy', 'Attachment-Based']),                    'json'],
            ['credentials_list',    json_encode(['Licensed Professional Counselor (LPC)', 'Licensed Marriage and Family Therapist (LMFT)', 'EMDR Certified']), 'json'],
            ['years_in_business',   '12',                                                                                   'int'],
            ['accepting_clients',   '1',                                                                                    'boolean'],
            ['session_fee_cents',   '18000',                                                                                'int'],
            ['insurance_accepted',  json_encode(['Aetna', 'Blue Cross', 'Cigna', 'United Healthcare']),                    'json'],
            ['languages',           json_encode(['English', 'Spanish']),                                                    'json'],
            ['website',             'https://claritycounseling.example.com',                                                'string'],
            ['profile_completeness','100',                                                                                  'int'],

            // Notification preferences (mix of true/false for gate logic testing)
            ['notify_incident_reported',      '1', 'boolean'],
            ['notify_incident_activated',     '1', 'boolean'],
            ['notify_incident_closed',        '1', 'boolean'],
            ['notify_vault_accessed',         '1', 'boolean'],
            ['notify_cs_countersigned',       '1', 'boolean'],
            ['notify_ss_acknowledged',        '1', 'boolean'],
            ['notify_document_issued',        '1', 'boolean'],
            ['notify_plan_review_due',        '1', 'boolean'],
            ['notify_bp_invoice_sent',        '1', 'boolean'],
            ['notify_bp_proposal_received',   '1', 'boolean'],
            ['notify_bp_contract_signed',     '1', 'boolean'],
            ['notify_bp_milestone_submitted', '1', 'boolean'],
            ['notify_message_received',       '1', 'boolean'],
            ['notify_network_request',        '1', 'boolean'],
            ['notify_referral_received',      '0', 'boolean'],
            ['notify_referral_accepted',      '1', 'boolean'],
            ['notify_service_request',        '0', 'boolean'],
            ['notify_news_post',              '1', 'boolean'],
            ['notify_platform_announcement',  '1', 'boolean'],
            ['notify_complaint_reply',        '1', 'boolean'],
            ['notify_ceu_expiry',             '0', 'boolean'],
            ['email_digest_frequency',        'daily',  'string'],
        ];
        foreach ($sarahMeta as [$k, $v, $t]) {
            $rows[] = $this->row('p_sarah', $k, $v, $t);
        }

        // ── p_david — partial profile (missing credentials) ────────────────
        $davidMeta = [
            ['specialties',       json_encode(['Depression', 'Anxiety', 'Adjustment Disorders']), 'json'],
            ['years_in_business', '5',    'int'],
            ['accepting_clients', '1',    'boolean'],
            ['profile_completeness', '55', 'int'],
            ['notify_incident_reported',  '1', 'boolean'],
            ['notify_message_received',   '1', 'boolean'],
            ['notify_plan_review_due',    '1', 'boolean'],
            ['email_digest_frequency',    'weekly', 'string'],
        ];
        foreach ($davidMeta as [$k, $v, $t]) {
            $rows[] = $this->row('p_david', $k, $v, $t);
        }

        // ── p_maria — full profile + services mode ─────────────────────────
        $mariaMeta = [
            ['specialties',       json_encode(['Couples Therapy', 'Family Systems', 'Premarital Counseling', 'Divorce Support']), 'json'],
            ['services_offered',  json_encode(['Couples Therapy', 'Family Therapy', 'Individual Therapy', 'Intensive Retreats']), 'json'],
            ['approaches',        json_encode(['EFT', 'Gottman Method', 'Family Systems', 'Narrative Therapy']), 'json'],
            ['credentials_list',  json_encode(['PhD in Psychology', 'Licensed Marriage and Family Therapist (LMFT)']), 'json'],
            ['years_in_business', '18', 'int'],
            ['accepting_clients', '1',  'boolean'],
            ['session_fee_cents', '22000', 'int'],
            ['services_mode_enabled', '1', 'boolean'],
            ['profile_completeness',  '100', 'int'],
            ['notify_incident_reported', '1', 'boolean'],
            ['notify_message_received',  '1', 'boolean'],
            ['notify_bp_invoice_sent',   '1', 'boolean'],
            ['notify_service_request',   '1', 'boolean'],
            ['email_digest_frequency',   'immediate', 'string'],
        ];
        foreach ($mariaMeta as [$k, $v, $t]) {
            $rows[] = $this->row('p_maria', $k, $v, $t);
        }

        // ── cs_marcus — CS profile meta ────────────────────────────────────
        $marcusMeta = [
            ['cs_fee_type',           'annual',                                            'string'],
            ['cs_fee_cents',          '90000',                                             'int'],
            ['cs_max_practitioners',  '12',                                                'int'],
            ['cs_current_count',      '4',                                                 'int'],
            ['cs_coverage_areas',     json_encode(['Austin, TX', 'Houston, TX', 'San Antonio, TX']), 'json'],
            ['cs_emergency_protocols',json_encode(['24-hour response', 'On-call phone line', 'Emergency vault access protocol']), 'json'],
            ['cs_experience_years',   '8',  'int'],
            ['cs_turnaround_hours',   '4',  'int'],
            ['cs_available',          '1',  'boolean'],
            ['notify_plan_assigned',  '1',  'boolean'],
            ['notify_incident_reported', '1', 'boolean'],
            ['notify_task_assigned',  '1',  'boolean'],
            ['notify_message_received', '1', 'boolean'],
            ['notify_document_issued',  '1', 'boolean'],
            ['email_digest_frequency',  'immediate', 'string'],
        ];
        foreach ($marcusMeta as [$k, $v, $t]) {
            $rows[] = $this->row('cs_marcus', $k, $v, $t);
        }

        // ── cs_priya — CS meta ─────────────────────────────────────────────
        $priyaMeta = [
            ['cs_fee_type',          'monthly',  'string'],
            ['cs_fee_cents',         '8500',     'int'],
            ['cs_max_practitioners', '10',       'int'],
            ['cs_current_count',     '3',        'int'],
            ['cs_available',         '1',        'boolean'],
            ['notify_incident_reported', '1', 'boolean'],
            ['notify_message_received',  '1', 'boolean'],
            ['email_digest_frequency',   'daily', 'string'],
        ];
        foreach ($priyaMeta as [$k, $v, $t]) {
            $rows[] = $this->row('cs_priya', $k, $v, $t);
        }

        // ── bp_acme — full BP profile ──────────────────────────────────────
        $acmeMeta = [
            ['bp_service_categories',  json_encode(['Medical Billing', 'Credentialing', 'Prior Authorization', 'Revenue Cycle Management']), 'json'],
            ['bp_certifications',      json_encode(['HFMA Certified', 'AAPC Member', 'CAQH Credentialing Specialist']), 'json'],
            ['bp_coverage_states',     json_encode(['NY', 'NJ', 'CT', 'PA', 'FL', 'TX']), 'json'],
            ['bp_turnaround_days',     '3',   'int'],
            ['bp_min_contract_cents',  '150000', 'int'],
            ['bp_about',               'Acme Health Services has provided comprehensive healthcare administrative support since 2010. We specialize in revenue cycle management, credentialing, and billing for mental health and behavioral health practices.', 'string'],
            ['notify_proposal_accepted', '1', 'boolean'],
            ['notify_contract_signed',   '1', 'boolean'],
            ['notify_invoice_paid',      '1', 'boolean'],
            ['notify_milestone_approved','1', 'boolean'],
            ['notify_message_received',  '1', 'boolean'],
            ['email_digest_frequency',   'daily', 'string'],
        ];
        foreach ($acmeMeta as [$k, $v, $t]) {
            $rows[] = $this->row('bp_acme', $k, $v, $t);
        }

        // ── bp_jamal — freelancer meta ─────────────────────────────────────
        $jamalMeta = [
            ['bp_service_categories', json_encode(['Medical Billing', 'Medical Coding', 'Claim Submission']), 'json'],
            ['bp_certifications',     json_encode(['CPC - Certified Professional Coder', 'AAPC Member']),   'json'],
            ['bp_coverage_states',    json_encode(['GA', 'FL', 'SC', 'NC']),                               'json'],
            ['bp_about',              'Certified professional coder with 7 years of experience in behavioral health billing and claims management.', 'string'],
            ['notify_proposal_accepted', '1', 'boolean'],
            ['notify_invoice_paid',      '1', 'boolean'],
            ['notify_message_received',  '1', 'boolean'],
            ['email_digest_frequency',   'daily', 'string'],
        ];
        foreach ($jamalMeta as [$k, $v, $t]) {
            $rows[] = $this->row('bp_jamal', $k, $v, $t);
        }

        // ── ss_linda — SS meta ─────────────────────────────────────────────
        $lindaMeta = [
            ['notify_incident_assigned', '1', 'boolean'],
            ['notify_task_assigned',     '1', 'boolean'],
            ['notify_message_received',  '1', 'boolean'],
            ['notify_plan_updated',      '1', 'boolean'],
            ['email_digest_frequency',   'immediate', 'string'],
        ];
        foreach ($lindaMeta as [$k, $v, $t]) {
            $rows[] = $this->row('ss_linda', $k, $v, $t);
        }

        // Bulk upsert
        foreach ($rows as $row) {
            DB::table('user_meta')->updateOrInsert(
                ['user_id' => $row['user_id'], 'meta_key' => $row['meta_key']],
                $row
            );
        }
    }

    private function row(string $userId, string $key, string $value, string $type): array
    {
        return [
            'id'         => (string) Str::uuid(),
            'user_id'    => $userId,
            'meta_key'   => $key,
            'meta_value' => $value,
            'meta_type'  => $type,
            'created_at' => now()->subMonths(rand(1, 6)),
            'updated_at' => now()->subDays(rand(0, 14)),
        ];
    }
}
