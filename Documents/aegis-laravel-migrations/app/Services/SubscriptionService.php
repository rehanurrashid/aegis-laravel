<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

/**
 * Wraps Laravel Cashier for subscription billing (separate from Stripe Connect payouts).
 */
class SubscriptionService
{
    public function subscribe(User $user, string $stripePriceId, string $paymentMethod, string $planName = 'default'): Subscription
    {
        return $user->newSubscription($planName, $stripePriceId)->create($paymentMethod);
    }

    public function upgrade(User $user, string $newPriceId, string $planName = 'default'): Subscription
    {
        $sub = $user->subscription($planName);
        $sub->swap($newPriceId);

        // Update tier on user table per the new price
        $tier = $this->tierForPriceId($newPriceId);
        if ($tier) $user->update(['tier' => $tier]);

        return $sub->refresh();
    }

    public function downgrade(User $user, string $newPriceId, string $planName = 'default'): Subscription
    {
        return $this->upgrade($user, $newPriceId, $planName);
    }

    public function cancel(User $user, string $planName = 'default'): Subscription
    {
        $sub = $user->subscription($planName);
        $sub->cancel(); // ends at period end
        return $sub->refresh();
    }

    public function reactivate(User $user, string $planName = 'default'): Subscription
    {
        $sub = $user->subscription($planName);
        $sub->resume();
        return $sub->refresh();
    }

    public function getStatus(User $user, string $planName = 'default'): array
    {
        $sub = $user->subscription($planName);
        if (!$sub) {
            return ['status' => 'none', 'tier' => $user->tier];
        }

        return [
            'status'         => $sub->stripe_status,
            'tier'           => $user->tier,
            'on_grace_period'=> $sub->onGracePeriod(),
            'ends_at'        => $sub->ends_at,
            'price_id'       => $sub->stripe_price,
        ];
    }

    /**
     * Re-sync Stripe subscription state to local DB.
     */
    public function syncStripe(User $user): void
    {
        // Cashier's hooks handle this on webhook receive; manual hook for admin tools.
        $user->refresh();
    }

    private function tierForPriceId(string $priceId): ?string
    {
        $map = config('aegis.stripe_price_to_tier', []);
        return $map[$priceId] ?? null;
    }
}
