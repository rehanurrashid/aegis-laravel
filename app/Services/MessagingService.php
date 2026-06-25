<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
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
        return MessageThread::create([
            'id'                    => 'mt_' . Str::lower(Str::random(12)),
            'participant_ids'       => json_encode(array_values(array_unique($participantIds))),
            'title'                 => $title,
            'is_continuity_contact' => $incidentId ? 1 : 0,
            'incident_id'           => $incidentId,
            'last_message_at'       => now(),
        ]);
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

                $this->activity->log(
                    $recipient->id,
                    $this->portalFor($recipient->role),
                    'message',
                    ActivitySeverity::Info,
                    'message_received',
                    "New message from {$sender->display_name}",
                    Str::limit($body, 140),
                    'message_thread',
                    $thread->id,
                    $sender->id
                );
            }

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
