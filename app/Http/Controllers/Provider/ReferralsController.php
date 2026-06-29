<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referrals\CreateReferralRequest;
use App\Models\Referral;
use App\Models\ReferralMeta;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ReferralsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $sent     = Referral::where('sender_id',    $user->id)->with('recipient')->get();
        $received = Referral::where('recipient_id', $user->id)->with('sender')->get();

        return Inertia::render('Provider/Referrals', [
            'sent'     => $sent,
            'received' => $received,
            'stats'    => [
                'sent'     => $sent->where('status', 'sent')->count() + $received->where('status', 'sent')->count(),
                'accepted' => $sent->where('status', 'accepted')->count() + $received->where('status', 'accepted')->count(),
                'closed'   => $sent->where('status', 'closed')->count() + $received->where('status', 'closed')->count(),
            ],
        ]);
    }

    /**
     * Store a referral from the shared 4-step ReferralModal.
     *
     * Core columns go on `referrals`; the rich step-1/2/3 details
     * (client_name, diagnosis, specialty, coverage, reason, urgency,
     * notes, attachments) are persisted as `referral_meta` rows so
     * we don't pollute the lean referrals table.
     */
    public function store(CreateReferralRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // Resolve recipient: prefer provider_id, else look up by slug, else fall back to off-platform name
        $recipientId = $data['provider_id'] ?? null;
        if (!$recipientId && !empty($data['provider_slug'])) {
            $recipientId = User::where('slug', $data['provider_slug'])->value('id');
        }

        $referralId = (string) Str::uuid();
        $subject    = trim($data['client_name'] . ($data['diagnosis'] ? ' — ' . $data['diagnosis'] : ''));

        DB::transaction(function () use ($data, $user, $recipientId, $referralId, $subject, $request) {
            Referral::create([
                'id'           => $referralId,
                'sender_id'    => $user->id,
                'recipient_id' => $recipientId ?: $user->id,   // self-link for off-platform — meta carries the real name
                'status'       => 'sent',
                'subject'      => mb_substr($subject, 0, 191),
            ]);

            $meta = [
                'roster_item_id'       => $data['roster_item_id'] ?? null,
                'client_name'          => $data['client_name'],
                'diagnosis'            => $data['diagnosis'] ?? null,
                'provider_slug'        => $data['provider_slug'] ?? null,
                'provider_name_manual' => $data['provider_name_manual'] ?? null,
                'specialty'            => $data['specialty'] ?? null,
                'coverage'             => $data['coverage'] ?? null,
                'reason'                => $data['reason'],
                'urgency'              => $data['urgency'] ?? 'routine',
                'notes'                => $data['notes'] ?? null,
                'off_platform'         => $recipientId ? '0' : '1',
            ];

            foreach ($meta as $k => $v) {
                if ($v === null || $v === '') continue;
                ReferralMeta::create([
                    'id'          => (string) Str::uuid(),
                    'referral_id' => $referralId,
                    'meta_key'    => $k,
                    'meta_value'  => is_scalar($v) ? (string) $v : json_encode($v),
                    'meta_type'   => 'string',
                ]);
            }

            // Persist any uploaded attachments under storage/referrals/{referralId}/
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $idx => $file) {
                    $path = $file->store('referrals/' . $referralId, 'public');
                    ReferralMeta::create([
                        'id'          => (string) Str::uuid(),
                        'referral_id' => $referralId,
                        'meta_key'    => 'attachment_' . $idx,
                        'meta_value'  => $path,
                        'meta_type'   => 'string',
                    ]);
                }
            }
        });

        return back()->with('success', 'Referral sent.');
    }

    public function accept(Request $request, Referral $referral): RedirectResponse
    {
        $this->authorize('respond', $referral);
        $referral->update(['status' => 'accepted', 'responded_at' => now()]);
        return back()->with('success', 'Referral accepted.');
    }

    public function decline(Request $request, Referral $referral): RedirectResponse
    {
        $this->authorize('respond', $referral);
        $referral->update(['status' => 'declined', 'responded_at' => now()]);
        return back()->with('success', 'Referral declined.');
    }
}
