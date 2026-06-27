<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Events\Auth\EmailVerified;
use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class VerifyEmailController extends Controller
{
    /** GET /email/verify */
    public function notice(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->verified) {
            return redirect()->route('home');
        }

        return Inertia::render('Auth/VerifyEmail', [
            'email' => $user->email,
        ]);
    }

    /** GET /email/verify/{id}/{hash} — signed link click */
    public function verify(Request $request, string $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user || $user->id !== $id) {
            return redirect()->route('login');
        }

        if (!$request->hasValidSignature()) {
            return redirect()->route('verification.notice')
                ->withErrors(['email' => 'Verification link is invalid or has expired.']);
        }

        if (!$user->verified) {
            $user->forceFill(['verified' => 1])->save();
            event(new EmailVerified($user));
        }

        return redirect()->route('home')->with('success', 'Email verified. Welcome to Aegis.');
    }

    /** POST /email/verification-notification — resend */
    public function resend(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->verified) {
            return redirect()->route('home');
        }

        static::sendVerificationEmail($user);

        return back()->with('success', 'Verification link sent. Check your inbox.');
    }

    public static function sendVerificationEmail(User $user): void
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        SendEmailJob::dispatch(
            'emails.account.02-verify-email',
            ['verification_url' => $url],
            $user->id
        )->onQueue('email');
    }
}
