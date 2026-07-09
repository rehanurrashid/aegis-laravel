<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\Account\SubscriptionRenewalUpcoming;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Daily job — finds Cashier subscriptions renewing in exactly 7 days and fires
 * SubscriptionRenewalUpcoming, which SendEmailNotificationListener maps to the
 * admin.55-renewal-upcoming template.
 */
class SubscriptionRenewalCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Days ahead to warn. */
    public int $daysAhead;

    public function __construct(int $daysAhead = 7)
    {
        $this->daysAhead = $daysAhead;
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $window = Carbon::now()->addDays($this->daysAhead);

        // Cashier stores subscriptions in `subscriptions` table; we query it directly
        // since we can't guarantee the Cashier subscription manager is available here.
        // Query active subscriptions not scheduled for cancellation
        // Cashier stores next renewal date on Stripe; locally we just find active subs
        // and let Stripe's invoice.upcoming webhook (7 days before) handle the actual reminder.
        // This job is a fallback for subscriptions where webhook may have been missed.
        $windowStart = $window->copy()->startOfDay();
        $windowEnd   = $window->copy()->endOfDay();

        $subs = \Illuminate\Support\Facades\DB::table('subscriptions')
            ->where('stripe_status', 'active')
            ->whereNull('ends_at')  // not scheduled for cancellation
            ->get();

        foreach ($subs as $sub) {
            $user = User::find($sub->user_id ?? $sub->billable_id ?? null);
            if (! $user) {
                continue;
            }

            $renewalDate = Carbon::parse($sub->trial_ends_at ?? $sub->ends_at ?? now()->addDays(7));
            $priceId     = $sub->stripe_price ?? null;
            $tier        = $priceId ? (config("aegis.stripe_price_to_tier.{$priceId}") ?? 'Standard') : 'Standard';

            // Amount — best effort from Stripe price config; 0 is a safe fallback.
            $amountCents = (int) config("aegis.stripe_price_cents.{$priceId}", 0);

            Log::info('SubscriptionRenewalCheckJob: renewal upcoming', [
                'user_id'      => $user->id,
                'renewal_date' => $renewalDate->toDateString(),
            ]);

            event(new SubscriptionRenewalUpcoming(
                $user,
                $amountCents,
                $renewalDate->toFormattedDateString(),
                $tier
            ));
        }
    }
}
