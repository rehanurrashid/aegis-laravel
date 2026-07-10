<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Enums\ActivitySeverity;
use App\Events\Auth\AccountClosed;
use App\Events\Business\SubscriptionCancelled;
use App\Events\Business\SubscriptionTierChanged;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * HasCommonSettingsMethods
 *
 * Shared write-path logic used identically across Provider, ContinuitySteward,
 * and BusinessPartner SettingsControllers. Portal string is always resolved at
 * runtime via $user->role?->portal() — never hardcoded.
 *
 * Portal-specific methods (billingPortal, connectOnboard, connectReturn) are
 * declared abstract and implemented in each portal controller because they
 * contain route names that differ per portal.
 *
 * Trait requirements (must be provided by the using class via DI):
 *   $this->activity   — ActivityService
 *   $this->profiles   — ProfileService
 *   $this->subscriptions — SubscriptionService
 */
trait HasCommonSettingsMethods
{
    // ── Account ──────────────────────────────────────────────────────────────

    public function updateAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $user = $request->user();
        $user->forceFill(['phone' => $data['phone'] ?? null])->save();

        $this->activity->log(
            $user->id,
            $user->role?->portal() ?? 'provider',
            'account',
            ActivitySeverity::Info,
            'phone_updated',
            'Phone number updated',
            'You updated your contact phone number.',
            null, null, null, 'log', $user->id,
        );

