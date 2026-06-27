<?php

declare(strict_types=1);

use App\Http\Middleware\CheckAccountLocked;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsureIncidentActive;
use App\Http\Middleware\EnsurePlanActive;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureServicesMode;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ImpersonateForDemo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ── Middleware aliases ────────────────────────────────────────────────
        $middleware->alias([
            'role'            => EnsureRole::class,
            'admin'           => EnsureAdminRole::class,
            'plan.active'     => EnsurePlanActive::class,
            'incident.active' => EnsureIncidentActive::class,
            'services.mode'   => EnsureServicesMode::class,
            'check.locked'    => CheckAccountLocked::class,
            'demo'            => ImpersonateForDemo::class,
        ]);

        // ── Web group appends ─────────────────────────────────────────────────
        $middleware->web(append: [
            HandleInertiaRequests::class,
            ImpersonateForDemo::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
    ])
    ->booted(function () {
        // ── Policy registrations ──────────────────────────────────────────────
        $policies = [
            \App\Models\ContinuityPlan::class          => \App\Policies\ContinuityPlanPolicy::class,
            \App\Models\VaultItem::class               => \App\Policies\VaultPolicy::class,
            \App\Models\CriticalIncident::class        => \App\Policies\IncidentPolicy::class,
            \App\Models\IncidentTask::class            => \App\Policies\PlanTaskPolicy::class,
            \App\Models\PlanTask::class                => \App\Policies\PlanTaskPolicy::class,
            \App\Models\ContinuityDocument::class      => \App\Policies\ContinuityDocumentPolicy::class,
            \App\Models\Referral::class                => \App\Policies\ReferralPolicy::class,
            \App\Models\MessageThread::class           => \App\Policies\MessagePolicy::class,
            \App\Models\Service::class                 => \App\Policies\ServicePolicy::class,
            \App\Models\BpJob::class                   => \App\Policies\BpJobPolicy::class,
            \App\Models\BpContract::class              => \App\Policies\BpContractPolicy::class,
            \App\Models\BpInvoice::class               => \App\Policies\BpInvoicePolicy::class,
            \App\Models\Complaint::class               => \App\Policies\ComplaintPolicy::class,
            \App\Models\User::class                    => \App\Policies\AdminPolicy::class,
            \App\Models\ProfileEditAuthorization::class => \App\Policies\ProfileEditAuthorizationPolicy::class,
            \App\Models\NewsPost::class                => \App\Policies\NewsPolicy::class,
            \App\Models\NetworkConnection::class       => \App\Policies\NetworkConnectionPolicy::class,
            \App\Models\HelpArticle::class             => \App\Policies\HelpArticlePolicy::class,
            \App\Models\PackageOverride::class         => \App\Policies\PackagePolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            \Illuminate\Support\Facades\Gate::policy($model, $policy);
        }
    })
    ->create();
