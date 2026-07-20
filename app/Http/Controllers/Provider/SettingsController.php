<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasCommonSettingsMethods;
use App\Models\UserMeta;
use App\Models\UserSession;
use App\Services\ActivityService;
use App\Events\Business\CsAddonChanged;
use App\Events\Business\MaatAddonChanged;
use App\Services\ProfileService;
use App\Services\SecurityCompletionService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    use HasCommonSettingsMethods;
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

        $security = app(SecurityCompletionService::class)->compute($user);

        return Inertia::render('Provider/Settings', [
            'user'                   => $userArr,
            'meta'                   => $meta,
            'mfaEnabled'             => (bool) $user->two_factor_enabled,
            'securityCompletion'     => $security['pct'],
            'securityItemsRemaining' => $security['items_remaining'],
            'securityNextItem'       => $security['next_item'],
            'mfaMethod'        => $user->mfaToken?->method ?? '',
            'sessions'         => $sessions,
            'subscription'     => $this->subscriptions->getFullSubscriptionData($user),
            'pricing'          => config('aegis.pricing'),
            'accountPaused'    => $user->meta->where('meta_key', 'account_paused')->value('meta_value') === '1',
            'pausedUntil'      => $user->getMeta ? optional($user->meta->where('meta_key','pause_prefs')->first())?->typed_value['until'] ?? null : null,
            'activeAgreements' => $activeAgreements->values(),
            'paymentMethods'   => $this->fetchPaymentMethods($user),
            'hasCsAddon'       => (bool) $user->cs_addon,
            'availableAsCs'    => (bool) $this->profiles->getMeta($user, 'available_as_cs', false),
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

    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cs_notify'                               => 'nullable|array',
            'cs_notify.re_attestation_complete'       => 'nullable|boolean',
            'cs_notify.steward_requests_changes'      => 'nullable|boolean',
            'cs_notify.steward_updates_info'          => 'nullable|boolean',
            'cs_notify.roles_permissions_change'      => 'nullable|boolean',
            'cs_notify.documents_accessed'            => 'nullable|boolean',
            'cs_notify.steward_added_removed'         => 'nullable|boolean',
            'cs_notify.critical_incident_reported'    => 'nullable|boolean',
            'cs_notify.continuity_response'           => 'nullable|boolean',
            'ss_notify'                               => 'nullable|array',
            'ss_notify.re_attestation_complete'       => 'nullable|boolean',
            'ss_notify.steward_requests_changes'      => 'nullable|boolean',
            'ss_notify.steward_updates_info'          => 'nullable|boolean',
            'ss_notify.roles_permissions_change'      => 'nullable|boolean',
            'ss_notify.documents_accessed'            => 'nullable|boolean',
            'ss_notify.steward_added_removed'         => 'nullable|boolean',
            'ss_notify.critical_incident_reported'    => 'nullable|boolean',
            'ss_notify.continuity_response'           => 'nullable|boolean',
        ]);
        $this->profiles->saveMeta($request->user(), 'notify_cs_activity', $data['cs_notify'] ?? [], 'json');
        $this->profiles->saveMeta($request->user(), 'notify_ss_activity', $data['ss_notify'] ?? [], 'json');
        return back()->with('success', 'Notification preferences saved.');
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

    /**
     * Toggle whether this practitioner is listed as available to serve as CS for others.
     * Stored in user_meta key 'available_as_cs'.
     * Note: available_as_ss has been removed per Chapman decision #2.
     */
    public function updateCsAvailability(Request $request): RedirectResponse
    {
        $available = $request->boolean('available_as_cs');
        $this->profiles->saveMeta($request->user(), 'available_as_cs', $available ? '1' : '0', 'bool');
        return back()->with('success', 'CS availability updated.');
    }








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




    public function toggleMaat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enable'  => ['required', 'boolean'],
            'billing' => ['nullable', 'in:monthly,annual'],
        ]);
        $user = $request->user();

        if ($data['enable'] && $user->tier?->value !== 'practice') {
            return back()->withErrors(['maat' => 'MAAT requires Continuity Practice tier.']);
        }

        // Use billing period sent from Vue toggle — falls back to monthly if not provided
        $billing = $data['billing'] ?? 'monthly';

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

    public function toggleCsAddon(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enable'  => ['required', 'boolean'],
            'billing' => ['nullable', 'in:monthly,annual'],
        ]);
        $user = $request->user();

        if ($data['enable'] && $user->tier?->value !== 'practice') {
            return back()->withErrors(['cs_addon' => 'CS Add-On requires Continuity Practice tier.']);
        }

        // Use billing period sent from Vue toggle — falls back to monthly if not provided
        $billing = $data['billing'] ?? 'monthly';

        try {
            app(\App\Services\SubscriptionService::class)->toggleCsAddon($user, (bool) $data['enable'], $billing);
            $msg = $data['enable'] ? 'CS Add-On activated.' : 'CS Add-On removed.';

            $this->activity->log(
                $user->id, 'provider', 'account',
                \App\Enums\ActivitySeverity::Info,
                $data['enable'] ? 'cs_addon_added' : 'cs_addon_removed',
                $data['enable'] ? 'CS Add-On activated' : 'CS Add-On removed',
                $msg,
                \App\Models\User::class, $user->id,
                null, 'log', $user->id,
            );

            event(new CsAddonChanged($user->fresh(), $data['enable'] ? 'activated' : 'deactivated'));

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withErrors(['cs_addon' => $e->getMessage()]);
        }
    }

    // ── Payment methods ───────────────────────────────────────────────────




    public function connectOnboard(Request $request): RedirectResponse
    {
        if (!config('services.stripe.secret')) {
            return back()->withErrors(['connect' => 'Stripe is not configured. Set STRIPE_SECRET in .env.']);
        }
        try {
            $url = $this->getConnectOnboardUrl(
                $request->user(), 'provider',
                'provider.settings.connect.onboard',
                'provider.settings.connect.return'
            );
            return redirect($url);
        } catch (\Throwable $e) {
            Log::error('[Stripe Connect] onboard failed', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['connect' => 'Could not start Stripe Connect setup. ' . $e->getMessage()]);
        }
    }

    /** Return URL after Stripe Connect Express onboarding completes. */
    public function connectReturn(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->syncStripeConnectStatus($user);

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
