<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Events\Auth\PasswordReset as PasswordResetEvent;
use App\Http\Controllers\Controller;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    public function __construct(private ActivityService $activity) {}
    /** GET /forgot-password */
    public function showForgot(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /** POST /forgot-password */
    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        // Generic response regardless of result — prevents user enumeration
        if ($user) {
            PasswordResetToken::where('user_id', $user->id)->delete();

            $rawToken = bin2hex(random_bytes(32));

            PasswordResetToken::create([
                'id'         => 'prt_' . Str::lower(Str::random(12)),
                'user_id'    => $user->id,
                'token'      => hash('sha256', $rawToken),
                'expires_at' => now()->addHours(2),
                'created_at' => now(),
            ]);

            \App\Jobs\SendEmailJob::dispatch(
                'emails.account.05-password-reset',
                ['token' => $rawToken, 'email' => $user->email],
                $user->id
            )->onQueue('email');
        }

        return back()->with('status', 'If that email exists in our system, a reset link has been sent.');
    }

    /** GET /reset-password/{token} */
    public function showReset(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /** POST /reset-password */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        $hashedToken = hash('sha256', $request->token);
        $record = PasswordResetToken::where('token', $hashedToken)
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return back()->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        $user->forceFill([
            'password'           => Hash::make($request->password),
            'failed_login_count' => 0,
            'locked_at'          => null,
            'locked_reason'      => null,
        ])->save();

        $record->update(['used_at' => now()]);

        event(new PasswordResetEvent($user));

        return redirect()->route('login')->with('status', 'Your password has been reset. Please log in.');
    }

    /** PUT /settings/password (authenticated) */
    public function change(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (!Hash::check($request->current_password, (string) $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->forceFill(['password' => Hash::make($request->password)])->save();

        event(new PasswordResetEvent($user));

        $this->activity->log(
            $user->id,
            $user->role?->portal() ?? 'provider',
            'account',
            \App\Enums\ActivitySeverity::Warning,
            'password_changed',
            'Password changed',
            'You successfully changed your account password.',
            null, null, null,
            'log',
            $user->id,
        );

        app(\App\Services\SecurityCompletionService::class)->recompute($user);

        return back()->with('success', 'Password updated successfully.');
    }
}
