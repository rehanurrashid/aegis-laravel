<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasCommonSettingsMethods;
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
    use HasCommonSettingsMethods;
    public function __construct(
        private ActivityService $activity,
        private ProfileService $profiles,
        private SubscriptionService $subscriptions,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user()->load('meta', 'sessions');
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

        $userArr         = $user->toArray();
        $userArr['mfa_enabled'] = (bool) $user->two_factor_enabled;
        $userArr['mfa_method']  = $user->mfaToken?->method ?? '';
        $userArr['bp_type']     = $user->bp_type instanceof \BackedEnum
            ? $user->bp_type->value
            : ($user->bp_type ?? 'agency');

        return Inertia::render('BusinessPartner/Settings', [
            'user'           => $userArr,
            'meta'           => $meta,
            'mfaEnabled'     => (bool) $user->two_factor_enabled,
            'mfaMethod'      => $user->mfaToken?->method ?? '',
            'sessions'       => $sessions,
            'subscription'   => $this->subscriptions->getFullSubscriptionData($user),
            'paymentMethods' => $this->fetchPaymentMethods($user),
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







    public function billingPortal(Request $request): RedirectResponse
    {
        try {
            $url = $this->subscriptions->billingPortalUrl(
                $request->user(),
                route('bp.settings.index') . '?section=billing'
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
                $request->user(), 'bp',
                'bp.settings.connect.onboard',
                'bp.settings.connect.return'
            );
            return redirect($url);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[Stripe Connect BP] onboard failed', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['connect' => 'Could not start Stripe Connect setup. ' . $e->getMessage()]);
        }
    }


    public function connectReturn(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->syncStripeConnectStatus($user);

        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'bp', 'settings',
            \App\Enums\ActivitySeverity::Info,
            'stripe_connect_completed', 'Stripe Connect linked',
            'Your Stripe Connect account was successfully linked.',
            null, null, null, 'log', $user->id,
        );

        return redirect()->route('bp.settings.index', ['section' => 'stripe_connect'])
            ->with('success', 'Stripe Connect setup complete. You can now receive payouts from practitioners.');
    }


    public function updateBusinessPrefs(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'visible_in_search'  => 'boolean',
            'accept_direct_hire' => 'boolean',
            'show_rates'         => 'boolean',
        ]);
        $user = $request->user();
        $this->profiles->saveMeta($user, 'bp_business_prefs', $data, 'json');
        return back()->with('success', 'Business settings saved.');
    }

    public function updatePayoutPrefs(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'payout_frequency' => 'required|string|in:weekly,biweekly,monthly',
            'minimum_payout'   => 'nullable|integer|min:0',
        ]);
        $user = $request->user();
        $this->profiles->saveMeta($user, 'bp_payout_prefs', $data, 'json');
        return back()->with('success', 'Payout preferences saved.');
    }

    public function updatePrivacy(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'level'             => 'required|string|in:public,network,private',
            'search'            => 'boolean',
            'show_contracts'    => 'boolean',
            'show_team_members' => 'boolean',
        ]);
        $user = $request->user();
        $this->profiles->saveMeta($user, 'bp_privacy', $data, 'json');
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
            \Illuminate\Support\Facades\Log::warning('[BP Settings] fetchPaymentMethods failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            return [];
        }
    }



}
