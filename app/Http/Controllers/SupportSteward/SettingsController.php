<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportSteward;

use App\Http\Controllers\Controller;
use App\Models\UserSession;
use App\Services\ActivityService;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private ActivityService $activity,
        private ProfileService $profiles) {}

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
        $userArr['mfa_enabled']              = (bool) $user->two_factor_enabled;
        $userArr['mfa_method']               = $user->mfaToken?->method ?? '';
        $userArr['linked_provider_name']     = $user->linkedProvider?->display_name;
        $userArr['linked_provider_tier_label'] = match($user->linkedProvider?->tier?->value) {
            'practice' => 'Continuity Practice',
            'access'   => 'Continuity Access',
            default    => null,
        };

        return Inertia::render('SupportSteward/Settings', [
            'user'       => $userArr,
            'meta'       => $meta,
            'mfaEnabled' => (bool) $user->two_factor_enabled,
            'mfaMethod'  => $user->mfaToken?->method ?? '',
            'sessions'   => $sessions,
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
            'notify_in_app'   => 'nullable|boolean',
            'notify_summary'  => 'nullable|boolean',
            'notify_plan'     => 'nullable|boolean',
            'notify_incident' => 'nullable|boolean',
            'notify_message'  => 'nullable|boolean',
            'notify_account'  => 'nullable|boolean',
            'categories'               => 'nullable|array',
            'categories.*.key'         => 'required|string',
            'categories.*.push'        => 'boolean',
            'categories.*.email'       => 'boolean',
            'categories.*.inapp'       => 'boolean',
        ]);

        $user = $request->user();
        foreach ($data as $key => $val) {
            if (str_starts_with($key, 'notify_') && (is_bool($val) || is_numeric($val))) {
                $this->profiles->saveMeta($user, $key, $val ? '1' : '0', 'string');
            }
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
}
