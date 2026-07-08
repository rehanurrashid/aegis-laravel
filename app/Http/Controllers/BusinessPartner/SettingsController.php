<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

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
            'categories'                   => 'nullable|array',
            'categories.*.key'             => 'required|string|max:64',
            'categories.*.push'            => 'nullable|boolean',
            'categories.*.email'           => 'nullable|boolean',
            'categories.*.inapp'           => 'nullable|boolean',
            // Security & Login Alerts
            'security.alertOnNewLogin'     => 'nullable|boolean',
            'security.sessionTimeout'      => 'nullable|boolean',
            // Business Partner specific
            'business.proposalActivity'    => 'nullable|boolean',
            'business.contractMilestones'  => 'nullable|boolean',
            'business.paymentsInvoices'    => 'nullable|boolean',
            'business.contractExpiry'      => 'nullable|boolean',
            'business.profileViews'        => 'nullable|boolean',
            // Billing
            'billing.invoiceEmails'        => 'nullable|boolean',
            // Network & Updates
            'network.connectionAlerts'     => 'nullable|boolean',
            'network.weeklyDigest'         => 'nullable|boolean',
            'network.featureUpdates'       => 'nullable|boolean',
        ]);

        $user = $request->user();

        if (!empty($data['categories'])) {
            $this->profiles->saveMeta($user, 'notify_categories', $data['categories'], 'json');
        }

        $groups = ['security', 'business', 'billing', 'network'];
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
        $data = $request->validate(['price_id' => ['required', 'string', 'starts_with:price_']]);

        try {
            $result = $this->subscriptions->changePlan($request->user(), $data['price_id']);
            $msg = match ($result['direction']) {
                'upgrade'   => 'Plan upgraded successfully.',
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
                route('bp.settings.index') . '?section=billing'
            );
            return redirect()->away($url);
        } catch (\Throwable $e) {
            return back()->withErrors(['stripe' => 'Could not open billing portal. Please try again.']);
        }
    }
}
