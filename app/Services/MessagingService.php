<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Events\Messages\MessageSent;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessagingService
{
    public function __construct(private ActivityService $activity) {}

    public function createThread(array $participantIds, ?string $title = null, ?string $incidentId = null): MessageThread
    {
        $participantIds = array_values(array_unique($participantIds));
        $thread = MessageThread::create([
            'id'                    => 'mt_' . Str::lower(Str::random(12)),
            'created_by_id'         => $participantIds[0] ?? null,
            'participant_ids'       => json_encode($participantIds),
            'title'                 => $title,
            'is_continuity_contact' => $incidentId ? 1 : 0,
            'incident_id'           => $incidentId,
            'last_message_at'       => now(),
        ]);

        $creator = User::find($participantIds[0] ?? null);
        if ($creator) {
            $this->activity->log(
                $creator->id,
                $this->portalFor($creator->role?->value ?? ''),
                'message',
                ActivitySeverity::Info,
                'thread_created',
                'New conversation started',
                $title ?? 'Direct message',
                'message_thread',
                $thread->id,
                null,
                'log',
                $creator->id
            );

            // Notify every other participant that a conversation was started with them
            foreach (array_slice($participantIds, 1) as $pid) {
                $other = User::find($pid);
                if (!$other) continue;
                $this->activity->log(
                    $other->id,
                    $this->portalFor($other->role?->value ?? ''),
                    'message',
                    ActivitySeverity::Info,
                    'thread_received',
                    "{$creator->display_name} started a conversation with you",
                    $title ?? 'Direct message',
                    'message_thread',
                    $thread->id,
                    $creator->id,
                    'notification',
                    $creator->id
                );
            }
        }

        return $thread;
    }

    public function sendMessage(MessageThread $thread, User $sender, string $body): Message
    {
        return DB::transaction(function () use ($thread, $sender, $body) {
            $msg = Message::create([
                'id'        => 'm_' . Str::lower(Str::random(12)),
                'thread_id' => $thread->id,
                'sender_id' => $sender->id,
                'body'      => $body,
                'sent_at'   => now(),
                'read_by'   => json_encode([$sender->id]),
            ]);

            $thread->update(['last_message_at' => now()]);

            $participants = json_decode($thread->participant_ids, true) ?: [];
            foreach ($participants as $pid) {
                if ($pid === $sender->id) continue;
                $recipient = User::find($pid);
                if (!$recipient) continue;

                // Notification entry for the recipient
                $this->activity->log(
                    $recipient->id,                                        // 1  userId
                    $this->portalFor($recipient->role?->value ?? ''),      // 2  portal
                    'message',                                             // 3  module
                    ActivitySeverity::Info,                                // 4  severity
                    'message_received',                                    // 5  action
                    "New message from {$sender->display_name}",            // 6  title
                    Str::limit($body, 140),                                // 7  description
                    'message_thread',                                      // 8  linkableType
                    $thread->id,                                           // 9  linkableId
                    $sender->id,                                           // 10 relatedUserId
                    'notification',                                        // 11 entryType ← correct
                    $sender->id                                            // 12 actorId
                );
            }

            // Log entry for the sender's own history
            $this->activity->log(
                $sender->id,                                           // 1  userId
                $this->portalFor($sender->role?->value ?? ''),         // 2  portal
                'message',                                             // 3  module
                ActivitySeverity::Info,                                // 4  severity
                'message_sent',                                        // 5  action
                'Message sent',                                        // 6  title
                Str::limit($body, 140),                                // 7  description
                'message_thread',                                      // 8  linkableType
                $thread->id,                                           // 9  linkableId
                null,                                                  // 10 relatedUserId
                'log',                                                 // 11 entryType ← correct
                $sender->id                                            // 12 actorId
            );

            event(new MessageSent($thread, $msg, $sender));

            return $msg;
        });
    }

    public function markRead(MessageThread $thread, User $user): void
    {
        $messages = Message::where('thread_id', $thread->id)->get();
        foreach ($messages as $m) {
            $readBy = json_decode($m->read_by ?? '[]', true) ?: [];
            if (!in_array($user->id, $readBy, true)) {
                $readBy[] = $user->id;
                $m->update(['read_by' => json_encode($readBy)]);
            }
        }
    }

    public function archiveThread(MessageThread $thread): void
    {
        $thread->update(['archived_at' => now()]);
    }

    public function getThreads(string $userId): Collection
    {
        return MessageThread::whereRaw("json_extract(participant_ids, '$') LIKE ?", ['%' . $userId . '%'])
            ->orderByDesc('last_message_at')
            ->get()
            ->filter(function ($t) use ($userId) {
                $ids = json_decode($t->participant_ids, true) ?: [];
                return in_array($userId, $ids, true);
            })
            ->values();
    }

    public function getMessages(MessageThread $thread, int $limit = 100): Collection
    {
        return Message::where('thread_id', $thread->id)
            ->orderBy('sent_at')
            ->limit($limit)
            ->get();
    }

    private function portalFor(string $role): string
    {
        return match ($role) {
            'practitioner'       => 'provider',
            'continuity_steward' => 'continuity_steward',
            'support_steward'    => 'support_steward',
            'business_partner'   => 'business_partner',
            default              => 'provider',
        };
    }
}
