<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Http\Controllers\Controller;
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
        $user = $request->user()->load('meta', 'sessions', 'linkedProvider');
        $meta = $user->meta->pluck('typed_value', 'meta_key')->toArray();

        $currentSessionId = $request->session()->getId();

        $sessions = UserSession::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn ($s) => [
                'id'           => $s->id,
                'device'       => $s->device_label ?? 'Unknown device',
                'ip'           => $s->ip_address,
                'last_seen_at' => $s->last_seen_at?->diffForHumans(),
                'created_at'   => $s->created_at?->toDateString(),
                'is_current'   => $s->session_token === $currentSessionId,
            ])
            ->sortByDesc(fn ($s) => [$s['is_current'] ? 1 : 0])
            ->values();

        $userArr = $user->toArray();
        $userArr['mfa_enabled']                = (bool) $user->two_factor_enabled;
        $userArr['mfa_method']                 = $user->mfaToken?->method ?? '';
        $userArr['linked_provider_name']       = $user->linkedProvider?->display_name;
        $userArr['linked_provider_tier']       = $user->linkedProvider?->tier?->value;
        $userArr['linked_provider_tier_label'] = match ($user->linkedProvider?->tier?->value) {
            'practice' => 'Continuity Practice',
            'access'   => 'Continuity Access',
            default    => null,
        };
        $userArr['cs_account_type']    = $user->cs_account_type?->value ?? 'invited';
        $userArr['cs_assignment_role'] = $meta['cs_assignment_role'] ?? 'Primary';

        // Only Business CS has a paid subscription
        $csAccountType = $user->cs_account_type instanceof \BackedEnum
            ? $user->cs_account_type->value
            : (string) ($user->cs_account_type ?? 'invited');

        $subscription = null;
        if ($csAccountType === 'business') {
            $subscription = $this->subscriptions->getFullSubscriptionData($user);
        }

        return Inertia::render('ContinuitySteward/Settings', [
            'user'         => $userArr,
            'meta'         => $meta,
            'mfaEnabled'   => (bool) $user->two_factor_enabled,
            'mfaMethod'    => $user->mfaToken?->method ?? '',
            'sessions'     => $sessions,
            'subscription' => $subscription,
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
            'categories'                  => 'nullable|array',
            'categories.*.key'            => 'required|string|max:64',
            'categories.*.push'           => 'nullable|boolean',
            'categories.*.email'          => 'nullable|boolean',
            'categories.*.inapp'          => 'nullable|boolean',
            // Security & Login Alerts
            'security.alertOnNewLogin'    => 'nullable|boolean',
            'security.sessionTimeout'     => 'nullable|boolean',
            // Assignment & Coverage
            'assignment.incidentTrigger'  => 'nullable|boolean',
            'assignment.planChanges'      => 'nullable|boolean',
            'assignment.attestation'      => 'nullable|boolean',
            'assignment.agreements'       => 'nullable|boolean',
            'assignment.coverageActivation' => 'nullable|boolean',
            'assignment.checkinReminder'  => 'nullable|boolean',
            // Document alerts
            'docs.documentAccess'         => 'nullable|boolean',
            // Billing
            'billing.invoiceEmails'       => 'nullable|boolean',
            // Network & Updates
            'network.connectionAlerts'    => 'nullable|boolean',
            'network.weeklyDigest'        => 'nullable|boolean',
            'network.featureUpdates'      => 'nullable|boolean',
        ]);

        $user = $request->user();

        if (!empty($data['categories'])) {
            $this->profiles->saveMeta($user, 'notify_categories', $data['categories'], 'json');
        }

        $groups = ['security', 'assignment', 'docs', 'billing', 'network'];
        foreach ($groups as $group) {
            if (isset($data[$group]) && is_array($data[$group])) {
                $this->profiles->saveMeta($user, "notify_{$group}", $data[$group], 'json');
            }
        }

        return back()->with('success', 'Notification preferences saved.');
    }

    public function updateMessaging(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'who'          => 'nullable|string|in:assigned,any,none',
            'status'       => 'nullable|string|in:available,busy,away,off',
            'readReceipts' => 'nullable|boolean',
            'onlineStatus' => 'nullable|boolean',
            'awayText'     => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $this->profiles->saveMeta($user, 'messaging_prefs', $data, 'json');
        return back()->with('success', 'Messaging settings saved.');
    }

    public function updateEmailPrefs(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'digestFreq'     => 'nullable|string|in:daily,weekly,monthly,never',
            'digest'         => 'nullable|boolean',
            'activityDigest' => 'nullable|boolean',
            'productUpdates' => 'nullable|boolean',
            'unsubAll'       => 'nullable|boolean',
        ]);

        $user = $request->user();
        $this->profiles->saveMeta($user, 'email_prefs', $data, 'json');
        return back()->with('success', 'Email preferences saved.');
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
        $this->profiles->revokeSession($request->user(), $session);
        return back()->with('success', 'Session revoked.');
    }

    public function revokeAllSessions(Request $request): RedirectResponse
    {
        $this->profiles->revokeAllSessions($request->user());
        return back()->with('success', 'All sessions revoked.');
    }

    public function swapPlan(Request $request): RedirectResponse
    {
        $user   = $request->user();
        $csType = $user->cs_account_type instanceof \BackedEnum
            ? $user->cs_account_type->value
            : 'invited';

        if ($csType !== 'business') {
            return back()->withErrors(['subscription' => 'Only Business CS accounts have a paid subscription.']);
        }

        $data = $request->validate(['price_id' => ['required', 'string', 'starts_with:price_']]);

        try {
            $result = $this->subscriptions->changePlan($user, $data['price_id']);
            $msg = match ($result['direction']) {
                'upgrade'   => 'Plan updated successfully.',
                'downgrade' => 'Plan will change at your next billing cycle.',
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

    public function billingPortal(Request $request): RedirectResponse
    {
        try {
            $url = $this->subscriptions->billingPortalUrl(
                $request->user(),
                route('cs.settings.index') . '?section=billing'
            );
            return redirect()->away($url);
        } catch (\Throwable $e) {
            return back()->withErrors(['stripe' => 'Could not open billing portal. Please try again.']);
        }
    }
    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate(['confirm' => 'required|string|in:DELETE MY ACCOUNT']);
        $user = $request->user();
        $user->update(['deactivated_at' => now()]);
        $user->tokens()->delete();
        $this->activity->log(
            $user->id, 'cs', 'account',
            \App\Enums\ActivitySeverity::Warning,
            'account_deleted', 'Account deletion requested',
            'Account queued for permanent deletion in 30 days.',
            null, null, null, 'log', $user->id,
        );
        auth()->logout();
        return redirect()->route('login')->with('success', 'Your account has been scheduled for deletion. You have 30 days to contact support to cancel.');
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
            'until'   => $data['until'],
            'reason'  => $data['reason']  ?? 'other',
            'message' => $data['message'] ?? '',
        ], 'json');
        $this->activity->log(
            $user->id, 'cs', 'account',
            \App\Enums\ActivitySeverity::Warning,
            'account_paused', 'Account paused',
            'Account paused. Will not appear in searches or receive new assignments.',
            null, null, null, 'log', $user->id,
        );
        return back()->with('success', 'Account paused. You can reactivate at any time from Settings.');
    }

    public function resumeAccount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->profiles->saveMeta($user, 'account_paused', '0', 'string');
        $this->activity->log(
            $user->id, 'cs', 'account',
            \App\Enums\ActivitySeverity::Info,
            'account_resumed', 'Account reactivated',
            'Account is active again.',
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
            $user->id, 'cs', 'account',
            \App\Enums\ActivitySeverity::Info,
            'data_export_requested', 'Data export requested',
            'HIPAA-compliant data export requested.',
            null, null, null, 'log', $user->id,
        );
        return back()->with('success', 'Export request submitted. Your data will be emailed to ' . $user->email . ' within 24 hours.');
    }

    // ── Stripe Connect Express Onboarding ────────────────────────────────────

    public function connectOnboard(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!config('services.stripe.secret')) {
            return back()->withErrors(['connect' => 'Stripe is not configured.']);
        }

        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

            if (!$user->stripe_account_id || str_starts_with((string) $user->stripe_account_id, 'acct_demo_')) {
                $account = $stripe->accounts->create([
                    'type'         => 'express',
                    'email'        => $user->email,
                    'capabilities' => ['transfers' => ['requested' => true]],
                    'metadata'     => ['user_id' => $user->id, 'portal' => 'continuity_steward'],
                ]);
                $user->update(['stripe_account_id' => $account->id, 'stripe_connected' => false]);
            }

            $link = $stripe->accountLinks->create([
                'account'     => $user->stripe_account_id,
                'refresh_url' => route('cs.settings.connect.onboard'),
                'return_url'  => route('cs.settings.connect.return'),
                'type'        => 'account_onboarding',
            ]);

            return redirect($link->url);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[Stripe Connect CS] onboard failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['connect' => 'Could not start Stripe Connect setup: ' . $e->getMessage()]);
        }
    }

    public function connectReturn(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if ($user->stripe_account_id && config('services.stripe.secret')
            && !str_starts_with((string) $user->stripe_account_id, 'acct_demo_')) {
            try {
                $stripe  = new \Stripe\StripeClient(config('services.stripe.secret'));
                $account = $stripe->accounts->retrieve($user->stripe_account_id);
                $user->update([
                    'stripe_connected' => ($account->charges_enabled && $account->payouts_enabled && $account->details_submitted) ? 1 : 0,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[Stripe Connect CS] return sync failed', ['error' => $e->getMessage()]);
            }
        }

        return redirect()->route('cs.settings.index', ['tab' => 'integrations'])
            ->with('success', 'Stripe Connect setup complete. You can now receive payments from providers.');
    }

    public function updateRolePrefs(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'show_on_profile' => 'boolean',
            'vault_access'    => 'boolean',
        ]);
        $user = $request->user();
        $this->profiles->saveMeta($user, 'cs_role_prefs', $data, 'json');
        return back()->with('success', 'CS settings saved.');
    }

    public function updateVaultPrefs(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'notify_access' => 'boolean',
            'notify_unlock' => 'boolean',
        ]);
        $user = $request->user();
        $this->profiles->saveMeta($user, 'vault_notify_prefs', $data, 'json');
        return back()->with('success', 'Vault notification preferences saved.');
    }

    public function updatePrivacy(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'level'    => 'required|string|in:public,network,private',
            'search'   => 'boolean',
            'location' => 'boolean',
            'creds'    => 'boolean',
        ]);
        $user = $request->user();
        $this->profiles->saveMeta($user, 'cs_privacy', $data, 'json');
        return back()->with('success', 'Privacy settings saved.');
    }

    public function storePaymentMethod(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => 'required|string|starts_with:pm_',
            'set_default'       => 'boolean',
        ]);
        $user = $request->user();

        // Demo guard
        if (str_starts_with($data['payment_method_id'], 'pm_demo_')) {
            return back()->with('success', 'Demo payment method saved (not sent to Stripe).');
        }

        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer(['name' => $user->display_name, 'email' => $user->email]);
        }

        if (!empty($data['set_default'])) {
            $user->updateDefaultPaymentMethod($data['payment_method_id']);
            $user->update(['stripe_payment_method_id' => $data['payment_method_id']]);
        } else {
            $user->addPaymentMethod($data['payment_method_id']);
        }
        return back()->with('success', 'Payment method saved.');
    }

}
