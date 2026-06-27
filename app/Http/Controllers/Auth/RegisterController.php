<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RegisterController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function show(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): SymfonyResponse
    {
        $trace = '[REGISTER:' . substr(md5((string) microtime(true)), 0, 6) . ']';

        $user = $this->authService->register($request->validated());

        Auth::login($user);

        // Send verification email asynchronously
        VerifyEmailController::sendVerificationEmail($user);

        Log::info("{$trace} login complete", [
            'auth_check'   => Auth::check(),
            'auth_id'      => Auth::id(),
            'session_id'   => $request->session()->getId(),
            'session_keys' => array_keys($request->session()->all()),
        ]);

        return Inertia::location($this->portalDashboardUrl($user));
    }

    private function portalDashboardUrl(User $user): string
    {
        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);

        return match ($role) {
            UserRole::Practitioner      => route('provider.dashboard'),
            UserRole::ContinuitySteward => route('cs.dashboard'),
            UserRole::SupportSteward    => route('ss.dashboard'),
            UserRole::BusinessPartner   => route('bp.dashboard'),
            UserRole::Admin             => route('admin.dashboard'),
            default                     => url('/'),
        };
    }
}
