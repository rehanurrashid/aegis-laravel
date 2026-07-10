<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * FinancesSeeder — supplements InvoiceSeeder and BpSeeder to ensure every
 * UI state on Finances.vue is covered for p_sarah and other demo users.
 *
 * Idempotent: all inserts use updateOrInsert / upsert so re-running is safe.
 */
class FinancesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ─────────────────────────────────────────────────────────────────
        // 1. CS engagement fees on plan_stewards (fee_cents for CS Wallet)
        // ─────────────────────────────────────────────────────────────────
        // ps_sarah_marcus — primary CS, $900/activation
        DB::table('plan_stewards')
            ->where('id', 'ps_sarah_marcus')
            ->update([
                'fee_cents'     => 90000,
                'payment_terms' => 'on_close',
                'auto_charge'   => 0,
                'updated_at'    => $now->toDateTimeString(),
            ]);

        // ps_sarah_priya — alternate CS, $800/activation
        DB::table('plan_stewards')
            ->where('id', 'ps_sarah_priya')
            ->update([
                'fee_cents'     => 80000,
                'payment_terms' => 'on_close',
                'auto_charge'   => 0,
                'updated_at'    => $now->toDateTimeString(),
            ]);

        // ─────────────────────────────────────────────────────────────────
        // 2. BP Invoices for p_sarah — sent (payable), overdue, paid, disputed
        // ─────────────────────────────────────────────────────────────────
        $bpInvoices = [
            // SENT — appears in "Pending" tab and "Upcoming Payments" Overview card
            [
                'id'              => 'bpinv_nexus_sarah_sent',
                'bp_id'           => 'bp_team_owner',
                'practitioner_id' => 'p_sarah',
                'contract_id'     => 'contract_nexus_sarah',
                'invoice_number'  => 'NXS-2026-001',
                'status'          => 'sent',
                'subtotal_cents'  => 65000,
                'total_cents'     => 65000,
                'currency'        => 'USD',
                'notes'           => 'Marketing strategy & patient acquisition — Q1 2026.',
                'issued_at'       => $now->copy()->subDays(8)->toDateTimeString(),
                'due_at'          => $now->copy()->addDays(22)->toDateTimeString(),
                'paid_at'         => null,
                'voided_at'       => null,
                'created_at'      => $now->copy()->subDays(8)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(8)->toDateTimeString(),
            ],
            // OVERDUE — urgent, appears in alert banner
            [
                'id'              => 'bpinv_jamal_sarah_overdue',
                'bp_id'           => 'bp_jamal',
                'practitioner_id' => 'p_sarah',
                'contract_id'     => null,
                'invoice_number'  => 'JTORRES-2024-0003',
                'status'          => 'overdue',
                'subtotal_cents'  => 48000,
                'total_cents'     => 48000,
                'currency'        => 'USD',
                'notes'           => 'Billing coding audit — Q2 2024.',
                'issued_at'       => $now->copy()->subDays(45)->toDateTimeString(),
                'due_at'          => $now->copy()->subDays(15)->toDateTimeString(),
                'paid_at'         => null,
                'voided_at'       => null,
                'created_at'      => $now->copy()->subDays(45)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            // PAID — shows in history tab
            [
                'id'              => 'bpinv_acme_sarah_paid',
                'bp_id'           => 'bp_acme',
                'practitioner_id' => 'p_sarah',
                'contract_id'     => 'contract_acme_sarah_billing',
                'invoice_number'  => 'ACME-2025-0012',
                'status'          => 'paid',
                'subtotal_cents'  => 19200,
                'total_cents'     => 19200,
                'currency'        => 'USD',
                'notes'           => 'AR Recovery — 68.6% recovery on denied Blue Cross claims.',
                'issued_at'       => $now->copy()->subMonths(2)->toDateTimeString(),
                'due_at'          => $now->copy()->subMonths(1)->subDays(15)->toDateTimeString(),
                'paid_at'         => $now->copy()->subMonths(1)->subDays(20)->toDateTimeString(),
                'voided_at'       => null,
                'created_at'      => $now->copy()->subMonths(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(1)->subDays(20)->toDateTimeString(),
            ],
        ];

        foreach ($bpInvoices as $inv) {
            $inv['deleted_at'] = null;
            DB::table('bp_invoices')->updateOrInsert(['id' => $inv['id']], $inv);
        }

        // ─────────────────────────────────────────────────────────────────
        // 3. CS Invoices for p_sarah — sent + paid
        // ─────────────────────────────────────────────────────────────────
        $csInvoices = [
            [
                'id'              => 'csinv_marcus_sarah_annual',
                'cs_id'           => 'cs_marcus',
                'practitioner_id' => 'p_sarah',
                'invoice_number'  => 'MWCS-2024-001',
                'status'          => 'paid',
                'total_cents'     => 90000,
                'currency'        => 'USD',
                'issued_at'       => $now->copy()->subMonths(6)->toDateTimeString(),
                'due_at'          => $now->copy()->subMonths(5)->subDays(15)->toDateTimeString(),
                'paid_at'         => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(),
                'created_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(),
            ],
            [
                'id'              => 'csinv_marcus_sarah_current',
                'cs_id'           => 'cs_marcus',
                'practitioner_id' => 'p_sarah',
                'invoice_number'  => 'MWCS-2024-002',
                'status'          => 'sent',
                'total_cents'     => 90000,
                'currency'        => 'USD',
                'issued_at'       => $now->copy()->subDays(10)->toDateTimeString(),
                'due_at'          => $now->copy()->addDays(20)->toDateTimeString(),
                'paid_at'         => null,
                'created_at'      => $now->copy()->subDays(10)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(10)->toDateTimeString(),
            ],
        ];

        foreach ($csInvoices as $inv) {
            $inv['deleted_at'] = null;
            DB::table('cs_invoices')->updateOrInsert(['id' => $inv['id']], $inv);
        }

        // ─────────────────────────────────────────────────────────────────
        // 4. Active BP Contracts for p_sarah
        // ─────────────────────────────────────────────────────────────────
        $contracts = [
            [
                'id'               => 'contract_nexus_sarah',
                'practitioner_id'  => 'p_sarah',
                'bp_id'            => 'bp_team_owner',
                'title'            => 'Marketing & Patient Acquisition',
                'status'           => 'active',
                'total_value_cents'=> 65000,
                'payment_type'     => 'one_time',
                'started_at'       => $now->copy()->subMonths(3)->toDateTimeString(),
                'completed_at'     => null,
                'signed_at'        => $now->copy()->subMonths(3)->toDateTimeString(),
                'created_at'       => $now->copy()->subMonths(3)->toDateTimeString(),
                'updated_at'       => $now->copy()->subMonths(3)->toDateTimeString(),
            ],
            [
                'id'               => 'contract_jamal_sarah_cred',
                'practitioner_id'  => 'p_sarah',
                'bp_id'            => 'bp_jamal',
                'title'            => 'Credentialing — Aetna, Humana, Optum',
                'status'           => 'active',
                'total_value_cents'=> 180000,
                'payment_type'     => 'milestone',
                'started_at'       => $now->copy()->subMonths(2)->toDateTimeString(),
                'completed_at'     => null,
                'signed_at'        => $now->copy()->subMonths(2)->toDateTimeString(),
                'created_at'       => $now->copy()->subMonths(2)->toDateTimeString(),
                'updated_at'       => $now->copy()->subDays(5)->toDateTimeString(),
            ],
        ];

        foreach ($contracts as $c) {
            $c['deleted_at'] = null;
            DB::table('bp_contracts')->updateOrInsert(['id' => $c['id']], $c);
        }

        // ─────────────────────────────────────────────────────────────────
        // 5. PractitionerPayments — transaction history for p_sarah
        // ─────────────────────────────────────────────────────────────────
        $payments = [
            [
                'id'                   => 'pp_sarah_sub_jan',
                'practitioner_id'      => 'p_sarah',
                'kind'                 => 'subscription',
                'amount_cents'         => 4900,
                'currency'             => 'USD',
                'status'               => 'paid',
                'payment_method_label' => 'Aegis Platform · Practice Plan',
                'stripe_charge_id'     => 'ch_demo_sub_jan',
                'paid_at'              => $now->copy()->subMonths(1)->startOfMonth()->toDateTimeString(),
                'created_at'           => $now->copy()->subMonths(1)->startOfMonth()->toDateTimeString(),
                'updated_at'           => $now->copy()->subMonths(1)->startOfMonth()->toDateTimeString(),
            ],
            [
                'id'                   => 'pp_sarah_sub_feb',
                'practitioner_id'      => 'p_sarah',
                'kind'                 => 'subscription',
                'amount_cents'         => 4900,
                'currency'             => 'USD',
                'status'               => 'paid',
                'payment_method_label' => 'Aegis Platform · Practice Plan',
                'stripe_charge_id'     => 'ch_demo_sub_feb',
                'paid_at'              => $now->copy()->startOfMonth()->toDateTimeString(),
                'created_at'           => $now->copy()->startOfMonth()->toDateTimeString(),
                'updated_at'           => $now->copy()->startOfMonth()->toDateTimeString(),
            ],
            [
                'id'                   => 'pp_sarah_csfee_1',
                'practitioner_id'      => 'p_sarah',
                'kind'                 => 'cs_fee',
                'amount_cents'         => 90000,
                'currency'             => 'USD',
                'status'               => 'paid',
                'payment_method_label' => 'CS Invoice MWCS-2024-001 · Marcus Webb',
                'stripe_charge_id'     => 'ch_demo_cs_marcus_1',
                'paid_at'              => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(),
                'created_at'           => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(),
                'updated_at'           => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(),
            ],
            [
                'id'                   => 'pp_sarah_bp_acme',
                'practitioner_id'      => 'p_sarah',
                'kind'                 => 'bp_invoice',
                'amount_cents'         => 19200,
                'currency'             => 'USD',
                'status'               => 'paid',
                'payment_method_label' => 'Invoice ACME-2025-0012 · ACME Health Solutions',
                'stripe_charge_id'     => 'ch_demo_acme_sarah_1',
                'paid_at'              => $now->copy()->subMonths(1)->subDays(20)->toDateTimeString(),
                'created_at'           => $now->copy()->subMonths(1)->subDays(20)->toDateTimeString(),
                'updated_at'           => $now->copy()->subMonths(1)->subDays(20)->toDateTimeString(),
            ],
        ];

        foreach ($payments as $p) {
            DB::table('practitioner_payments')->updateOrInsert(['id' => $p['id']], $p);
        }

        // ─────────────────────────────────────────────────────────────────
        // 6. Default payment method for p_sarah (practitioner_payment_methods)
        // ─────────────────────────────────────────────────────────────────
        // Only insert if not already present
        $existing = DB::table('practitioner_payment_methods')
            ->where('practitioner_id', 'p_sarah')
            ->count();

        if ($existing === 0) {
            DB::table('practitioner_payment_methods')->insert([
                'id'              => 'ppm_' . Str::lower(Str::random(12)),
                'practitioner_id' => 'p_sarah',
                'label'           => 'Visa ending 4242',
                'brand'           => 'visa',
                'last4'           => '4242',
                'stripe_pm_id'    => 'pm_demo_sarah_visa',
                'is_default'      => 1,
                'created_at'      => now()->subMonths(6)->toDateTimeString(),
                'updated_at'      => now()->subMonths(6)->toDateTimeString(),
            ]);
        }

        // ─────────────────────────────────────────────────────────────────
        // 7. DisputeSeeder data (resolved dispute for p_sarah's CS invoice)
        // ─────────────────────────────────────────────────────────────────
        DB::table('disputes')->updateOrInsert(
            ['id' => 'dis_demo_sarah_01'],
            [
                'id'                    => 'dis_demo_sarah_01',
                'disputer_id'           => 'p_sarah',
                'respondent_id'         => 'cs_marcus',
                'subject_type'          => 'cs_invoice',
                'subject_id'            => 'csinv_marcus_sarah_annual',
                'reason'                => 'quality_issue',
                'amount_disputed_cents' => 25000,
                'description'           => 'Incident was not legitimately closed before the invoice was generated.',
                'status'                => 'resolved',
                'resolution'            => 'no_action',
                'resolution_cents'      => 0,
                'resolution_summary'    => 'Admin reviewed the incident log. Closure was valid. Dispute dismissed.',
                'resolved_by'           => 'admin_root',
                'opened_at'             => $now->copy()->subDays(21)->toDateTimeString(),
                'respondent_replied_at' => $now->copy()->subDays(19)->toDateTimeString(),
                'resolved_at'           => $now->copy()->subDays(14)->toDateTimeString(),
                'closed_at'             => null,
                'created_at'            => $now->copy()->subDays(21)->toDateTimeString(),
                'updated_at'            => $now->copy()->subDays(14)->toDateTimeString(),
            ]
        );
    }
}
