<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Events\Referral\ReferralAccepted;
use App\Events\Referral\ReferralCancelled;
use App\Events\Referral\ReferralClosed;
use App\Events\Referral\ReferralDeclined;
use App\Events\Referral\ReferralSent;
use App\Models\Referral;
use App\Models\ReferralMeta;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Provider→Provider client referrals. Per UC-PRV-080..089, UC-PRV-108..125.
 *
 * Every write action:
 *  1. Persists the DB change
 *  2. Logs to ActivityService for all affected parties (self log + counterpart notification)
 *  3. Fires a domain event → SendEmailNotificationListener dispatches gated emails
 */
class ReferralService
{
    public function __construct(private ActivityService $activity) {}

    // ── UC-PRV-108, UC-PRV-121 ──────────────────────────────────────────────
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

        // Activity — sender self-log (entry_type: log)
        $this->activity->log(
            $sender->id, 'provider', 'referral', ActivitySeverity::Info,
            'referral_sent',
            "Referral sent to {$recipient->display_name}",
            $data['subject'] ?? 'Awaiting response.',
            'referral', $referral->id, $recipient->id
        );

        // Activity — recipient notification (entry_type: notification)
        $this->activity->log(
            $recipient->id, 'provider', 'referral', ActivitySeverity::Info,
            'referral_received',
            "Referral from {$sender->display_name}",
            $data['subject'] ?? 'Tap to review.',
            'referral', $referral->id, $sender->id
        );

        // Event → email
        ReferralSent::dispatch($referral, $sender, $recipient);

        return $referral;
    }

    // ── UC-PRV-111, UC-PRV-122 ──────────────────────────────────────────────
    public function accept(Referral $referral): Referral
    {
        $referral->update(['status' => 'accepted', 'responded_at' => now()]);
        $referral->refresh();

        $recipient = User::find($referral->recipient_id);
        $sender    = User::find($referral->sender_id);

        // Activity — recipient self-log
        $this->activity->log(
            $referral->recipient_id, 'provider', 'referral', ActivitySeverity::Info,
            'referral_accepted_self',
            "You accepted a referral from {$sender?->display_name}",
            $referral->subject ?? 'Referral accepted.',
            'referral', $referral->id, $referral->sender_id
        );

        // Activity — sender notification
        $this->activity->log(
            $referral->sender_id, 'provider', 'referral', ActivitySeverity::Info,
            'referral_accepted',
            "Your referral was accepted by {$recipient?->display_name}",
            $referral->subject ?? 'Tap to view.',
            'referral', $referral->id, $referral->recipient_id
        );

        // Event → email
        ReferralAccepted::dispatch($referral);

        return $referral->fresh();
    }

    // ── UC-PRV-111, UC-PRV-123 ──────────────────────────────────────────────
    public function decline(Referral $referral, ?string $reason = null): Referral
    {
        $referral->update(['status' => 'declined', 'responded_at' => now()]);
        $referral->refresh();

        if ($reason !== null) {
            ReferralMeta::updateOrCreate(
                ['referral_id' => $referral->id, 'meta_key' => 'decline_reason'],
                [
                    'id'         => 'rm_' . Str::lower(Str::random(12)),
                    'meta_value' => $reason,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $recipient = User::find($referral->recipient_id);
        $sender    = User::find($referral->sender_id);

        // Activity — recipient self-log
        $this->activity->log(
            $referral->recipient_id, 'provider', 'referral', ActivitySeverity::Info,
            'referral_declined_self',
            "You declined a referral from {$sender?->display_name}",
            $reason ?? 'No reason given.',
            'referral', $referral->id, $referral->sender_id
        );

        // Activity — sender notification
        $this->activity->log(
            $referral->sender_id, 'provider', 'referral', ActivitySeverity::Info,
            'referral_declined',
            "Your referral was declined by {$recipient?->display_name}",
            $reason ?? 'No reason given.',
            'referral', $referral->id, $referral->recipient_id
        );

        // Event → email
        ReferralDeclined::dispatch($referral, $reason);

        return $referral->fresh();
    }

    // ── UC-PRV-124 (mark complete by either party) ──────────────────────────
    public function close(Referral $referral, ?User $actor = null): Referral
    {
        $referral->update(['status' => 'closed', 'closed_at' => now()]);
        $referral->refresh();

        $actor      = $actor ?? User::find($referral->sender_id);
        $otherPartyId = $actor?->id === $referral->sender_id
            ? $referral->recipient_id
            : $referral->sender_id;
        $otherParty = User::find($otherPartyId);

        // Activity — actor self-log
        $this->activity->log(
            $actor->id, 'provider', 'referral', ActivitySeverity::Info,
            'referral_closed_self',
            "You marked a referral as complete",
            $referral->subject ?? 'Referral completed.',
            'referral', $referral->id, $otherPartyId
        );

        // Activity — other party notification
        if ($otherParty) {
            $this->activity->log(
                $otherParty->id, 'provider', 'referral', ActivitySeverity::Info,
                'referral_closed',
                "Referral marked complete by {$actor->display_name}",
                $referral->subject ?? 'Referral completed.',
                'referral', $referral->id, $actor->id
            );
        }

        // Event → email
        ReferralClosed::dispatch($referral, $actor);

        return $referral->fresh();
    }

    // ── UC-PRV-110 (sender cancels before response) ─────────────────────────
    public function cancel(Referral $referral, ?User $actor = null): Referral
    {
        $referral->update(['status' => 'cancelled', 'closed_at' => now()]);
        $referral->refresh();

        $actor     = $actor ?? User::find($referral->sender_id);
        $recipient = User::find($referral->recipient_id);

        // Activity — sender self-log
        $this->activity->log(
            $referral->sender_id, 'provider', 'referral', ActivitySeverity::Info,
            'referral_cancelled_self',
            "You cancelled a referral to {$recipient?->display_name}",
            $referral->subject ?? 'Referral cancelled.',
            'referral', $referral->id, $referral->recipient_id
        );

        // Activity — recipient notification
        if ($recipient) {
            $this->activity->log(
                $recipient->id, 'provider', 'referral', ActivitySeverity::Info,
                'referral_cancelled',
                "A referral from {$actor?->display_name} was cancelled",
                $referral->subject ?? 'No further action needed.',
                'referral', $referral->id, $referral->sender_id
            );
        }

        // Event → email
        ReferralCancelled::dispatch($referral, $actor);

        return $referral->fresh();
    }

    // ── Read helpers ─────────────────────────────────────────────────────────
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
