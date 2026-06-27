<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;

class MessagePolicy
{
    /** Anyone who has ever sent or received a message in the thread, or created it */
    public function send(User $user, MessageThread $thread): bool
    {
        return $this->isParticipant($user, $thread);
    }

    public function read(User $user, MessageThread $thread): bool
    {
        return $this->isParticipant($user, $thread);
    }

    private function isParticipant(User $user, MessageThread $thread): bool
    {
        if ($thread->created_by_id === $user->id) {
            return true;
        }

        return Message::where('thread_id', $thread->id)
            ->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                  ->orWhere('recipient_id', $user->id);
            })
            ->exists();
    }
}
