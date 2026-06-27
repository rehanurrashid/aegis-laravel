<?php

declare(strict_types=1);

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Handles one-click email unsubscribe links carried in the email footer.
 *
 * The link is a signed URL (URL::signedRoute) so the user_id + gate cannot be
 * tampered with and no login is required — recipients click straight from their
 * inbox. The handler flips the relevant notify_* UserMeta gate to '0', which
 * NotificationService::shouldSend() then honours on all future sends.
 *
 * The master gate 'notify_email' switches off every category at once.
 */
class UnsubscribeController extends Controller
{
    /**
     * Whitelisted gate keys. Restricting writes to these prevents a signed link
     * from being repurposed to mutate arbitrary user_meta rows. These mirror the
     * notify_* keys seeded in AuthService::register().
     */
    private const GATES = [
        'notify_email'       => 'all email notifications',
        'notify_incident'    => 'critical incident alerts',
        'notify_message'     => 'message notifications',
        'notify_task'        => 'task notifications',
        'notify_assignment'  => 'steward assignment notifications',
        'notify_attestation' => 'attestation notifications',
        'notify_plan_change' => 'continuity plan updates',
        'notify_plan_review' => 'plan review reminders',
        'notify_role_change' => 'role change notifications',
        'notify_payment'     => 'billing and payout notifications',
        'notify_proposal'    => 'proposal notifications',
        'notify_agreement'   => 'agreement notifications',
        'notify_summary'     => 'digest summaries',
    ];

    public function __invoke(Request $request)
    {
        $userId = (string) $request->query('user_id', '');
        $gate   = (string) $request->query('gate', 'notify_email');

        if (! array_key_exists($gate, self::GATES)) {
            $gate = 'notify_email';
        }

        $user = $userId !== '' ? User::find($userId) : null;

        if ($user) {
            UserMeta::updateOrCreate(
                ['user_id' => $user->id, 'meta_key' => $gate],
                [
                    'id'         => 'um_' . Str::lower(Str::random(12)),
                    'meta_value' => '0',
                    'meta_type'  => 'boolean',
                ]
            );
        }

        return response()->view('emails.unsubscribed', [
            'category'    => self::GATES[$gate],
            'gate'        => $gate,
            'was_master'  => $gate === 'notify_email',
            'resolved'    => (bool) $user,
            'settings_url' => rtrim(config('app.url'), '/') . '/settings',
        ]);
    }
}
