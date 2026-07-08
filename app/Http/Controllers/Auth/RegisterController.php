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

        // Mark email as already sent so VerifyEmailController::notice()
        // does not send a second verification email on the redirect landing.
        $request->session()->put('verify_email_sent_' . $user->id, true);

        Log::info("{$trace} login complete", [
            'auth_check'   => Auth::check(),
            'auth_id'      => Auth::id(),
            'session_id'   => $request->session()->getId(),
            'session_keys' => array_keys($request->session()->all()),
        ]);

        // Redirect to email verification notice — Vue's onSuccess then navigates here.
        // Server-side also points to verification page so non-JS clients work too.
        return Inertia::location(route('verification.notice'));
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
