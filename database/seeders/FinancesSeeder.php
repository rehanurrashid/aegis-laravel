<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinancesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ─────────────────────────────────────────────────────────────
        // 1. CS engagement fees on plan_stewards
        // ─────────────────────────────────────────────────────────────
        DB::table('plan_stewards')->where('id', 'ps_sarah_marcus')->update([
            'fee_cents' => 90000, 'payment_terms' => 'on_close',
            'auto_charge' => 0, 'updated_at' => $now,
        ]);
        DB::table('plan_stewards')->where('id', 'ps_sarah_priya')->update([
            'fee_cents' => 80000, 'payment_terms' => 'net_30',
            'auto_charge' => 0, 'updated_at' => $now,
        ]);

        // ─────────────────────────────────────────────────────────────
        // 2. BP Contracts — MUST come before BP Invoices (FK constraint)
        // ─────────────────────────────────────────────────────────────
        $contracts = [
            [
                'id' => 'contract_nexus_sarah', 'practitioner_id' => 'p_sarah',
                'bp_id' => 'bp_team_owner', 'title' => 'Marketing & Patient Acquisition',
                'status' => 'active', 'total_value_cents' => 65000, 'payment_type' => 'one_time',
                'started_at' => $now->copy()->subMonths(3), 'completed_at' => null,
                'signed_at' => $now->copy()->subMonths(3),
                'created_at' => $now->copy()->subMonths(3), 'updated_at' => $now->copy()->subMonths(3),
            ],
            [
                'id' => 'contract_acme_sarah_billing', 'practitioner_id' => 'p_sarah',
                'bp_id' => 'bp_acme', 'title' => 'Billing Management & AR Recovery',
                'status' => 'active', 'total_value_cents' => 145000, 'payment_type' => 'one_time',
                'started_at' => $now->copy()->subMonths(4), 'completed_at' => null,
                'signed_at' => $now->copy()->subMonths(4),
                'created_at' => $now->copy()->subMonths(4), 'updated_at' => $now->copy()->subMonths(4),
            ],
            [
                'id' => 'contract_jamal_sarah_cred', 'practitioner_id' => 'p_sarah',
                'bp_id' => 'bp_jamal', 'title' => 'Credentialing — Aetna, Humana, Optum',
                'status' => 'active', 'total_value_cents' => 180000, 'payment_type' => 'milestone',
                'started_at' => $now->copy()->subMonths(2), 'completed_at' => null,
                'signed_at' => $now->copy()->subMonths(2),
                'created_at' => $now->copy()->subMonths(2), 'updated_at' => $now->copy()->subDays(5),
            ],
        ];
        foreach ($contracts as $c) {
            $c['deleted_at'] = null;
            DB::table('bp_contracts')->updateOrInsert(['id' => $c['id']], $c);
        }

        // ─────────────────────────────────────────────────────────────
        // 3. BP Invoices (contracts must exist first)
        // ─────────────────────────────────────────────────────────────
        $bpInvoices = [
            [
                'id' => 'bpinv_nexus_sarah_sent',
                'bp_id' => 'bp_team_owner', 'practitioner_id' => 'p_sarah',
                'contract_id' => 'contract_nexus_sarah',
                'invoice_number' => 'NXS-2026-001', 'status' => 'sent',
                'subtotal_cents' => 65000, 'total_cents' => 65000, 'currency' => 'USD',
                'notes' => 'Marketing strategy & patient acquisition — Q1 2026.',
                'issued_at' => $now->copy()->subDays(8),
                'due_at'    => $now->copy()->addDays(22),
                'paid_at'   => null, 'voided_at' => null,
                'created_at' => $now->copy()->subDays(8), 'updated_at' => $now->copy()->subDays(8),
            ],
            [
                'id' => 'bpinv_acme_sarah_sent',
                'bp_id' => 'bp_acme', 'practitioner_id' => 'p_sarah',
                'contract_id' => 'contract_acme_sarah_billing',
                'invoice_number' => 'ACME-2026-042', 'status' => 'sent',
                'subtotal_cents' => 145000, 'total_cents' => 145000, 'currency' => 'USD',
                'notes' => 'Monthly billing management — March 2026.',
                'issued_at' => $now->copy()->subDays(3),
                'due_at'    => $now->copy()->addDays(27),
                'paid_at'   => null, 'voided_at' => null,
                'created_at' => $now->copy()->subDays(3), 'updated_at' => $now->copy()->subDays(3),
            ],
            [
                'id' => 'bpinv_jamal_sarah_overdue',
                'bp_id' => 'bp_jamal', 'practitioner_id' => 'p_sarah',
                'contract_id' => 'contract_jamal_sarah_cred',
                'invoice_number' => 'JTORRES-2024-0003', 'status' => 'overdue',
                'subtotal_cents' => 48000, 'total_cents' => 48000, 'currency' => 'USD',
                'notes' => 'Billing coding audit — Q2 2024.',
                'issued_at' => $now->copy()->subDays(45),
                'due_at'    => $now->copy()->subDays(15),
                'paid_at'   => null, 'voided_at' => null,
                'created_at' => $now->copy()->subDays(45), 'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'id' => 'bpinv_acme_sarah_paid',
                'bp_id' => 'bp_acme', 'practitioner_id' => 'p_sarah',
                'contract_id' => 'contract_acme_sarah_billing',
                'invoice_number' => 'ACME-2025-0041', 'status' => 'paid',
                'subtotal_cents' => 19200, 'total_cents' => 19200, 'currency' => 'USD',
                'notes' => 'AR Recovery — 68.6% recovery on denied Blue Cross claims.',
                'issued_at' => $now->copy()->subMonths(2),
                'due_at'    => $now->copy()->subMonths(1)->subDays(15),
                'paid_at'   => $now->copy()->subMonths(1)->subDays(20),
                'voided_at' => null,
                'created_at' => $now->copy()->subMonths(2), 'updated_at' => $now->copy()->subMonths(1)->subDays(20),
            ],
        ];
        foreach ($bpInvoices as $inv) {
            $inv['deleted_at'] = null;
            DB::table('bp_invoices')->updateOrInsert(['id' => $inv['id']], $inv);
        }

        // ─────────────────────────────────────────────────────────────
        // 4. CS Invoices
        // ─────────────────────────────────────────────────────────────
        $csInvoices = [
            [
                'id' => 'csinv_marcus_sarah_annual',
                'cs_id' => 'cs_marcus', 'practitioner_id' => 'p_sarah',
                'invoice_number' => 'MWCS-2026-001', 'status' => 'paid',
                'total_cents' => 90000, 'currency' => 'USD',
                'issued_at' => $now->copy()->subMonths(6),
                'due_at'    => $now->copy()->subMonths(5)->subDays(15),
                'paid_at'   => $now->copy()->subMonths(5)->subDays(20),
                'created_at' => $now->copy()->subMonths(6), 'updated_at' => $now->copy()->subMonths(5)->subDays(20),
            ],
            [
                'id' => 'csinv_marcus_sarah_current',
                'cs_id' => 'cs_marcus', 'practitioner_id' => 'p_sarah',
                'invoice_number' => 'MWCS-2026-002', 'status' => 'sent',
                'total_cents' => 90000, 'currency' => 'USD',
                'issued_at' => $now->copy()->subDays(10),
                'due_at'    => $now->copy()->addDays(20),
                'paid_at'   => null,
                'created_at' => $now->copy()->subDays(10), 'updated_at' => $now->copy()->subDays(10),
            ],
        ];
        foreach ($csInvoices as $inv) {
            $inv['deleted_at'] = null;
            DB::table('cs_invoices')->updateOrInsert(['id' => $inv['id']], $inv);
        }

        // service_sessions skipped — service_request_id is NOT NULL with no default.
        // Clinical session demo data comes from ServiceSeeder (ss_sarah_client_1 etc.).

        // ─────────────────────────────────────────────────────────────
        // 6. Practitioner Payments — CS fees, BP, sessions (no subscription — real from Stripe)
        // ─────────────────────────────────────────────────────────────
        $payments = [
            [
                'id' => 'pp_sarah_csfee_1', 'practitioner_id' => 'p_sarah',
                'kind' => 'cs_fee', 'amount_cents' => 90000, 'currency' => 'USD',
                'status' => 'paid',
                'payment_method_label' => 'CS Invoice MWCS-2026-001 · Marcus Webb',
                'stripe_charge_id' => 'ch_demo_cs_marcus_1',
                'paid_at' => $now->copy()->subMonths(5)->subDays(20),
                'created_at' => $now->copy()->subMonths(5)->subDays(20),
                'updated_at' => $now->copy()->subMonths(5)->subDays(20),
            ],
            [
                'id' => 'pp_sarah_bp_acme', 'practitioner_id' => 'p_sarah',
                'kind' => 'bp_invoice', 'amount_cents' => 19200, 'currency' => 'USD',
                'status' => 'paid',
                'payment_method_label' => 'Invoice ACME-2025-0041 · ACME Health Solutions',
                'stripe_charge_id' => 'ch_demo_acme_sarah_1',
                'paid_at' => $now->copy()->subMonths(1)->subDays(20),
                'created_at' => $now->copy()->subMonths(1)->subDays(20),
                'updated_at' => $now->copy()->subMonths(1)->subDays(20),
            ],
        ];
        foreach ($payments as $p) {
            DB::table('practitioner_payments')->updateOrInsert(['id' => $p['id']], $p);
        }

        // ─────────────────────────────────────────────────────────────
        // 7. Default payment method for p_sarah
        // ─────────────────────────────────────────────────────────────
        $existing = DB::table('practitioner_payment_methods')
            ->where('practitioner_id', 'p_sarah')
            ->count();
        if ($existing === 0) {
            DB::table('practitioner_payment_methods')->insert([
                'id' => 'ppm_sarah_visa',
                'practitioner_id' => 'p_sarah',
                'label' => 'Visa ending 4242',
                'brand' => 'visa', 'last4' => '4242',
                'stripe_pm_id' => 'pm_demo_sarah_visa',
                'is_default' => 1,
                'created_at' => $now->copy()->subMonths(6),
                'updated_at' => $now->copy()->subMonths(6),
            ]);
        }

        // ─────────────────────────────────────────────────────────────
        // 8. Sample dispute (resolved)
        // ─────────────────────────────────────────────────────────────
        if (DB::getSchemaBuilder()->hasTable('disputes')) {
            DB::table('disputes')->updateOrInsert(
                ['id' => 'dis_demo_sarah_01'],
                [
                    'id' => 'dis_demo_sarah_01',
                    'disputer_id' => 'p_sarah', 'respondent_id' => 'cs_marcus',
                    'subject_type' => 'cs_invoice', 'subject_id' => 'csinv_marcus_sarah_annual',
                    'reason' => 'quality_issue',
                    'amount_disputed_cents' => 25000,
                    'description' => 'Incident closure timing question.',
                    'status' => 'resolved', 'resolution' => 'no_action',
                    'resolution_cents' => 0,
                    'resolution_summary' => 'Reviewed & dismissed. Closure timing was valid.',
                    'resolved_by' => 'admin_root',
                    'opened_at' => $now->copy()->subDays(21),
                    'respondent_replied_at' => $now->copy()->subDays(19),
                    'resolved_at' => $now->copy()->subDays(14),
                    'closed_at' => null,
                    'created_at' => $now->copy()->subDays(21),
                    'updated_at' => $now->copy()->subDays(14),
                ]
            );
        }

        // ─────────────────────────────────────────────────────────────
        // 9. Default spending controls for p_sarah
        // ─────────────────────────────────────────────────────────────
        $prefs = [
            'fin_autopay_enabled'          => '1',
            'fin_approval_threshold_cents' => '50000',
            'fin_monthly_limit_cents'      => '500000',
        ];
        foreach ($prefs as $key => $value) {
            $ex = DB::table('user_meta')->where('user_id', 'p_sarah')->where('meta_key', $key)->first();
            if ($ex) {
                DB::table('user_meta')->where('id', $ex->id)->update(['meta_value' => $value, 'updated_at' => $now]);
            } else {
                DB::table('user_meta')->insert([
                    'id' => 'um_' . Str::lower(Str::random(12)),
                    'user_id' => 'p_sarah', 'meta_key' => $key, 'meta_value' => $value,
                    'meta_type' => 'string',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }
}
