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

        // Primary check: user_roles table (default role assignment)
        $hasRole = $user->roleAssignments()
            ->whereIn('role', $roles)
            ->where('is_default', 1)
            ->exists();

        // Fallback: users.role column (in case the assignment row is missing)
        if (!$hasRole) {
            $userRole = $user->role instanceof UserRole
                ? $user->role->value
                : (string) $user->role;
            $hasRole = in_array($userRole, $roles, true);
        }

        if (!$hasRole) {
            abort(403, 'You do not have access to this portal.');
        }

        return $next($request);
    }
}
