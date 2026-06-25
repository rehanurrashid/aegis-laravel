<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Hard gate for all /admin/* routes. Kept separate from EnsureRole so
     * route:list shows the dedicated middleware name.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->deactivated_at !== null) {
            abort(403, 'Account deactivated.');
        }

        $isAdmin = $user->roleAssignments()
            ->where('role', UserRole::Admin->value)
            ->where('is_default', 1)
            ->exists();

        if (!$isAdmin) {
            $role = $user->role instanceof UserRole
                ? $user->role
                : UserRole::tryFrom((string) $user->role);
            $isAdmin = $role === UserRole::Admin;
        }

        if (!$isAdmin) {
            abort(403, 'Administrator access required.');
        }

        return $next($request);
    }
}
