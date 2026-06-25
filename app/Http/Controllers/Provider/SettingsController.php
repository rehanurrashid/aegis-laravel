<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\UserMeta;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private ProfileService $profiles) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $meta = UserMeta::where('user_id', $user->id)
            ->where('meta_key', 'like', 'notify_%')
            ->pluck('meta_value', 'meta_key')
            ->toArray();

        return Inertia::render('Provider/Settings', [
            'user'       => $user,
            'meta'       => $meta,
            'mfaEnabled' => (bool) $user->mfa_enabled,
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notify_email'    => 'nullable|boolean',
            'notify_sms'      => 'nullable|boolean',
            'notify_in_app'   => 'nullable|boolean',
            'notify_summary'  => 'nullable|boolean',
            'notify_plan'     => 'nullable|boolean',
            'notify_vault'    => 'nullable|boolean',
            'notify_incident' => 'nullable|boolean',
            'notify_steward'  => 'nullable|boolean',
            'notify_payment'  => 'nullable|boolean',
            'notify_message'  => 'nullable|boolean',
            'notify_referral' => 'nullable|boolean',
            'notify_account'  => 'nullable|boolean',
        ]);

        foreach ($data as $key => $val) {
            $this->profiles->setMeta($request->user()->id, $key, $val ? '1' : '0', 'bool');
        }
        return back()->with('success', 'Notification preferences saved.');
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();
        if (!\Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }
        $user->update(['deactivated_at' => now()]);
        $user->tokens()->delete();
        auth()->logout();
        return redirect('/login')->with('success', 'Account closed.');
    }
}
