<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\AdminAuditLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ImpersonateForDemo
{
    /**
     * Dev/local-only impersonation. Recreates the PHP prototype's ?as=p_sarah
     * demo affordance. Silently ignored anywhere outside local/development.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!App::environment(['local', 'development'])) {
            return $next($request);
        }

        $asUserId = $request->query('as');

        if (!$asUserId) {
            return $next($request);
        }

        /** @var User|null $target */
        $target = User::find($asUserId);

        if (!$target || $target->deactivated_at !== null) {
            return $next($request);
        }

        // If an admin is impersonating, record it (best-effort)
        $actor = Auth::user();
        if ($actor) {
            $actorRole = $actor->role instanceof UserRole
                ? $actor->role
                : UserRole::tryFrom((string) $actor->role);

            if ($actorRole === UserRole::Admin && $actor->id !== $target->id) {
                try {
                    AdminAuditLog::create([
                        'id'             => 'aal_' . Str::lower(Str::random(12)),
                        'admin_id'       => $actor->id,
                        'action'         => 'impersonate',
                        'target_user_id' => $target->id,
                        'target_type'    => 'user',
                        'target_id'      => $target->id,
                        'meta_json'      => json_encode(['env' => App::environment()]),
                        'created_at'     => now(),
                    ]);
                } catch (\Throwable $e) {
                    // Audit failure must never block the impersonation
                }
            }
        }

        Auth::login($target);
        $request->session()->regenerate();

        return $next($request);
    }
}
