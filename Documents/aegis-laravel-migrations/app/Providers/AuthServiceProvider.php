<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Laravel 11 — policies are registered in bootstrap/app.php->booted(). This
 * provider remains as a hook for any future auth-related bindings (token
 * guards, gate definitions that don\'t belong on policies, etc).
 */
class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected $policies = [
        // Registered centrally in bootstrap/app.php — leave empty here.
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
