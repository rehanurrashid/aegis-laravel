<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\IncidentStatus;
use App\Enums\StewardStatus;
use App\Enums\UserRole;
use App\Models\ActivityEvent;
use App\Models\CriticalIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        // Return null to disable Inertia's asset-version check.
        // The default parent::version() hashes the Vite manifest; on staging
        // this triggers 409 Conflict responses after every deploy/rebuild,
        // causing Inertia::location() navigations to land on stale pages.
        // Re-enable once a proper asset versioning strategy is in place.
        return null;
    }

    public function share(Request $request): array
    {
        $user         = $request->user();

        Log::info('[INERTIA_SHARE]', [
            'url'          => $request->fullUrl(),
            'route'        => $request->route()?->getName(),
            'method'       => $request->method(),
            'session_id'   => $request->session()->getId(),
            'auth_check'   => auth()->check(),
            'auth_id'      => auth()->id(),
            'user_class'   => $user ? get_class($user) : null,
            'user_id'      => $user?->id,
            'user_role'    => $user?->role instanceof \App\Enums\UserRole ? $user->role->value : ($user?->role ?? null),
            'session_keys' => array_keys($request->session()->all()),
            'cookie_session' => $request->cookies->has(config('session.cookie')) ? 'present' : 'missing',
        ]);

        $hasEmergency = false;
        $unreadCount  = 0;
        $roleSlugs    = [];

        if ($user) {
            $hasEmergency = CriticalIncident::where('status', IncidentStatus::Active->value)
                ->where(function ($q) use ($user) {
                    $q->where('practitioner_id', $user->id)
                      ->orWhereHas('plan.stewards', function ($s) use ($user) {
                          $s->where('steward_id', $user->id)
                            ->where('status', StewardStatus::Active->value);
                      });
                })
                ->exists();

            $unreadCount = ActivityEvent::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();

            $roleSlugs = $user->roleAssignments()
                ->pluck('role')
                ->map(fn($r) => $r instanceof UserRole ? $r->value : (string) $r)
                ->all();
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'              => $user->id,
                    'display_name'    => $user->display_name,
                    'email'           => $user->email,
                    'role'            => $user->role instanceof UserRole
                                            ? $user->role->value
                                            : (string) $user->role,
                    'tier'            => $user->tier instanceof \BackedEnum
                                            ? $user->tier->value
                                            : ($user->tier ?? null),
                    'slug'            => $user->slug ?? null,
                    'avatar_initials' => $user->avatar_initials ?? null,
                    'two_factor'      => (bool) ($user->two_factor_enabled ?? false),
                ] : null,
                'portal' => $user
                    ? ($user->role instanceof UserRole
                        ? $user->role->portal()
                        : (UserRole::tryFrom((string) $user->role)?->portal() ?? null))
                    : null,
                'tier'   => $user?->tier instanceof \BackedEnum
                                ? $user->tier->value
                                : ($user?->tier ?? null),
                'roles'  => $roleSlugs,
            ],
            'hasEmergency' => $hasEmergency,
            'unreadCount'  => $unreadCount,
            'activePage'   => $request->route()?->getName(),
            'flash'        => [
                'success' => session('success'),
                'error'   => session('error'),
                'info'    => session('info'),
                'warning' => session('warning'),
                'status'  => session('status'),
            ],
        ]);
    }
}
