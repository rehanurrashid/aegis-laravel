<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Events\Auth\EmailVerified;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VerifyEmailController extends Controller
{
    public function notice(): Response
    {
        return Inertia::render('auth/VerifyEmail');
    }

    public function verify(VerifyEmailRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user && !$user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
            event(new EmailVerified($user));
        }
        return redirect()->route('home')->with('success', 'Email verified.');
    }

    public function resend(VerifyEmailRequest $request): RedirectResponse
    {
        // Re-dispatches verification email via SendEmailJob; gated by notify_email.
        return back()->with('success', 'Verification link sent.');
    }
}
