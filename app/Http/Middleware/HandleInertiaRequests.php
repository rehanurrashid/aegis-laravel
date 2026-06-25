<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\IncidentStatus;
use App\Enums\StewardStatus;
use App\Enums\UserRole;
use App\Models\ActivityEvent;
use App\Models\CriticalIncident;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user         = $request->user();
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
