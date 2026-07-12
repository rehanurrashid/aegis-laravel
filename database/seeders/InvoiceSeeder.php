<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── BP Invoices ──────────────────────────────────────────────────
        $bpInvoices = [
            [
                'id'              => 'bpinv_acme_maria_1',
                'bp_id'           => 'bp_acme',
                'practitioner_id' => 'p_maria',
                'contract_id'     => 'contract_acme_maria',
                'invoice_number'  => 'ACME-2024-0001',
                'status'          => 'paid',
                'subtotal_cents'  => 145000,
                'total_cents'     => 145000,
                'currency'        => 'USD',
                'notes'           => 'Month 1 — Onboarding & Initial Billing Submission.',
                'issued_at'       => $now->copy()->subMonths(1)->subDays(22)->toDateTimeString(),
                'due_at'          => $now->copy()->subMonths(1)->subDays(12)->toDateTimeString(),
                'paid_at'         => $now->copy()->subMonths(1)->subDays(14)->toDateTimeString(),
                'voided_at'       => null,
                'created_at'      => $now->copy()->subMonths(1)->subDays(22)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(1)->subDays(14)->toDateTimeString(),
            ],
            [
                'id'              => 'bpinv_acme_maria_2',
                'bp_id'           => 'bp_acme',
                'practitioner_id' => 'p_maria',
                'contract_id'     => 'contract_acme_maria',
                'invoice_number'  => 'ACME-2024-0002',
                'status'          => 'sent',
                'subtotal_cents'  => 145000,
                'total_cents'     => 145000,
                'currency'        => 'USD',
                'notes'           => 'Month 2 — Ongoing Billing + Denial Resolution.',
                'issued_at'       => $now->copy()->subDays(7)->toDateTimeString(),
                'due_at'          => $now->copy()->addDays(23)->toDateTimeString(),
                'paid_at'         => null,
                'voided_at'       => null,
                'created_at'      => $now->copy()->subDays(7)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(7)->toDateTimeString(),
            ],
            [
                'id'              => 'bpinv_acme_maria_3',
                'bp_id'           => 'bp_acme',
                'practitioner_id' => 'p_maria',
                'contract_id'     => 'contract_acme_maria',
                'invoice_number'  => null,
                'status'          => 'draft',
                'subtotal_cents'  => 145000,
                'total_cents'     => 145000,
                'currency'        => 'USD',
                'notes'           => 'Month 3 — Quarterly Performance Report.',
                'issued_at'       => null,
                'due_at'          => $now->copy()->addDays(28)->toDateTimeString(),
                'paid_at'         => null,
                'voided_at'       => null,
                'created_at'      => $now->copy()->subDays(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'              => 'bpinv_jamal_sarah_overdue',
                'bp_id'           => 'bp_jamal',
                'practitioner_id' => 'p_sarah',
                'contract_id'     => 'contract_jamal_sarah_cred',
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
            [
                'id'              => 'bpinv_jamal_david_void',
                'bp_id'           => 'bp_jamal',
                'practitioner_id' => 'p_david',
                'contract_id'     => null,
                'invoice_number'  => 'JTORRES-2024-0001',
                'status'          => 'void',
                'subtotal_cents'  => 25000,
                'total_cents'     => 25000,
                'currency'        => 'USD',
                'notes'           => 'Voided — service not rendered.',
                'issued_at'       => $now->copy()->subMonths(2)->toDateTimeString(),
                'due_at'          => $now->copy()->subMonths(2)->addDays(30)->toDateTimeString(),
                'paid_at'         => null,
                'voided_at'       => $now->copy()->subMonths(1)->subDays(5)->toDateTimeString(),
                'created_at'      => $now->copy()->subMonths(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(1)->subDays(5)->toDateTimeString(),
            ],
        ];

        foreach ($bpInvoices as $inv) {
            $inv['deleted_at'] = null;
            DB::table('bp_invoices')->updateOrInsert(['id' => $inv['id']], $inv);
        }

        // bp_invoice_line_items columns: id, invoice_id, description, quantity, unit_amount_cents, line_total_cents, sort_order
        // Fixed: total_cents → line_total_cents
        $lineItems = [
            ['id' => (string) Str::uuid(), 'invoice_id' => 'bpinv_acme_maria_1',         'description' => 'Revenue Cycle Management — Monthly Retainer (Month 1)', 'quantity' => 1, 'unit_amount_cents' => 145000, 'line_total_cents' => 145000, 'sort_order' => 1, 'created_at' => $now->copy()->subMonths(1)->subDays(22)->toDateTimeString(), 'updated_at' => $now->copy()->subMonths(1)->subDays(22)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'invoice_id' => 'bpinv_acme_maria_2',         'description' => 'Revenue Cycle Management — Monthly Retainer (Month 2)', 'quantity' => 1, 'unit_amount_cents' => 145000, 'line_total_cents' => 145000, 'sort_order' => 1, 'created_at' => $now->copy()->subDays(7)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(7)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'invoice_id' => 'bpinv_jamal_sarah_overdue',  'description' => 'Billing Coding Audit — 8 hours @ $60/hr',               'quantity' => 8, 'unit_amount_cents' => 6000,   'line_total_cents' => 48000,  'sort_order' => 1, 'created_at' => $now->copy()->subDays(45)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(45)->toDateTimeString()],
        ];
        foreach ($lineItems as $li) {
            DB::table('bp_invoice_line_items')->insert($li);
        }

        // bp_invoice_payments columns: id, invoice_id, payer_id, amount_cents, method, status, stripe_payment_intent, paid_at, created_at
        // Fixed: added payer_id (NOT NULL), currency removed, payment_method→method, stripe_payment_intent_id→stripe_payment_intent
        // status ENUM: pending|succeeded|failed|refunded
        DB::table('bp_invoice_payments')->insert([
            'id'                    => (string) Str::uuid(),
            'invoice_id'            => 'bpinv_acme_maria_1',
            'payer_id'              => 'p_maria',
            'amount_cents'          => 145000,
            'method'                => 'stripe',
            'stripe_payment_intent' => 'pi_demo_acme_maria_1',
            'status'                => 'succeeded',
            'paid_at'               => $now->copy()->subMonths(1)->subDays(14)->toDateTimeString(),
            'created_at'            => $now->copy()->subMonths(1)->subDays(14)->toDateTimeString(),
        ]);

        DB::table('bp_invoice_payments')->insert([
            'id'                    => (string) Str::uuid(),
            'invoice_id'            => 'bpinv_jamal_sarah_overdue',
            'payer_id'              => 'p_sarah',
            'amount_cents'          => 48000,
            'method'                => 'stripe',
            'stripe_payment_intent' => 'pi_demo_failed_001',
            'status'                => 'failed',
            'paid_at'               => null,
            'created_at'            => $now->copy()->subDays(10)->toDateTimeString(),
        ]);

        // ── CS Invoices ──────────────────────────────────────────────────
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
            [
                'id'              => 'csinv_priya_maria_overdue',
                'cs_id'           => 'cs_priya',
                'practitioner_id' => 'p_maria',
                'invoice_number'  => 'PRCCS-2024-001',
                'status'          => 'overdue',
                'total_cents'     => 102000,
                'currency'        => 'USD',
                'issued_at'       => $now->copy()->subDays(40)->toDateTimeString(),
                'due_at'          => $now->copy()->subDays(10)->toDateTimeString(),
                'paid_at'         => null,
                'created_at'      => $now->copy()->subDays(40)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(1)->toDateTimeString(),
            ],
        ];

        foreach ($csInvoices as $inv) {
            $inv['deleted_at'] = null;
            DB::table('cs_invoices')->updateOrInsert(['id' => $inv['id']], $inv);
        }

        // practitioner_payment_methods columns: id, practitioner_id, label, brand, last4, stripe_pm_id, is_default
        // Fixed: type→removed (no such column), last_four→last4
        DB::table('practitioner_payment_methods')->insert([
            'id'              => (string) Str::uuid(),
            'practitioner_id' => 'p_sarah',
            'label'           => 'Visa ending 4242',
            'brand'           => 'visa',
            'last4'           => '4242',
            'stripe_pm_id'    => 'pm_demo_sarah_visa',
            'is_default'      => 1,
            'created_at'      => now()->subMonths(6)->toDateTimeString(),
            'updated_at'      => now()->subMonths(6)->toDateTimeString(),
        ]);
        DB::table('practitioner_payment_methods')->insert([
            'id'              => (string) Str::uuid(),
            'practitioner_id' => 'p_maria',
            'label'           => 'Mastercard ending 5555',
            'brand'           => 'mastercard',
            'last4'           => '5555',
            'stripe_pm_id'    => 'pm_demo_maria_mc',
            'is_default'      => 1,
            'created_at'      => now()->subMonths(12)->toDateTimeString(),
            'updated_at'      => now()->subMonths(12)->toDateTimeString(),
        ]);
    }
}
