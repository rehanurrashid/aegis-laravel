<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Business\SubscriptionTierChanged;
use App\Events\Business\MaatAddonChanged;
use App\Events\Business\SubscriptionCancelled;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

/**
 * Wraps Laravel Cashier for subscription billing (separate from Stripe Connect payouts).
 *
 * All swap/upgrade/downgrade helpers use the correct proration mode:
 *   - Upgrade   → swapAndInvoice()   → bills prorated difference immediately
 *   - Downgrade → noProrate()->swap() → new price effective next cycle, no refund
 */
class SubscriptionService
{
    public function subscribe(User $user, string $stripePriceId, string $paymentMethod, string $planName = 'default'): Subscription
    {
        return $user->newSubscription($planName, $stripePriceId)->create($paymentMethod);
    }

    /**
     * Upgrade to a higher-priced plan — bills the prorated difference NOW.
     */
    public function upgrade(User $user, string $newPriceId, string $planName = 'default'): Subscription
    {
        $sub = $user->subscription($planName);
        if (!$sub) {
            throw new \RuntimeException('Cannot upgrade — no active subscription.');
        }

        $sub->swapAndInvoice($newPriceId);

        $tier = $this->tierForPriceId($newPriceId);
        if ($tier && $tier !== 'maat_addon') $user->update(['tier' => $tier]);

        event(new SubscriptionTierChanged($user->fresh(), 'upgrade', $tier));
        return $sub->refresh();
    }

    /**
     * Downgrade to a lower-priced plan — new price takes effect next cycle, no refund.
     */
    public function downgrade(User $user, string $newPriceId, string $planName = 'default'): Subscription
    {
        $sub = $user->subscription($planName);
        if (!$sub) {
            throw new \RuntimeException('Cannot downgrade — no active subscription.');
        }

        $sub->noProrate()->swap($newPriceId);

        $tier = $this->tierForPriceId($newPriceId);
        if ($tier && $tier !== 'maat_addon') $user->update(['tier' => $tier]);

        event(new SubscriptionTierChanged($user->fresh(), 'downgrade', $tier));
        return $sub->refresh();
    }

