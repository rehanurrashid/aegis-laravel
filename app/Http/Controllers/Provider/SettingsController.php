<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\UserMeta;
use App\Models\UserSession;
use App\Services\ActivityService;
use App\Events\Business\SubscriptionTierChanged;
use App\Events\Business\SubscriptionCancelled;
use App\Events\Business\MaatAddonChanged;
use App\Events\Auth\AccountClosed;
use App\Services\ProfileService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
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
        $user = $request->user()->load('meta', 'sessions', 'continuityPlans.stewards.steward');

        // Return ALL meta so Vue can populate every panel
        $meta = $user->meta->pluck('typed_value', 'meta_key')->toArray();

        // Synthesize privacy_prefs from user columns + individual meta keys if not yet saved as blob
        if (empty($meta['privacy_prefs'])) {
            $meta['privacy_prefs'] = [
                'level'         => $user->practitioner_public ? 'public' : 'network',
                'search'        => true,
                'networkShow'   => true,
                'ratings'       => isset($meta['show_ratings'])       ? (bool) $meta['show_ratings']       : true,
                'location'      => true,
                'referralStats' => isset($meta['show_referral_stats']) ? (bool) $meta['show_referral_stats'] : true,
                'demographics'  => isset($meta['show_demographics'])   ? (bool) $meta['show_demographics']   : true,
            ];
        }

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

        // Active steward agreements for Agreements & Contracts panel
        $activeAgreements = collect();
        foreach ($user->continuityPlans as $plan) {
            foreach ($plan->stewards()->where('status', 'active')->with('steward')->get() as $ps) {
                $stewardName = $ps->steward?->display_name ?? 'Unknown';
                $roleLabel   = match($ps->role instanceof \BackedEnum ? $ps->role->value : (string) $ps->role) {
                    'continuity' => 'Continuity Steward Agreement',
                    'support'    => 'Support Steward Agreement',
                    default      => ucfirst((string) ($ps->role instanceof \BackedEnum ? $ps->role->value : $ps->role)) . ' Steward Agreement',
                };
                $signedNote  = $ps->signed_at
                    ? 'Signed ' . \Carbon\Carbon::parse($ps->signed_at)->format('M j, Y')
                    : 'Pending signature';
                $reviewNote  = $ps->review_due_at
                    ? ' · Due ' . \Carbon\Carbon::parse($ps->review_due_at)->format('M j, Y')
                    : ' · Reviewed annually';
                $activeAgreements->push([
                    'title'  => $roleLabel,
                    'meta'   => "{$stewardName} — {$signedNote}{$reviewNote}",
                    'status' => 'active',
                ]);
            }
        }

        return Inertia::render('Provider/Settings', [
            'user'             => $userArr,
            'meta'             => $meta,
            'mfaEnabled'       => (bool) $user->two_factor_enabled,
            'mfaMethod'        => $user->mfaToken?->method ?? '',
            'sessions'         => $sessions,
            'subscription'     => $this->subscriptions->getFullSubscriptionData($user),
            'pricing'          => config('aegis.pricing'),
            'accountPaused'    => $user->meta->where('meta_key', 'account_paused')->value('meta_value') === '1',
            'pausedUntil'      => $user->getMeta ? optional($user->meta->where('meta_key','pause_prefs')->first())?->typed_value['until'] ?? null : null,
            'activeAgreements' => $activeAgreements->values(),
            'paymentMethods'   => $this->fetchPaymentMethods($user),
        ]);
    }


    /**
     * Fetch all Stripe cards for this user.
     * User::paymentMethods() shadows Cashier — call Stripe directly.
     */
    private function fetchPaymentMethods(\App\Models\User $user): array
    {
        if (!$user->hasStripeId()) {
            return [];
        }
        try {
            $stripe      = $user->stripe();
            $pmList      = $stripe->paymentMethods->all(['customer' => $user->stripe_id, 'type' => 'card']);
            $defaultPmId = $user->stripe_payment_method_id
                ?? ($stripe->customers->retrieve($user->stripe_id)->invoice_settings->default_payment_method ?? null);

            return collect($pmList->data)->map(fn ($pm) => [
                'id'          => $pm->id,
                'brand'       => $pm->card->brand ?? 'card',
                'last4'       => $pm->card->last4 ?? '••••',
                'exp_month'   => $pm->card->exp_month ?? null,
                'exp_year'    => $pm->card->exp_year ?? null,
                'is_default'  => $pm->id === $defaultPmId,
                'method_type' => 'card',
            ])->sortByDesc('is_default')->values()->toArray();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[SettingsController] fetchPaymentMethods failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            return [];
        }
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
            'categories'       => 'nullable|array',
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

    public function updateSecurityAlerts(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'alertOnNewLogin' => 'nullable|boolean',
            'sessionTimeout'  => 'nullable|boolean',
        ]);
        $this->profiles->saveMeta($request->user(), 'notify_security', $data, 'json');
        return back()->with('success', 'Security alert settings saved.');
    }

    public function updateCsSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'activation'     => 'nullable|boolean',
            'annualReminder' => 'nullable|boolean',
            'notifyOnChange' => 'nullable|boolean',
        ]);
        $this->profiles->saveMeta($request->user(), 'notify_cs', $data, 'json');
        return back()->with('success', 'Continuity Steward settings saved.');
    }

    public function updateSsSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notifyIncident' => 'nullable|boolean',
            'notifyChange'   => 'nullable|boolean',
            'annualAttest'   => 'nullable|boolean',
        ]);
        $this->profiles->saveMeta($request->user(), 'notify_ss', $data, 'json');
        return back()->with('success', 'Support Steward settings saved.');
    }

    public function updateVaultAlerts(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notifyAccess' => 'nullable|boolean',
            'notifyUnlock' => 'nullable|boolean',
        ]);
        $this->profiles->saveMeta($request->user(), 'notify_vault', $data, 'json');
        return back()->with('success', 'Vault alert settings saved.');
    }

    public function updateAgreementAlerts(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'expiryReminder'    => 'nullable|boolean',
            'notifyCountersign' => 'nullable|boolean',
        ]);
        $this->profiles->saveMeta($request->user(), 'notify_agreements', $data, 'json');
        return back()->with('success', 'Agreement alert settings saved.');
    }

    public function updateNetworkSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'requireApproval'  => 'nullable|boolean',
            'dataUse'          => 'nullable|boolean',
            'hideFromBP'       => 'nullable|boolean',
            'connectionAlerts' => 'nullable|boolean',
            'weeklyDigest'     => 'nullable|boolean',
            'featureUpdates'   => 'nullable|boolean',
            'geoFocus'         => 'nullable|string|in:25mi,50mi,100mi,statewide,national',
        ]);
        $this->profiles->saveMeta($request->user(), 'notify_network', $data, 'json');
        return back()->with('success', 'Network settings saved.');
    }

    public function updateServicesSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode'           => 'nullable|boolean',
            'showPublic'     => 'nullable|boolean',
            'acceptBookings' => 'nullable|boolean',
            'showPricing'    => 'nullable|boolean',
            'bpDiscoverable' => 'nullable|boolean',
            'bookingExpiry'  => 'nullable|string|in:24h,48h,72h,1week',
            'sessionBuffer'  => 'nullable|string|in:none,15min,30min,1hr',
            'hourlyRate'     => 'nullable|numeric|min:0|max:9999',
        ]);

        $user = $request->user();

        // Store full prefs blob
        $this->profiles->saveMeta($user, 'services_prefs', $data, 'json');

        // Mirror the services mode toggle to the meta key used by the sidebar/profile badge
        if (array_key_exists('mode', $data)) {
            $this->profiles->saveMeta($user, 'services_mode', $data['mode'] ? '1' : '0', 'string');
        }

        $this->activity->log(
            $user->id,
            'provider',
            'account',
            \App\Enums\ActivitySeverity::Info,
            'services_settings_updated',
            'Services settings updated',
            'You updated your My Services preferences.',
            null, null, null,
            'log',
            $user->id,
        );

        return back()->with('success', 'Services settings saved.');
    }

    public function updatePrivacySettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'level'         => 'nullable|string|in:public,network,private',
            'search'        => 'nullable|boolean',
            'networkShow'   => 'nullable|boolean',
            'ratings'       => 'nullable|boolean',
            'location'      => 'nullable|boolean',
            'referralStats' => 'nullable|boolean',
            'demographics'  => 'nullable|boolean',
        ]);

        $user = $request->user();

        // Store full prefs blob
        $this->profiles->saveMeta($user, 'privacy_prefs', $data, 'json');

        // Mirror individual keys used by public profile service
        if (array_key_exists('ratings', $data)) {
            $this->profiles->saveMeta($user, 'show_ratings', $data['ratings'] ? '1' : '0', 'string');
        }
        if (array_key_exists('referralStats', $data)) {
            $this->profiles->saveMeta($user, 'show_referral_stats', $data['referralStats'] ? '1' : '0', 'string');
        }
        if (array_key_exists('demographics', $data)) {
            $this->profiles->saveMeta($user, 'show_demographics', $data['demographics'] ? '1' : '0', 'string');
        }

        // Mirror visibility level to the three practitioner_public / cs_public columns
        if (!empty($data['level'])) {
            $isPublic = $data['level'] === 'public';
            $user->update([
                'practitioner_public'     => $isPublic || $data['level'] === 'network',
                'cs_public'               => $isPublic || $data['level'] === 'network',
                'business_partner_public' => $isPublic,
            ]);
        }

        $this->activity->log(
            $user->id,
            'provider',
            'account',
            \App\Enums\ActivitySeverity::Info,
            'privacy_settings_updated',
            'Privacy settings updated',
            'You updated your privacy and visibility preferences.',
            null, null, null,
            'log',
            $user->id,
        );

        return back()->with('success', 'Privacy settings saved.');
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
        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'provider', 'settings',
            \App\Enums\ActivitySeverity::Warning,
            'session_revoked', 'Session revoked',
            'A session was remotely terminated from your security settings.',
            null, null, null, 'log', $user->id,
        );
        return back()->with('success', 'Session revoked.');
    }

    public function revokeAllSessions(Request $request): RedirectResponse
    {
        $user  = $request->user();
        // Keep the current Laravel session intact; revoke all UserSession records
        $this->profiles->revokeAllSessions($user);
        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'provider', 'settings',
            \App\Enums\ActivitySeverity::Warning,
            'all_sessions_revoked', 'All sessions terminated',
            'All active sessions were signed out from your security settings.',
            null, null, null, 'log', $user->id,
        );
        return back()->with('success', 'All sessions revoked.');
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate(['confirm' => 'required|string|in:DELETE MY ACCOUNT']);

        $user = $request->user();
        $user->update(['deactivated_at' => now()]);
        $user->tokens()->delete();

        $this->activity->log(
            $user->id, 'provider', 'account',
            \App\Enums\ActivitySeverity::Warning,
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

        // Treat empty string as null for until
        $data['until'] = !empty($data['until']) ? $data['until'] : null;

        $user = $request->user();
        // Store pause state in user_meta (paused_at is on subscriptions table, not users)
        $this->profiles->saveMeta($user, 'account_paused', '1', 'string');
        $this->profiles->saveMeta($user, 'pause_prefs', [
            'until'   => $data['until']   ?? null,
            'reason'  => $data['reason']  ?? 'other',
            'message' => $data['message'] ?? '',
        ], 'json');

        $this->activity->log(
            $user->id, 'provider', 'account',
            \App\Enums\ActivitySeverity::Warning,
            'account_paused', 'Account paused',
            'Your account has been paused. You will not appear in searches or receive referrals.',
            null, null, null, 'log', $user->id,
        );

        return back()->with('success', 'Account paused successfully. You can reactivate at any time.');
    }

    public function resumeAccount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->profiles->saveMeta($user, 'account_paused', '0', 'string');

        $this->activity->log(
            $user->id, 'provider', 'account',
            \App\Enums\ActivitySeverity::Info,
            'account_resumed', 'Account reactivated',
            'Your account is active again.',
            null, null, null, 'log', $user->id,
        );

        return back()->with('success', 'Account reactivated. You are now visible in search results.');
    }

    public function exportData(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'include' => 'required|array|min:1',
            'include.*' => 'string|in:profile,referrals,documents,agreements,network,activity,messages,billing',
            'format' => 'required|string|in:json,csv',
        ]);

        $user = $request->user();

        $this->activity->log(
            $user->id, 'provider', 'account',
            \App\Enums\ActivitySeverity::Info,
            'data_export_requested', 'Data export requested',
            'A HIPAA-compliant export of your data has been requested and will be emailed within 24 hours.',
            null, null, null, 'log', $user->id,
        );

        // TODO: dispatch ExportUserDataJob::dispatch($user, $data['include'], $data['format']);

        return back()->with('success', 'Export request submitted. Your data will be emailed to ' . $user->email . ' within 24 hours.');
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
        $user = $request->user();
        try {
            $result = $this->subscriptions->changePlan($user, $data['price_id']);
            $msg = match ($result['direction']) {
                'upgrade'   => 'Plan upgraded. Prorated charge added to your account.',
                'downgrade' => 'Plan will change at the next billing cycle.',
                default     => 'Plan unchanged.',
            };

            $actionMap = [
                'upgrade'        => ['subscription_upgraded',   'Plan upgraded',   'You upgraded your Aegis subscription plan.'],
                'downgrade'      => ['subscription_downgraded', 'Plan downgraded', 'You downgraded your Aegis subscription plan. Change takes effect at next cycle.'],
                'switch-annual'  => ['subscription_changed',    'Billing changed', 'You switched to annual billing.'],
                'switch-monthly' => ['subscription_changed',    'Billing changed', 'You switched to monthly billing.'],
            ];
            [$action, $title, $desc] = $actionMap[$result['direction']] ?? ['subscription_changed', 'Plan changed', $msg];

            $this->activity->log(
                $user->id, 'provider', 'account',
                \App\Enums\ActivitySeverity::Info,
                $action, $title, $desc,
                \App\Models\User::class, $user->id,
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
                $user->id, 'provider', 'account',
                \App\Enums\ActivitySeverity::Warning,
                'subscription_cancelled', 'Subscription cancelled',
                'You cancelled your Aegis subscription. Access continues until the end of the current billing period.',
                \App\Models\User::class, $user->id,
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
                $user->id, 'provider', 'account',
                \App\Enums\ActivitySeverity::Info,
                'subscription_reactivated', 'Subscription reactivated',
                'You reactivated your Aegis subscription.',
                \App\Models\User::class, $user->id,
                null, 'log', $user->id,
            );

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

            $this->activity->log(
                $user->id, 'provider', 'account',
                \App\Enums\ActivitySeverity::Info,
                $data['enable'] ? 'maat_addon_added' : 'maat_addon_removed',
                $data['enable'] ? 'MAAT add-on activated' : 'MAAT add-on removed',
                $msg,
                \App\Models\User::class, $user->id,
                null, 'log', $user->id,
            );

            event(new MaatAddonChanged($user, $data['enable'] ? 'active' : 'removed'));

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
            // Always attach first — updateDefaultPaymentMethod can fail on unattached PMs
            $user->addPaymentMethod($data['payment_method_id']);
            if (!empty($data['set_default'])) {
                $user->updateDefaultPaymentMethod($data['payment_method_id']);
                $user->update(['stripe_payment_method_id' => $data['payment_method_id']]);
            }
            $this->activity->log(
                $user->id, $user->role?->portal() ?? 'provider', 'settings',
                \App\Enums\ActivitySeverity::Info,
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
            $this->subscriptions->setDefaultPaymentMethod($user, $data['payment_method_id']);
            $this->activity->log(
                $user->id, $user->role?->portal() ?? 'provider', 'settings',
                \App\Enums\ActivitySeverity::Info,
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
            $this->subscriptions->removePaymentMethod($user, $data['payment_method_id']);
            $this->activity->log(
                $user->id, $user->role?->portal() ?? 'provider', 'settings',
                \App\Enums\ActivitySeverity::Info,
                'payment_method_removed', 'Payment method removed',
                'A payment method was removed from your account.',
                null, null, null, 'log', $user->id,
            );
            return back()->with('success', 'Payment method removed.');
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }
    // ── Stripe Connect Express Onboarding ────────────────────────────────────

    /**
     * Generate a Stripe Connect Express AccountLink and redirect the user.
     * If no Connect account exists yet, create one first.
     */
    public function connectOnboard(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!config('services.stripe.secret')) {
            return back()->withErrors(['connect' => 'Stripe is not configured. Set STRIPE_SECRET in .env.']);
        }

        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

            // Create Express account if none exists yet
            if (!$user->stripe_account_id || str_starts_with((string) $user->stripe_account_id, 'acct_demo_')) {
                $account = $stripe->accounts->create([
                    'type'         => 'express',
                    'email'        => $user->email,
                    'capabilities' => ['transfers' => ['requested' => true]],
                    'metadata'     => ['user_id' => $user->id, 'portal' => 'provider'],
                ]);
                $user->update(['stripe_account_id' => $account->id, 'stripe_connected' => false]);
            }

            $accountLink = $stripe->accountLinks->create([
                'account'     => $user->stripe_account_id,
                'refresh_url' => route('provider.settings.connect.onboard'),
                'return_url'  => route('provider.settings.connect.return'),
                'type'        => 'account_onboarding',
            ]);

            return redirect($accountLink->url);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[Stripe Connect] onboard failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return back()->withErrors(['connect' => 'Could not start Stripe Connect setup. ' . $e->getMessage()]);
        }
    }

    /**
     * Return URL after Stripe Connect Express onboarding completes.
     * Stripe does NOT send account.updated immediately — we check manually and sync.
     */
    public function connectReturn(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->stripe_account_id && config('services.stripe.secret')
            && !str_starts_with((string) $user->stripe_account_id, 'acct_demo_')) {
            try {
                $stripe  = new \Stripe\StripeClient(config('services.stripe.secret'));
                $account = $stripe->accounts->retrieve($user->stripe_account_id);
                $connected = $account->charges_enabled && $account->payouts_enabled && $account->details_submitted;
                $user->update(['stripe_connected' => $connected ? 1 : 0]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[Stripe Connect] return sync failed', ['error' => $e->getMessage()]);
            }
        }

        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'provider', 'settings',
            \App\Enums\ActivitySeverity::Info,
            'stripe_connect_completed', 'Stripe Connect linked',
            'Your Stripe Connect account was successfully linked.',
            null, null, null, 'log', $user->id,
        );

        return redirect()->route('provider.settings.index', ['section' => 'stripe_connect'])
            ->with('success', 'Stripe Connect setup complete. Your account is now active for receiving payments.');
    }

    public function updateReferral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'accepting'   => 'boolean',
            'autoAccept'  => 'boolean',
            'suggestAlts' => 'boolean',
            'autoArchive' => 'boolean',
        ]);
        $user = $request->user();
        $this->profiles->saveMeta($user, 'referral_prefs', $data, 'json');
        // Mirror network_accepting for search/referral visibility
        if (array_key_exists('accepting', $data)) {
            $this->profiles->saveMeta($user, 'network_accepting', $data['accepting'] ? '1' : '0', 'string');
        }
        return back()->with('success', 'Referral preferences saved.');
    }

}
