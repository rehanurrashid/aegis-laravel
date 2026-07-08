<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\UserMeta;
use App\Models\UserSession;
use App\Services\ActivityService;
use App\Services\ProfileService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private ActivityService $activity,
        private ProfileService $profiles,
        private SubscriptionService $subscriptions,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user()->load('meta', 'sessions');

        // Return ALL meta so Vue can populate every panel
        $meta = $user->meta->pluck('typed_value', 'meta_key')->toArray();

        // Active sessions (not revoked)
        $currentSessionId = $request->session()->getId();

        $sessions = UserSession::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn ($s) => [
                'id'         => $s->id,
                'device'     => $s->device_label ?? 'Unknown device',
                'ip'         => $s->ip_address,
                'user_agent' => $s->user_agent,
                'last_seen_at' => $s->last_seen_at?->diffForHumans(),
                'created_at'   => $s->created_at?->toDateString(),
                'is_current'   => $s->session_token === $currentSessionId,
            ])
            // Sort: current first, then by last_seen_at desc
            ->sortByDesc(fn ($s) => [$s['is_current'] ? 1 : 0, $s['last_seen_at']])
            ->values();

        // Enrich user with computed fields Vue expects
        $userArr = $user->toArray();
        $userArr['mfa_enabled']          = (bool) $user->two_factor_enabled;
        $userArr['mfa_method']           = $user->mfaToken?->method ?? '';
        $userArr['has_cs_portal']         = $user->meta()->where('meta_key', 'has_cs_portal')->value('meta_value') === '1';
        $userArr['has_ss_portal']         = $user->meta()->where('meta_key', 'has_ss_portal')->value('meta_value') === '1';
        $userArr['tier']                  = $user->tier?->value ?? null;
        // Founding member = among the first 100 practitioners to register on the platform.
        // Determined by counting practitioners registered before this user.
        $userArr['is_founding_member']    = \App\Models\User::where('role', 'practitioner')
            ->where('created_at', '<', $user->created_at)
            ->count() < 100;

        return Inertia::render('Provider/Settings', [
            'user'         => $userArr,
            'meta'         => $meta,
            'mfaEnabled'   => (bool) $user->two_factor_enabled,
            'mfaMethod'    => $user->mfaToken?->method ?? '',
            'sessions'     => $sessions,
            'subscription' => $this->subscriptions->getFullSubscriptionData($user),
            'pricing'      => config('aegis.pricing'),
        ]);
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'phone' => $data['phone'] ?? null,
        ])->save();

        $this->activity->log(
            $user->id,
            $user->role?->portal() ?? 'provider',
            'account',
            \App\Enums\ActivitySeverity::Info,
            'phone_updated',
            'Phone number updated',
            'You updated your contact phone number.',
            null, null, null,
            'log',
            $user->id,
        );

        return back()->with('success', 'Account details updated.');
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
            // Channel-level from notification table
            'prefs'           => 'nullable|array',
            'categories'      => 'nullable|array',
        ]);

        $user = $request->user();

        // Save simple boolean notify_* keys
        foreach ($data as $key => $val) {
            if (str_starts_with($key, 'notify_') && is_bool($val) || is_string($val)) {
                $this->profiles->saveMeta($user, $key, $val ? '1' : '0', 'string');
            }
        }

        // Save quiet hours + digest prefs
        if (!empty($data['prefs'])) {
            $this->profiles->saveMeta($user, 'notify_prefs', $data['prefs'], 'json');
        }

        // Save per-category channel matrix
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

    public function revokeSession(Request $request, string $session): RedirectResponse
    {
        $user = $request->user();
        $this->profiles->revokeSession($user, $session);
        return back()->with('success', 'Session revoked.');
    }

    public function revokeAllSessions(Request $request): RedirectResponse
    {
        $user  = $request->user();
        // Keep the current Laravel session intact; revoke all UserSession records
        $this->profiles->revokeAllSessions($user);
        return back()->with('success', 'All sessions revoked.');
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $data = $request->validate(['confirm' => 'required|string|in:DELETE MY ACCOUNT']);

        $user = $request->user();
        $user->update(['deactivated_at' => now()]);
        $user->tokens()->delete();
        auth()->logout();

        return redirect('/login')->with('success', 'Account closed.');
    }

    // ── Subscription management ──────────────────────────────────────────

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

    public function swapPlan(Request $request): RedirectResponse
    {
        $data = $request->validate(['price_id' => ['required', 'string', 'starts_with:price_']]);
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

    public function cancelPlan(Request $request): RedirectResponse
    {
        try {
            $this->subscriptions->cancel($request->user());
            return back()->with('success', 'Your subscription will end at the current billing period.');
        } catch (\Throwable $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    public function resumePlan(Request $request): RedirectResponse
    {
        try {
            $this->subscriptions->reactivate($request->user());
            return back()->with('success', 'Your subscription has been reactivated.');
        } catch (\Throwable $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    public function toggleMaat(Request $request): RedirectResponse
    {
        $data = $request->validate(['enable' => ['required', 'boolean']]);
        $user = $request->user();

        if ($data['enable'] && $user->tier?->value !== 'practice') {
            return back()->withErrors(['maat' => 'MAAT requires Continuity Practice tier.']);
        }

        $sub     = $user->subscription('default');
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
            $msg = $data['enable'] ? 'MAAT Professional CS Service added.' : 'MAAT Professional CS Service removed.';
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withErrors(['maat' => $e->getMessage()]);
        }
    }

    // ── Payment methods ───────────────────────────────────────────────────

    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => 'required|string|starts_with:pm_',
            'set_default'       => 'nullable|boolean',
        ]);
        $user = $request->user();
        try {
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer(['name' => $user->display_name, 'email' => $user->email]);
            }
            if (!empty($data['set_default'])) {
                $user->updateDefaultPaymentMethod($data['payment_method_id']);
            } else {
                $user->addPaymentMethod($data['payment_method_id']);
            }
            return back()->with('success', 'Payment method saved.');
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => 'Could not save payment method. ' . $e->getMessage()]);
        }
    }

    public function setDefaultPaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate(['payment_method_id' => 'required|string|starts_with:pm_']);
        try {
            $this->subscriptions->setDefaultPaymentMethod($request->user(), $data['payment_method_id']);
            return back()->with('success', 'Default payment method updated.');
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }

    public function removePaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate(['payment_method_id' => 'required|string|starts_with:pm_']);
        try {
            $this->subscriptions->removePaymentMethod($request->user(), $data['payment_method_id']);
            return back()->with('success', 'Payment method removed.');
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }
}
