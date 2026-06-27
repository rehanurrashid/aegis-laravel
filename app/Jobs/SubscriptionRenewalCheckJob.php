<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\Stripe\SubscriptionRenewalUpcoming;
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
        $subs = \Illuminate\Support\Facades\DB::table('subscriptions')
            ->whereNotNull('stripe_status')
            ->where('stripe_status', 'active')
            ->whereDate('trial_ends_at', $window)->orWhereDate(
                \Illuminate\Support\Facades\DB::raw('DATE(renews_at)'), $window->toDateString()
            )
            ->get();

        foreach ($subs as $sub) {
            $user = User::find($sub->user_id ?? $sub->billable_id ?? null);
            if (! $user) {
                continue;
            }

            $renewalDate = Carbon::parse($sub->trial_ends_at ?? $sub->renews_at ?? $sub->current_period_end ?? now()->addDays(7));
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
