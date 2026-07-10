<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasCommonSettingsMethods;
use App\Models\UserSession;
use App\Services\ActivityService;
use App\Services\ProfileService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use App\Events\Business\SubscriptionTierChanged;

use Illuminate\Http\Request;
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
            'user'           => $userArr,
            'meta'           => $meta,
            'mfaEnabled'     => (bool) $user->two_factor_enabled,
            'mfaMethod'      => $user->mfaToken?->method ?? '',
            'sessions'       => $sessions,
            'subscription'   => $subscription,
            'paymentMethods' => $csAccountType === 'business' ? $this->fetchPaymentMethods($user) : [],
            'pricing'        => config('aegis.pricing'),
        ]);
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
            $actionMap = [
                'upgrade'        => ['subscription_upgraded',   'Plan upgraded',   'Subscription plan updated.'],
                'downgrade'      => ['subscription_downgraded', 'Plan downgraded', 'Plan change scheduled for next cycle.'],
                'switch-annual'  => ['subscription_changed',    'Billing changed', 'Switched to annual billing.'],
                'switch-monthly' => ['subscription_changed',    'Billing changed', 'Switched to monthly billing.'],
            ];
            [$action, $title, $desc] = $actionMap[$result['direction']] ?? ['subscription_changed', 'Plan changed', $msg];
            $this->activity->log(
                $user->id, 'cs', 'settings',
                \App\Enums\ActivitySeverity::Info,
                $action, $title, $desc,
                \App\Models\User::class, $user->id,
                null, 'log', $user->id,
            );
            if (in_array($result['direction'], ['upgrade', 'downgrade'], true)) {
                event(new SubscriptionTierChanged($user, $result['direction'], 'cs_business'));
            }
            return back()->with('success', $msg);
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




    public function connectOnboard(Request $request): RedirectResponse
    {
        if (!config('services.stripe.secret')) {
            return back()->withErrors(['connect' => 'Stripe is not configured.']);
        }
        try {
            $url = $this->getConnectOnboardUrl(
                $request->user(), 'cs',
                'cs.settings.connect.onboard',
                'cs.settings.connect.return'
            );
            return redirect($url);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[Stripe Connect CS] onboard failed', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['connect' => 'Could not start Stripe Connect setup. ' . $e->getMessage()]);
        }
    }


    public function connectReturn(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->syncStripeConnectStatus($user);

        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'cs', 'settings',
            \App\Enums\ActivitySeverity::Info,
            'stripe_connect_completed', 'Stripe Connect linked',
            'Your Stripe Connect account was successfully linked.',
            null, null, null, 'log', $user->id,
        );

        return redirect()->route('cs.settings.index', ['section' => 'stripe_connect'])
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

    private function fetchPaymentMethods(\App\Models\User $user): array
    {
        if (!$user->hasStripeId()) return [];
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
            \Illuminate\Support\Facades\Log::warning('[CS Settings] fetchPaymentMethods failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            return [];
        }
    }



}
