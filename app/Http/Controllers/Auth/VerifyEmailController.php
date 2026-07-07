<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
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
            return $this->postVerifyRedirect($user);
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

        return $this->postVerifyRedirect($user)
            ->with('success', 'Email verified! ' . $this->postVerifyMessage($user));
    }

    /** POST /email/verification-notification — resend */
    public function resend(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->verified) {
            return $this->postVerifyRedirect($user);
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

    // ── Post-verify routing ───────────────────────────────────────────────

    /**
     * Route the user to the correct next step after email verification.
     *
     * Paid roles (Practitioner, BP, Business CS) → plan selection
     * Free roles (Invited CS, SS) → portal dashboard directly
     */
    private function postVerifyRedirect(User $user): RedirectResponse
    {
        $role   = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        $csType = $user->cs_account_type instanceof \App\Enums\CsAccountType
            ? $user->cs_account_type->value
            : (string) ($user->cs_account_type ?? '');

        $needsPlan =
            $role === 'practitioner' ||
            $role === 'business_partner' ||
            ($role === 'continuity_steward' && $csType === 'business');

        if ($needsPlan && !$user->subscribed('default')) {
            return redirect()->route('onboarding.plan');
        }

        return redirect()->route($this->dashboardRoute($user));
    }

    private function postVerifyMessage(User $user): string
    {
        $role   = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        $csType = $user->cs_account_type instanceof \App\Enums\CsAccountType
            ? $user->cs_account_type->value
            : (string) ($user->cs_account_type ?? '');

        $needsPlan =
            $role === 'practitioner' ||
            $role === 'business_partner' ||
            ($role === 'continuity_steward' && $csType === 'business');

        return $needsPlan && !$user->subscribed('default')
            ? 'Now choose your plan to get started.'
            : 'Welcome to Aegis.';
    }

    private function dashboardRoute(User $user): string
    {
        $role = $user->role instanceof UserRole ? $user->role : UserRole::tryFrom((string) $user->role);

        return match ($role) {
            UserRole::Practitioner      => 'provider.dashboard',
            UserRole::ContinuitySteward => 'cs.dashboard',
            UserRole::SupportSteward    => 'ss.dashboard',
            UserRole::BusinessPartner   => 'bp.dashboard',
            UserRole::Admin             => 'admin.dashboard',
            default                     => 'home',
        };
    }
}
