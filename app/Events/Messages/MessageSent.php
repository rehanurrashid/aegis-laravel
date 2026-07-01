<?php

declare(strict_types=1);

namespace App\Events\Messages;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MessageThread $thread,
        public Message       $message,
        public User          $sender,
    ) {}
}
