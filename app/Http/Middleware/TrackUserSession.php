<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\UserSession;
use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    public function __construct(private AuthService $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $this->upsertSession($request);
        }

        return $next($request);
    }

    private function upsertSession(Request $request): void
    {
        try {
            $user      = $request->user();
            $ua        = (string) $request->userAgent();
            $ip        = (string) $request->ip();
            $sessionId = $request->session()->getId();

            UserSession::updateOrCreate(
                ['session_token' => $sessionId],
                [
                    'id'           => 'us_' . Str::lower(Str::random(12)),
                    'user_id'      => $user->id,
                    'ip_address'   => $ip,
                    'user_agent'   => $ua,
                    'device_label' => $this->parseDeviceLabel($ua),
                    'last_seen_at' => now(),
                    'revoked_at'   => null,
                    'created_at'   => now(),
                ]
            );
        } catch (\Throwable) {
            // Never break the request if session tracking fails
        }
    }

    private function parseDeviceLabel(string $ua): string
    {
        // OS
        $os = match (true) {
            str_contains($ua, 'iPhone')          => 'iPhone',
            str_contains($ua, 'iPad')            => 'iPad',
            str_contains($ua, 'Android')         => str_contains($ua, 'Mobile') ? 'Android Phone' : 'Android Tablet',
            str_contains($ua, 'Macintosh')       => 'Mac',
            str_contains($ua, 'Windows NT 10')   => 'Windows 11',
            str_contains($ua, 'Windows NT 6')    => 'Windows 10',
            str_contains($ua, 'X11')             => 'Linux',
            default                              => 'Device',
        };

        // Browser
        $browser = match (true) {
            str_contains($ua, 'Edg/')    => 'Edge ' . $this->version($ua, 'Edg/'),
            str_contains($ua, 'Chrome/') => 'Chrome ' . $this->version($ua, 'Chrome/'),
            str_contains($ua, 'Firefox/')=> 'Firefox ' . $this->version($ua, 'Firefox/'),
            str_contains($ua, 'Safari/') => 'Safari',
            default                      => 'Browser',
        };

        return "{$os} — {$browser}";
    }

    private function version(string $ua, string $key): string
    {
        $pos = strpos($ua, $key);
        if ($pos === false) return '';
        $ver = substr($ua, $pos + strlen($key), 6);
        return explode('.', $ver)[0];
    }
}
