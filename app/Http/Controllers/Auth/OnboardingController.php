<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OnboardingIntentRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(private AuthService $auth) {}

    public function showIntent(): Response
    {
        return Inertia::render('auth/OnboardingIntent');
    }

    public function showRole(): Response
    {
        return Inertia::render('auth/OnboardingRole');
    }

    public function submitIntent(OnboardingIntentRequest $request): RedirectResponse
    {
        $request->session()->put('onboarding_intent', $request->validated());
        return redirect()->route('onboarding.role');
    }

    public function complete(OnboardingIntentRequest $request): RedirectResponse
    {
        $this->auth->completeOnboarding($request->user(), $request->validated());
        return redirect()->route('home');
    }
}