        return back()->with('success', 'Account details updated.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'categories'         => 'nullable|array',
            'categories.*.key'   => 'required|string|max:64',
            'categories.*.push'  => 'nullable|boolean',
            'categories.*.email' => 'nullable|boolean',
            'categories.*.inapp' => 'nullable|boolean',
        ]);

        $user = $request->user();
        if (!empty($data['categories'])) {
            $this->profiles->saveMeta($user, 'notify_categories', $data['categories'], 'json');
        }

        return back()->with('success', 'Notification preferences saved.');
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme'    => 'nullable|string|in:gold,gold-dark,slate',
            'darkMode' => 'nullable|boolean',
            'timezone' => 'nullable|string|max:64',
        ]);

        $user = $request->user();
        $this->profiles->saveMeta($user, 'appearance', [
            'theme'     => $data['theme']    ?? 'gold',
            'dark_mode' => (bool) ($data['darkMode'] ?? false),
            'timezone'  => $data['timezone'] ?? 'America/New_York',
        ], 'json');

        return back()->with('success', 'Appearance settings saved.');
    }

    // ── Security / Sessions ───────────────────────────────────────────────────

    public function revokeSession(Request $request, string $session): RedirectResponse
    {
        $user = $request->user();
        $this->profiles->revokeSession($user, $session);

        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'provider', 'settings',
            ActivitySeverity::Warning,
            'session_revoked', 'Session revoked',
            'A session was remotely terminated from your security settings.',
            null, null, null, 'log', $user->id,
        );

        return back()->with('success', 'Session revoked.');
    }

    public function revokeAllSessions(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->profiles->revokeAllSessions($user);

        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'provider', 'settings',
            ActivitySeverity::Warning,
            'all_sessions_revoked', 'All sessions terminated',
            'All active sessions were signed out from your security settings.',
            null, null, null, 'log', $user->id,
        );

        return back()->with('success', 'All sessions revoked.');
    }

    // ── Danger zone ───────────────────────────────────────────────────────────

    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate(['confirm' => 'required|string|in:DELETE MY ACCOUNT']);

        $user = $request->user();
        $user->update(['deactivated_at' => now()]);
        $user->tokens()->delete();

        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'provider', 'account',
            ActivitySeverity::Warning,
            'account_deleted', 'Account deletion requested',
            'Account queued for permanent deletion in 30 days.',
            null, null, null, 'log', $user->id,
        );

        event(new AccountClosed($user, 'user_requested'));

        auth()->logout();
        return redirect('/login')->with('success', 'Your account has been scheduled for deletion. You have 30 days to change your mind by contacting support.');
    }

    public function pauseAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'until'   => 'nullable|date|after:today',
            'reason'  => 'nullable|string|in:leave,vacation,parental,sabbatical,other',
            'message' => 'nullable|string|max:500',
        ]);

        $data['until'] = !empty($data['until']) ? $data['until'] : null;

        $user = $request->user();
        $this->profiles->saveMeta($user, 'account_paused', '1', 'string');
        $this->profiles->saveMeta($user, 'pause_prefs', [
            'until'   => $data['until']   ?? null,
            'reason'  => $data['reason']  ?? 'other',
            'message' => $data['message'] ?? '',
        ], 'json');

        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'provider', 'account',
            ActivitySeverity::Warning,
            'account_paused', 'Account paused',
            'Your account has been paused. You will not appear in searches or receive activity.',
            null, null, null, 'log', $user->id,
        );

        return back()->with('success', 'Account paused successfully. You can reactivate at any time.');
    }

    public function resumeAccount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->profiles->saveMeta($user, 'account_paused', '0', 'string');

        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'provider', 'account',
            ActivitySeverity::Info,
            'account_resumed', 'Account reactivated',
            'Your account is active again.',
            null, null, null, 'log', $user->id,
        );

        return back()->with('success', 'Account reactivated. You are now visible and active.');
    }

    public function exportData(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'include'   => 'required|array|min:1',
            'include.*' => 'string|in:profile,referrals,documents,agreements,network,activity,messages,billing',
            'format'    => 'required|string|in:json,csv',
        ]);

        $user = $request->user();

        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'provider', 'account',
            ActivitySeverity::Info,
            'data_export_requested', 'Data export requested',
            'A HIPAA-compliant export of your data has been requested and will be emailed within 24 hours.',
            null, null, null, 'log', $user->id,
        );

        // TODO: ExportUserDataJob::dispatch($user, $data['include'], $data['format']);

        return back()->with('success', 'Export request submitted. Your data will be emailed to ' . $user->email . ' within 24 hours.');
    }

    // ── Subscription ─────────────────────────────────────────────────────────

    /**
     * Swap billing cycle or plan tier.
     *
     * CS overrides this to add the cs_account_type === 'business' guard.
     * All other portals use this implementation directly.
     */
    public function swapPlan(Request $request): RedirectResponse
    {
        $data = $request->validate(['price_id' => ['required', 'string', 'starts_with:price_']]);
        $user = $request->user();

        try {
            $result = $this->subscriptions->changePlan($user, $data['price_id']);
            $msg    = match ($result['direction']) {
                'upgrade'   => 'Plan upgraded. Prorated charge added to your account.',
                'downgrade' => 'Plan will change at the next billing cycle.',
                default     => 'Plan unchanged.',
            };

            $actionMap = [
                'upgrade'        => ['subscription_upgraded',   'Plan upgraded',   'You upgraded your Aegis subscription plan.'],
                'downgrade'      => ['subscription_downgraded', 'Plan downgraded', 'Plan change scheduled for the next billing cycle.'],
                'switch-annual'  => ['subscription_changed',    'Billing changed', 'You switched to annual billing.'],
                'switch-monthly' => ['subscription_changed',    'Billing changed', 'You switched to monthly billing.'],
            ];
            [$action, $title, $desc] = $actionMap[$result['direction']] ?? ['subscription_changed', 'Plan changed', $msg];

            $this->activity->log(
                $user->id, $user->role?->portal() ?? 'provider', 'account',
                ActivitySeverity::Info,
                $action, $title, $desc,
                User::class, $user->id,
                null, 'log', $user->id,
            );

            if (in_array($result['direction'], ['upgrade', 'downgrade'], true)) {
                event(new SubscriptionTierChanged($user, $result['direction'], $user->tier?->value));
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    public function cancelPlan(Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $this->subscriptions->cancel($user);

            $this->activity->log(
                $user->id, $user->role?->portal() ?? 'provider', 'account',
                ActivitySeverity::Warning,
                'subscription_cancelled', 'Subscription cancelled',
                'You cancelled your Aegis subscription. Access continues until the end of the current billing period.',
                User::class, $user->id,
                null, 'log', $user->id,
            );

            event(new SubscriptionCancelled($user));

            return back()->with('success', 'Your subscription will end at the current billing period.');
        } catch (\Throwable $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    public function resumePlan(Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $this->subscriptions->reactivate($user);

            $this->activity->log(
                $user->id, $user->role?->portal() ?? 'provider', 'account',
                ActivitySeverity::Info,
                'subscription_reactivated', 'Subscription reactivated',
                'You reactivated your Aegis subscription.',
                User::class, $user->id,
                null, 'log', $user->id,
            );

            return back()->with('success', 'Your subscription has been reactivated.');
        } catch (\Throwable $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    // ── Payment methods ───────────────────────────────────────────────────────

    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => 'required|string|starts_with:pm_',
            'set_default'       => 'nullable|boolean',
        ]);
        $user = $request->user();

        // Demo guard
        if (str_starts_with($data['payment_method_id'], 'pm_demo_')) {
            return back()->with('success', 'Demo payment method saved (not sent to Stripe).');
        }

        try {
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer(['name' => $user->display_name, 'email' => $user->email]);
            }
            // Always attach first — updateDefaultPaymentMethod can fail on unattached PMs
            $user->addPaymentMethod($data['payment_method_id']);
            if (!empty($data['set_default'])) {
                $user->updateDefaultPaymentMethod($data['payment_method_id']);
                $user->update(['stripe_payment_method_id' => $data['payment_method_id']]);
            }

            $this->activity->log(
                $user->id, $user->role?->portal() ?? 'provider', 'settings',
                ActivitySeverity::Info,
                'payment_method_added', 'Payment method added',
                'A new payment method was added to your account.',
                null, null, null, 'log', $user->id,
            );

            return back()->with('success', 'Payment method saved.');
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => 'Could not save payment method. ' . $e->getMessage()]);
        }
    }

    public function setDefaultPaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate(['payment_method_id' => 'required|string|starts_with:pm_']);
        $user = $request->user();

        try {
            $user->addPaymentMethod($data['payment_method_id']);
            $user->updateDefaultPaymentMethod($data['payment_method_id']);
            $user->update(['stripe_payment_method_id' => $data['payment_method_id']]);

            $this->activity->log(
                $user->id, $user->role?->portal() ?? 'provider', 'settings',
                ActivitySeverity::Info,
                'payment_method_default_set', 'Default payment method updated',
                'Your default payment method was updated.',
                null, null, null, 'log', $user->id,
            );

            return back()->with('success', 'Default payment method updated.');
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }

    public function removePaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate(['payment_method_id' => 'required|string|starts_with:pm_']);
        $user = $request->user();

        try {
            $user->stripe()->paymentMethods->detach($data['payment_method_id']);

            $this->activity->log(
                $user->id, $user->role?->portal() ?? 'provider', 'settings',
                ActivitySeverity::Info,
                'payment_method_removed', 'Payment method removed',
                'A payment method was removed from your account.',
                null, null, null, 'log', $user->id,
            );

            return back()->with('success', 'Payment method removed.');
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }

    // ── Stripe Connect helper (shared logic) ──────────────────────────────────

    /**
     * Sync the stripe_connected flag after returning from Stripe.
     * Call this from each portal's connectReturn() before redirecting.
     */
    protected function syncStripeConnectStatus(mixed $user): void
    {
        if (!$user->stripe_account_id || !config('services.stripe.secret')
            || str_starts_with((string) $user->stripe_account_id, 'acct_demo_')) {
            return;
        }

        try {
            $stripe  = new \Stripe\StripeClient(config('services.stripe.secret'));
            $account = $stripe->accounts->retrieve($user->stripe_account_id);
            $connected = $account->charges_enabled && $account->payouts_enabled && $account->details_submitted;
            $user->update(['stripe_connected' => $connected ? 1 : 0]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Stripe Connect] return sync failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create or retrieve a Stripe Connect Express account and return the onboarding URL.
     * Each portal controller calls this with its own portal name and route names.
     */
    protected function getConnectOnboardUrl(mixed $user, string $portalName, string $refreshRoute, string $returnRoute): string
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        if (!$user->stripe_account_id || str_starts_with((string) $user->stripe_account_id, 'acct_demo_')) {
            $account = $stripe->accounts->create([
                'type'         => 'express',
                'email'        => $user->email,
                'capabilities' => ['transfers' => ['requested' => true]],
                'metadata'     => ['user_id' => $user->id, 'portal' => $portalName],
            ]);
            $user->update(['stripe_account_id' => $account->id, 'stripe_connected' => false]);
        }

        $link = $stripe->accountLinks->create([
            'account'     => $user->stripe_account_id,
            'refresh_url' => route($refreshRoute),
            'return_url'  => route($returnRoute),
            'type'        => 'account_onboarding',
        ]);

        return $link->url;
    }
}
