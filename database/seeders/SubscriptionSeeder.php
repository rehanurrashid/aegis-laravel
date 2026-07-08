<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds real Stripe subscription data for demo users.
 *
 * These are LIVE Stripe IDs from the MA'AT Practice Firm account.
 * They are safe to re-seed on migrate:fresh — Laravel Cashier reads
 * them from the local DB and does NOT call Stripe on every request.
 *
 * Stripe subscription checks (EnsureSubscriptionActive) only query
 * the local `subscriptions` table for stripe_status = 'active'.
 *
 * ┌──────────────────────────────────────────────────────────────┐
 * │ subscriptions.id = 1 (auto-increment bigint)                │
 * │ user_id          = p_sarah                                  │
 * │ stripe_id        = sub_1Tr3QvHnj73y5cBfBd6U6JCv            │
 * │ stripe_status    = active                                   │
 * │ stripe_price     = price_1TqSraHnj73y5cBfjxtPipio          │
 * └──────────────────────────────────────────────────────────────┘
 * ┌──────────────────────────────────────────────────────────────┐
 * │ subscription_items.subscription_id = 1 (FK)                 │
 * │ stripe_id        = si_UqkwHQA2jkCD0k                       │
 * │ stripe_product   = prod_Uq99iMpIwmgPTa                     │
 * │ stripe_price     = price_1TqSraHnj73y5cBfjxtPipio          │
 * └──────────────────────────────────────────────────────────────┘
 */
class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── Wipe existing demo subscriptions to avoid duplicate stripe_id errors ──
        DB::table('subscription_items')
            ->whereIn('subscription_id', function ($q) {
                $q->select('id')
                  ->from('subscriptions')
                  ->where('user_id', 'p_sarah');
            })->delete();

        DB::table('subscriptions')
            ->where('user_id', 'p_sarah')
            ->delete();

        // ── p_sarah — Practice tier, active Stripe subscription ────────────────
        $subId = DB::table('subscriptions')->insertGetId([
            'user_id'       => 'p_sarah',
            'type'          => 'default',
            'stripe_id'     => 'sub_1Tr3QvHnj73y5cBfBd6U6JCv',
            'stripe_status' => 'active',
            'stripe_price'  => 'price_1TqSraHnj73y5cBfjxtPipio',
            'quantity'      => 1,
            'trial_ends_at' => null,
            'ends_at'       => null,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        DB::table('subscription_items')->insert([
            'subscription_id' => $subId,
            'stripe_id'       => 'si_UqkwHQA2jkCD0k',
            'stripe_product'  => 'prod_Uq99iMpIwmgPTa',
            'stripe_price'    => 'price_1TqSraHnj73y5cBfjxtPipio',
            'quantity'        => 1,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }
}
