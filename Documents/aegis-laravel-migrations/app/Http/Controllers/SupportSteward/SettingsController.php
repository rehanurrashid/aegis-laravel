<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportSteward;

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
        return Inertia::render('SupportSteward/Settings', [
            'user'       => $user,
            'meta'       => UserMeta::where('user_id', $user->id)
                ->where('meta_key', 'like', 'notify_%')
                ->pluck('meta_value', 'meta_key')->toArray(),
            'mfaEnabled' => (bool) $user->mfa_enabled,
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $keys = ['notify_email', 'notify_sms', 'notify_in_app', 'notify_summary',
                 'notify_plan', 'notify_vault', 'notify_incident', 'notify_steward',
                 'notify_payment', 'notify_message', 'notify_referral', 'notify_account'];
        $data = $request->validate(array_combine($keys, array_fill(0, count($keys), 'nullable|boolean')));
        foreach ($data as $key => $val) {
            $this->profiles->setMeta($request->user()->id, $key, $val ? '1' : '0', 'bool');
        }
        return back()->with('success', 'Notification preferences saved.');
    }
}
