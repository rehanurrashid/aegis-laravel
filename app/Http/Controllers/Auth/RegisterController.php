<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /** GET /register */
    public function show(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /** POST /register */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $this->authService->register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($this->portalDashboardRoute($user));
    }

    private function portalDashboardRoute(User $user): string
    {
        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);

        return match ($role) {
            UserRole::Practitioner      => 'provider.dashboard',
            UserRole::ContinuitySteward => 'cs.dashboard',
            UserRole::SupportSteward    => 'ss.dashboard',
            UserRole::BusinessPartner   => 'bp.dashboard',
            UserRole::Admin             => 'admin.dashboard',
            default                     => 'login',
        };
    }
}
