<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\UserMeta;
use App\Services\ProfileService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private ProfileService $profiles,
        private SubscriptionService $subscriptions,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $meta = UserMeta::where('user_id', $user->id)
            ->where('meta_key', 'like', 'notify_%')
            ->pluck('meta_value', 'meta_key')
            ->toArray();

        return Inertia::render('Provider/Settings', [
            'user'         => $user,
            'meta'         => $meta,
            'mfaEnabled'   => (bool) $user->mfa_enabled,
            'subscription' => $this->subscriptions->getFullSubscriptionData($user),
            'pricing'      => config('aegis.pricing'),
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notify_email'    => 'nullable|boolean',
            'notify_sms'      => 'nullable|boolean',
            'notify_in_app'   => 'nullable|boolean',
            'notify_summary'  => 'nullable|boolean',
            'notify_plan'     => 'nullable|boolean',
            'notify_vault'    => 'nullable|boolean',
            'notify_incident' => 'nullable|boolean',
            'notify_steward'  => 'nullable|boolean',
            'notify_payment'  => 'nullable|boolean',
            'notify_message'  => 'nullable|boolean',
            'notify_referral' => 'nullable|boolean',
            'notify_account'  => 'nullable|boolean',
        ]);

        foreach ($data as $key => $val) {
            $this->profiles->setMeta($request->user()->id, $key, $val ? '1' : '0', 'bool');
        }
        return back()->with('success', 'Notification preferences saved.');
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();
        if (!\Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }
        $user->update(['deactivated_at' => now()]);
        $user->tokens()->delete();
        auth()->logout();
        return redirect('/login')->with('success', 'Account closed.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Subscription management — wired to Settings.vue panel-subscription
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Redirect to Stripe-hosted billing portal (card update, invoice history,
     * cancel — everything covered by Stripe's own UI).
     */
    public function billingPortal(Request $request): RedirectResponse
    {
        try {
            $url = $this->subscriptions->billingPortalUrl(
                $request->user(),
                route('provider.settings.index') . '?section=billing'
            );
            return redirect()->away($url);
        } catch (\Throwable $e) {
            \Log::error('[SettingsController] billingPortal failed', [
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
            ]);
            return back()->withErrors(['stripe' => 'Could not open billing portal. Please try again.']);
        }
    }

    /**
     * Change plan (upgrade or downgrade) — direction auto-detected by price comparison.
     */
    public function swapPlan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'price_id' => ['required', 'string', 'starts_with:price_'],
        ]);

        try {
            $result = $this->subscriptions->changePlan($request->user(), $data['price_id']);
            $msg = match ($result['direction']) {
                'upgrade'   => 'Plan upgraded. Prorated charge added to your account.',
                'downgrade' => 'Plan will change at the next billing cycle.',
                default     => 'Plan unchanged.',
            };
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    /**
     * Cancel subscription at period end — grace period until then.
     */
    public function cancelPlan(Request $request): RedirectResponse
    {
        try {
            $this->subscriptions->cancel($request->user());
            return back()->with('success', 'Your subscription will end at the current billing period. You can reactivate any time before then.');
        } catch (\Throwable $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    /**
     * Reactivate a cancelled subscription during grace period.
     */
    public function resumePlan(Request $request): RedirectResponse
    {
        try {
            $this->subscriptions->reactivate($request->user());
            return back()->with('success', 'Your subscription has been reactivated.');
        } catch (\Throwable $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    /**
     * Toggle MAAT addon on the existing subscription.
     */
    public function toggleMaat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enable' => ['required', 'boolean'],
        ]);

        $user = $request->user();

        if ($data['enable'] && $user->tier !== 'practice') {
            return back()->withErrors([
                'maat' => 'MAAT Professional CS Service requires Continuity Practice tier. Please upgrade first.',
            ]);
        }

        // Determine billing period from the base subscription's price
        $sub = $user->subscription('default');
        $billing = 'monthly';
        if ($sub && $sub->stripe_price) {
            $annualPrices = array_filter([
                env('STRIPE_PRICE_ACCESS_ANNUAL'),
                env('STRIPE_PRICE_PRACTICE_ANNUAL'),
            ]);
            if (in_array($sub->stripe_price, $annualPrices, true)) {
                $billing = 'annual';
            }
        }

        try {
            $this->subscriptions->toggleMaatAddon($user, (bool) $data['enable'], $billing);
            $msg = $data['enable']
                ? 'MAAT Professional CS Service added to your subscription.'
                : 'MAAT Professional CS Service removed.';
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withErrors(['maat' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Payment methods — wired to Settings.vue panel-billing
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Store a new payment method (Stripe PaymentMethod ID from Stripe Elements).
     * Optionally sets as default.
     */
    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => 'required|string|starts_with:pm_',
            'set_default'       => 'nullable|boolean',
        ]);

        $user = $request->user();
        try {
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer([
                    'name'  => $user->display_name,
                    'email' => $user->email,
                ]);
            }

            if (!empty($data['set_default'])) {
                $user->updateDefaultPaymentMethod($data['payment_method_id']);
            } else {
                $user->addPaymentMethod($data['payment_method_id']);
            }
            return back()->with('success', 'Payment method saved.');
        } catch (\Throwable $e) {
            \Log::error('[SettingsController] storePaymentMethod failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return back()->withErrors(['payment' => 'Could not save payment method. ' . $e->getMessage()]);
        }
    }

    public function setDefaultPaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => 'required|string|starts_with:pm_',
        ]);
        try {
            $this->subscriptions->setDefaultPaymentMethod($request->user(), $data['payment_method_id']);
            return back()->with('success', 'Default payment method updated.');
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }

    public function removePaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => 'required|string|starts_with:pm_',
        ]);
        try {
            $this->subscriptions->removePaymentMethod($request->user(), $data['payment_method_id']);
            return back()->with('success', 'Payment method removed.');
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }
}
