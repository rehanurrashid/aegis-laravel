<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SubscriptionSeeder — intentionally empty.
 *
 * Subscription data for demo users (p_sarah, p_rehan) has been removed.
 * Subscriptions are now created through the real Stripe onboarding flow only.
 *
 * The wipe block below is kept so that migrate:fresh + db:seed on an existing
 * database does not leave stale Cashier rows behind.
 */
class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        // Remove any previously seeded demo subscriptions
        foreach (['p_sarah', 'p_rehan'] as $userId) {
            DB::table('subscription_items')
                ->whereIn('subscription_id', function ($q) use ($userId) {
                    $q->select('id')->from('subscriptions')->where('user_id', $userId);
                })->delete();

            DB::table('subscriptions')->where('user_id', $userId)->delete();
        }
    }
}
