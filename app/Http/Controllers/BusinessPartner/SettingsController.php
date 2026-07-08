<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Models\UserSession;
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
        $user = $request->user()->load('meta', 'sessions');
        $meta = $user->meta->pluck('typed_value', 'meta_key')->toArray();

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
            ]);

        $userArr         = $user->toArray();
        $userArr['mfa_enabled'] = (bool) $user->two_factor_enabled;
        $userArr['bp_type']     = $user->bp_type instanceof \BackedEnum
            ? $user->bp_type->value
            : ($user->bp_type ?? 'agency');

        return Inertia::render('BusinessPartner/Settings', [
            'user'         => $userArr,
            'meta'         => $meta,
            'mfaEnabled'   => (bool) $user->two_factor_enabled,
            'sessions'     => $sessions,
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
            'notify_payment'  => 'nullable|boolean',
            'notify_message'  => 'nullable|boolean',
            'notify_account'  => 'nullable|boolean',
            'prefs'           => 'nullable|array',
            'categories'      => 'nullable|array',
        ]);

        $user = $request->user();
        foreach ($data as $key => $val) {
            if (str_starts_with($key, 'notify_') && (is_bool($val) || is_numeric($val))) {
                $this->profiles->saveMeta($user, $key, $val ? '1' : '0', 'string');
            }
        }
        if (!empty($data['prefs'])) {
            $this->profiles->saveMeta($user, 'notify_prefs', $data['prefs'], 'json');
        }
        if (!empty($data['categories'])) {
            $this->profiles->saveMeta($user, 'notify_categories', $data['categories'], 'json');
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
