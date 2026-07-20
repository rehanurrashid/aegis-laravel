<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayoutSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // bp_payouts columns: id, bp_id, amount_cents, currency, status, description, stripe_payout_id, scheduled_at, paid_at, created_at, updated_at
        // Removed: invoice_id, platform_fee_cents, net_amount_cents, stripe_transfer_id (don't exist)
        // status ENUM: pending|in_transit|paid|failed|cancelled
        DB::table('bp_payouts')->insert([
            'id'             => (string) Str::uuid(),
            'bp_id'          => 'bp_acme',
            'amount_cents'   => 137750,
            'currency'       => 'USD',
            'status'         => 'paid',
            'description'    => 'Payout for invoice ACME-2024-0001 (net of 5% platform fee)',
            'stripe_payout_id' => 'tr_demo_acme_001',
            'paid_at'        => $now->copy()->subMonths(1)->subDays(12)->toDateTimeString(),
            'created_at'     => $now->copy()->subMonths(1)->subDays(12)->toDateTimeString(),
            'updated_at'     => $now->copy()->subMonths(1)->subDays(12)->toDateTimeString(),
        ]);

        // bp_tax_documents columns: id, bp_id, doc_type, status, download_url, year, created_at, updated_at, deleted_at
        // Fixed: doc_type ENUM is w9|1099|ein_doc|other ('1099-NEC' → '1099')
        //        status ENUM is available|pending|verified ('issued' → 'available')
        //        Removed: tax_year→year, total_cents (doesn't exist), issued_at (doesn't exist), file_ref (doesn't exist)
        DB::table('bp_tax_documents')->insert([
            'id'           => (string) Str::uuid(),
            'bp_id'        => 'bp_acme',
            'doc_type'     => '1099',
            'status'       => 'available',
            'download_url' => 'tax/bp_acme/1099_2023.pdf',
            'year'         => 2023,
            'created_at'   => $now->copy()->subMonths(6)->toDateTimeString(),
            'updated_at'   => $now->copy()->subMonths(6)->toDateTimeString(),
        ]);

        // cs_payouts columns: id, cs_id, amount_cents, currency, status, description, stripe_payout_id, scheduled_at, paid_at, created_at, updated_at
        // Removed: invoice_id, platform_fee_cents, net_amount_cents, stripe_transfer_id
        DB::table('cs_payouts')->insert([
            'id'             => (string) Str::uuid(),
            'cs_id'          => 'cs_marcus',
            'amount_cents'   => 85500,
            'currency'       => 'USD',
            'status'         => 'paid',
            'description'    => 'Payout for invoice MWCS-2026-001 (net of 5% platform fee)',
            'stripe_payout_id' => 'tr_demo_marcus_001',
            'paid_at'        => $now->copy()->subMonths(5)->subDays(18)->toDateTimeString(),
            'created_at'     => $now->copy()->subMonths(5)->subDays(18)->toDateTimeString(),
            'updated_at'     => $now->copy()->subMonths(5)->subDays(18)->toDateTimeString(),
        ]);

        // practitioner_payments columns: id, practitioner_id, payment_method_id, kind, amount_cents, currency, status,
        //   payment_method_label, stripe_charge_id, paid_at, created_at, updated_at
        // Fixed: payable_type/payable_id → payment_method_id (nullable FK to practitioner_payment_methods);
        //        stripe_payment_intent_id → stripe_charge_id
        //        status ENUM: paid|failed|refunded|partially_refunded|pending ('succeeded' → 'paid')
        //        kind ENUM: subscription|maat_addon|cs_fee|bp_invoice|refund
        DB::table('practitioner_payments')->insert([
            'id'                   => (string) Str::uuid(),
            'practitioner_id'      => 'p_sarah',
            'payment_method_id'    => null,
            'kind'                 => 'cs_fee',
            'amount_cents'         => 90000,
            'currency'             => 'USD',
            'status'               => 'paid',
            'payment_method_label' => 'Visa ending 4242',
            'stripe_charge_id'     => 'pi_demo_sarah_cs_001',
            'paid_at'              => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(),
            'created_at'           => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(),
            'updated_at'           => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(),
        ]);
    }
}
