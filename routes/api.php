<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| API routes (minimal). The Aegis surface area is web/Inertia. This file is
| retained for Sanctum-authenticated profile reads and any future programmatic
| integrations. Group is rate-limited via RouteServiceProvider.
*/

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('api.user');
});
