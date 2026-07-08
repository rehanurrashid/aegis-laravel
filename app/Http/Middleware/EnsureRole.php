<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Gate any route to one or more roles.
     * Usage: role:practitioner  or  role:practitioner,admin
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Resolve the user's primary role as a plain string for comparison
        $userRoleValue = $user->role instanceof UserRole
            ? $user->role->value
            : (string) $user->role;

        // Check user_roles table (default assignment) — compare as raw strings
        // to avoid enum serialization issues in whereIn
        $hasRole = $user->roleAssignments()
            ->where('is_default', 1)
            ->whereRaw('LOWER(role) IN (' . implode(',', array_fill(0, count($roles), '?')) . ')', $roles)
            ->exists();

        // Fallback: users.role column
        if (!$hasRole) {
            $hasRole = in_array($userRoleValue, $roles, true);
        }

        if (!$hasRole) {
            $role = $user->role instanceof UserRole
                ? $user->role
                : UserRole::tryFrom($userRoleValue);

            $dashboardRoute = match ($role) {
                UserRole::Practitioner       => 'provider.dashboard',
                UserRole::ContinuitySteward  => 'cs.dashboard',
                UserRole::SupportSteward     => 'ss.dashboard',
                UserRole::BusinessPartner    => 'bp.dashboard',
                UserRole::Admin              => 'admin.dashboard',
                default                      => 'home',
            };

            return redirect()->route($dashboardRoute)
                ->with('error', 'You are not authorized to access that portal.');
        }

        return $next($request);
    }
}
