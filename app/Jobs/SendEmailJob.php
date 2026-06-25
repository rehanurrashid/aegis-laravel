<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\GenericMailable;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string  $template,
        public array   $data = [],
        public ?string $recipientUserId = null,
        public ?string $recipientEmail = null
    ) {
        $this->onQueue('email');
    }

    public function handle(): void
    {
        $email = $this->recipientEmail;
        $user  = null;

        if (!$email && $this->recipientUserId) {
            $user  = User::find($this->recipientUserId);
            $email = $user?->email;
        }

        if (!$email) {
            Log::warning('SendEmailJob: no recipient email resolved', [
                'template' => $this->template,
                'user_id'  => $this->recipientUserId,
            ]);
            return;
        }

        try {
            Mail::to($email)->send(new GenericMailable(
                template: $this->template,
                data: $this->data,
                userId: $this->recipientUserId
            ));
        } catch (\Throwable $e) {
            Log::error('SendEmailJob: send failed', [
                'template' => $this->template,
                'email'    => $email,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendEmailJob exhausted retries', [
            'template' => $this->template,
            'user_id'  => $this->recipientUserId,
            'error'    => $e->getMessage(),
        ]);
    }
}
