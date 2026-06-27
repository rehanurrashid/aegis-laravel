<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Models\Referral;
use App\Models\ReferralMeta;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Provider→Provider client referrals. Per UC-PRV-080..089.
 * Creates a Referral row, fans out activity to the recipient feed, and
 * stores optional client context as referral_meta rows.
 */
class ReferralService
{
    public function __construct(private ActivityService $activity) {}

    public function send(User $sender, User $recipient, array $data): Referral
    {
        if ($sender->id === $recipient->id) {
            throw new RuntimeException('Cannot send a referral to yourself.');
        }

        $referral = Referral::create([
            'id'           => 'rf_' . Str::lower(Str::random(12)),
            'sender_id'    => $sender->id,
            'recipient_id' => $recipient->id,
            'subject'      => $data['subject'] ?? null,
            'status'       => 'sent',
            'created_at'   => now(),
        ]);

        // Optional client meta
        foreach (['client_initials', 'client_age_band', 'reason', 'urgency', 'notes'] as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                ReferralMeta::create([
                    'id'          => 'rm_' . Str::lower(Str::random(12)),
                    'referral_id' => $referral->id,
                    'meta_key'    => $key,
                    'meta_value'  => (string) $data[$key],
                    'created_at'  => now(),
                ]);
            }
        }

        $this->activity->log(
            $recipient->id, 'provider', 'referral', ActivitySeverity::Info,
            'referral_received',
            "Referral from {$sender->display_name}",
            $data['subject'] ?? 'Tap to review.',
            'referral', $referral->id, $sender->id
        );

        return $referral;
    }

    public function accept(Referral $referral): Referral
    {
        $referral->update(['status' => 'accepted', 'responded_at' => now()]);

        $this->activity->log(
            $referral->sender_id, 'provider', 'referral', ActivitySeverity::Info,
            'referral_accepted',
            'Your referral was accepted',
            $referral->subject ?? 'Tap to view.',
            'referral', $referral->id, $referral->recipient_id
        );

        return $referral->fresh();
    }

    public function decline(Referral $referral, ?string $reason = null): Referral
    {
        $referral->update(['status' => 'declined', 'responded_at' => now()]);

        if ($reason !== null) {
            ReferralMeta::create([
                'id'          => 'rm_' . Str::lower(Str::random(12)),
                'referral_id' => $referral->id,
                'meta_key'    => 'decline_reason',
                'meta_value'  => $reason,
                'created_at'  => now(),
            ]);
        }

        $this->activity->log(
            $referral->sender_id, 'provider', 'referral', ActivitySeverity::Info,
            'referral_declined',
            'Your referral was declined',
            $reason ?? 'No reason given.',
            'referral', $referral->id, $referral->recipient_id
        );

        return $referral->fresh();
    }

    public function close(Referral $referral): Referral
    {
        $referral->update(['status' => 'closed', 'closed_at' => now()]);
        return $referral->fresh();
    }

    public function inbox(User $user, ?string $status = null): Collection
    {
        $q = Referral::where('recipient_id', $user->id);
        if ($status !== null) $q->where('status', $status);
        return $q->orderByDesc('created_at')->get();
    }

    public function sent(User $user, ?string $status = null): Collection
    {
        $q = Referral::where('sender_id', $user->id);
        if ($status !== null) $q->where('status', $status);
        return $q->orderByDesc('created_at')->get();
    }
}