    /**
     * Auto-detect upgrade vs downgrade based on current vs new price per-day cost.
     * Normalizes monthly and annual so annual→monthly is correctly downgrade in commitment.
     */
    public function changePlan(User $user, string $newPriceId, string $planName = 'default'): array
    {
        $sub = $user->subscription($planName);
        if (!$sub) {
            throw new \RuntimeException('Cannot change plan — no active subscription.');
        }

        $currentPriceId = $sub->stripe_price;
        if ($currentPriceId === $newPriceId) {
            return ['direction' => 'unchanged', 'subscription' => $sub];
        }

        try {
            $stripe   = $user->stripe();
            $current  = $stripe->prices->retrieve($currentPriceId);
            $incoming = $stripe->prices->retrieve($newPriceId);

            $currentPerDay  = $this->pricePerDay($current);
            $incomingPerDay = $this->pricePerDay($incoming);

            if ($incomingPerDay >= $currentPerDay) {
                $this->upgrade($user, $newPriceId, $planName);
                return ['direction' => 'upgrade', 'subscription' => $user->subscription($planName)->refresh()];
            } else {
                $this->downgrade($user, $newPriceId, $planName);
                return ['direction' => 'downgrade', 'subscription' => $user->subscription($planName)->refresh()];
            }
        } catch (\Throwable $e) {
            Log::error('[SubscriptionService] changePlan failed', [
                'user_id'       => $user->id,
                'current_price' => $currentPriceId,
                'new_price'     => $newPriceId,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function cancel(User $user, string $planName = 'default'): Subscription
    {
        $sub = $user->subscription($planName);
        if (!$sub) {
            throw new \RuntimeException('No active subscription to cancel.');
        }

        $sub->cancel(); // ends at period end
        event(new SubscriptionCancelled($user->fresh()));
        return $sub->refresh();
    }

    public function cancelNow(User $user, string $planName = 'default'): Subscription
    {
        $sub = $user->subscription($planName);
        if (!$sub) {
            throw new \RuntimeException('No active subscription to cancel.');
        }

        $sub->cancelNow();
        event(new SubscriptionCancelled($user->fresh()));
        return $sub->refresh();
    }

    public function reactivate(User $user, string $planName = 'default'): Subscription
    {
        $sub = $user->subscription($planName);
        if (!$sub) {
            throw new \RuntimeException('No subscription to reactivate.');
        }

        $sub->resume();
        return $sub->refresh();
    }

    public function getStatus(User $user, string $planName = 'default'): array
    {
        $sub = $user->subscription($planName);
        if (!$sub) {
            return [
                'status'          => 'none',
                'tier'            => $user->tier,
                'has_maat_addon'  => (bool) $user->maat_addon,
            ];
        }

        return [
            'status'          => $sub->stripe_status,
            'tier'            => $user->tier,
            'on_grace_period' => $sub->onGracePeriod(),
            'ends_at'         => $sub->ends_at,
            'price_id'        => $sub->stripe_price,
            'has_maat_addon'  => (bool) $user->maat_addon,
        ];
    }

    /**
     * Enriched status for Settings.vue — includes invoice history, payment methods,
     * current period dates, and all price IDs so the UI can render plan swap options.
     */
    public function getFullSubscriptionData(User $user, string $planName = 'default'): array
    {
        $status = $this->getStatus($user, $planName);
        $sub    = $user->subscription($planName);

        $currentPeriod = null;
        $nextInvoice   = null;
        $paymentMethods = [];
        $invoices       = [];

        if ($sub && $user->hasStripeId()) {
            try {
                $stripe = $user->stripe();

                // Current period + next invoice preview
                $stripeSub = $stripe->subscriptions->retrieve($sub->stripe_id);
                $currentPeriod = [
                    'start' => $stripeSub->current_period_start ?? null,
                    'end'   => $stripeSub->current_period_end   ?? null,
                ];

                if (!$sub->canceled()) {
                    try {
                        $upcoming = $stripe->invoices->upcoming(['customer' => $user->stripe_id]);
                        $nextInvoice = [
                            'amount_cents' => $upcoming->amount_due ?? 0,
                            'date'         => $upcoming->next_payment_attempt ?? $upcoming->period_end ?? null,
                        ];
                    } catch (\Throwable $e) {
                        // No upcoming invoice — subscription may be ending
                    }
                }

                // Recent invoice history (last 12)
                $invList = $stripe->invoices->all(['customer' => $user->stripe_id, 'limit' => 12]);
                foreach ($invList->data as $inv) {
                    $lineItem    = $inv->lines->data[0] ?? null;
                    $productName = null;

                    // 1. Try price nickname (e.g. "Continuity Practice — Monthly")
                    $priceNickname = $lineItem?->price?->nickname ?? null;
                    if ($priceNickname) {
                        $productName = $priceNickname;
                    }

                    // 2. Try fetching the Stripe Product name
                    if (!$productName) {
                        try {
                            $productId = $lineItem?->price?->product ?? null;
                            if ($productId) {
                                $product     = $stripe->products->retrieve($productId);
                                $productName = $product->name ?? null;
                            }
                        } catch (\Throwable) {}
                    }

                    // 3. Parse the description — strip "N × " prefix and " (at $X.XX / period)" suffix
                    if (!$productName && $lineItem?->description) {
                        $desc = $lineItem->description;
                        // Strip "1 × " prefix
                        $desc = preg_replace('/^\d+\s*[×x]\s*/u', '', $desc);
                        // Strip " (at $X.XX / ...)" suffix
                        $desc = preg_replace('/\s*\(at\s*\$[\d.,]+\s*\/[^)]*\)/i', '', $desc);
                        $productName = trim($desc) ?: null;
                    }

                    $productName = $productName ?? 'Aegis Subscription';

                    $invoices[] = [
                        'id'           => $inv->id,
                        'number'       => $inv->number,
                        'amount_cents' => $inv->amount_paid,
                        'status'       => $inv->status,
                        'paid_at'      => $inv->status_transitions->paid_at ?? null,
                        'created'      => $inv->created,
                        'description'  => $lineItem?->description ?? 'Aegis Subscription',
                        'product_name' => $productName,
                        'pdf_url'      => $inv->invoice_pdf,
                        'hosted_url'   => $inv->hosted_invoice_url,
                    ];
                }

                // Payment methods on customer
                $pmList = $stripe->paymentMethods->all(['customer' => $user->stripe_id, 'type' => 'card']);
                $defaultPmId = $stripeSub->default_payment_method
                    ?? ($stripe->customers->retrieve($user->stripe_id)->invoice_settings->default_payment_method ?? null);

                foreach ($pmList->data as $pm) {
                    $paymentMethods[] = [
                        'id'       => $pm->id,
                        'brand'    => $pm->card->brand,
                        'last4'    => $pm->card->last4,
                        'exp'      => sprintf('%02d/%s', $pm->card->exp_month, substr((string) $pm->card->exp_year, -2)),
                        'default'  => $pm->id === $defaultPmId,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('[SubscriptionService] getFullSubscriptionData Stripe fetch failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return array_merge($status, [
            'current_period'  => $currentPeriod,
            'next_invoice'    => $nextInvoice,
            'payment_methods' => $paymentMethods,
            'invoices'        => $invoices,
            'prices'          => $this->pricesForRole($user),
        ]);
    }

    /**
     * Returns the Stripe price IDs relevant to a user's role so the UI
     * can build swap options without hardcoding.
     */
    private function pricesForRole(User $user): array
    {
        $role = $user->role instanceof \App\Enums\UserRole
            ? $user->role->value
            : (string) $user->role;

        return match ($role) {
            'practitioner' => [
                'access_monthly'   => env('STRIPE_PRICE_ACCESS_MONTHLY'),
                'access_annual'    => env('STRIPE_PRICE_ACCESS_ANNUAL'),
                'practice_monthly' => env('STRIPE_PRICE_PRACTICE_MONTHLY'),
                'practice_annual'  => env('STRIPE_PRICE_PRACTICE_ANNUAL'),
                'maat_monthly'     => env('STRIPE_PRICE_MAAT_MONTHLY'),
                'maat_annual'      => env('STRIPE_PRICE_MAAT_ANNUAL'),
            ],
            'business_partner' => [
                'bp_monthly' => env('STRIPE_PRICE_BP_MONTHLY'),
                'bp_annual'  => env('STRIPE_PRICE_BP_ANNUAL'),
            ],
            'continuity_steward' => [
                'cs_business_monthly' => env('STRIPE_PRICE_CS_BUSINESS_MONTHLY'),
                'cs_business_annual'  => env('STRIPE_PRICE_CS_BUSINESS_ANNUAL'),
            ],
            default => [],
        };
    }

    /**
     * Re-sync Stripe subscription state to local DB.
     */
    public function syncStripe(User $user): void
    {
        $user->refresh();
    }

    /**
     * Toggle the MAAT Professional CS Service addon.
     *
     * Adds/removes it as a subscription_item on the user's existing 'default' subscription,
     * so it appears on the SAME invoice as the base plan.
     */
    public function toggleMaatAddon(User $user, bool $enable, string $billing = 'monthly'): User
    {
        $maatPriceId = $billing === 'annual'
            ? env('STRIPE_PRICE_MAAT_ANNUAL')
            : env('STRIPE_PRICE_MAAT_MONTHLY');

        if (!$maatPriceId) {
            throw new \RuntimeException(
                'MAAT price not configured. Set STRIPE_PRICE_MAAT_MONTHLY / STRIPE_PRICE_MAAT_ANNUAL in .env.'
            );
        }

        $sub = $user->subscription('default');
        if (!$sub) {
            throw new \RuntimeException('Cannot toggle MAAT — no active base subscription.');
        }

        try {
            if ($enable) {
                if (!$sub->hasPrice($maatPriceId)) {
                    $sub->addPrice($maatPriceId);
                }
            } else {
                // Remove either MAAT price (monthly or annual) if present
                foreach ([env('STRIPE_PRICE_MAAT_MONTHLY'), env('STRIPE_PRICE_MAAT_ANNUAL')] as $priceId) {
                    if ($priceId && $sub->hasPrice($priceId)) {
                        $sub->removePrice($priceId);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('[SubscriptionService] toggleMaatAddon failed', [
                'user_id' => $user->id,
                'enable'  => $enable,
                'price'   => $maatPriceId,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }

        $user->update(['maat_addon' => $enable ? 1 : 0]);

        $addonState = $enable ? 'activated' : 'deactivated';
        event(new MaatAddonChanged($user->fresh(), $addonState));

        return $user->fresh();
    }

    /**
     * Redirect URL for the Stripe-hosted billing portal.
     * Configure enabled features in Stripe Dashboard → Settings → Billing → Customer portal.
     */
    public function billingPortalUrl(User $user, string $returnUrl): string
    {
        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer([
                'name'  => $user->display_name,
                'email' => $user->email,
            ]);
        }

        return $user->billingPortalUrl($returnUrl);
    }

    /**
     * Set a saved payment method as the default for future invoices.
     */
    public function setDefaultPaymentMethod(User $user, string $paymentMethodId): void
    {
        $user->updateDefaultPaymentMethod($paymentMethodId);
        // Mirror to users.stripe_payment_method_id so peer-payment charges
        // (chargeProviderToBp / chargeProviderToCs / releaseServiceSessionPayout)
        // pick up the newly default card.
        $user->forceFill(['stripe_payment_method_id' => $paymentMethodId])->save();
    }

    /**
     * Remove a payment method from the customer.
     */
    public function removePaymentMethod(User $user, string $paymentMethodId): void
    {
        try {
            $user->stripe()->paymentMethods->detach($paymentMethodId);
        } catch (\Throwable $e) {
            Log::error('[SubscriptionService] removePaymentMethod failed', [
                'user_id' => $user->id,
                'pm_id'   => $paymentMethodId,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function tierForPriceId(string $priceId): ?string
    {
        $map = config('aegis.stripe_price_to_tier', []);
        return $map[$priceId] ?? null;
    }

    /**
     * Per-day cost in cents, normalized across monthly/annual so upgrade/downgrade
     * comparison is fair.
     */
    private function pricePerDay(\Stripe\Price $price): float
    {
        $amount   = (int) ($price->unit_amount ?? 0);
        $interval = $price->recurring->interval ?? 'month';
        $count    = (int) ($price->recurring->interval_count ?? 1);

        $days = match ($interval) {
            'day'   => $count,
            'week'  => 7 * $count,
            'month' => 30 * $count,
            'year'  => 365 * $count,
            default => 30,
        };

        return $days > 0 ? $amount / $days : $amount;
    }
}
