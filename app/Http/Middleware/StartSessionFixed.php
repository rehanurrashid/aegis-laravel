<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Session\Middleware\StartSession;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces StartSession to prevent the terminate-phase save from overwriting
 * a session that was explicitly force-saved during the request.
 *
 * The controllers call $request->session()->driver()->save() immediately after
 * Auth::loginUsingId() to lock the authenticated state into the DB row.
 * They then set '_skip_terminate_save' and call driver()->save() again.
 *
 * StartSessionFixed::terminate() reads the flag from the in-memory session and
 * skips the redundant save that would otherwise overwrite the row.
 */
class StartSessionFixed extends StartSession
{
    public function terminate(Request $request, Response $response): void
    {
        try {
            if ($request->hasSession() && $request->session()->has('_skip_terminate_save')) {
                return;
            }
        } catch (\Throwable) {
            // If session is unavailable, fall through to parent
        }

        parent::terminate($request, $response);
    }
}
