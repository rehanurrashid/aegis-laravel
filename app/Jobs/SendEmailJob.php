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
use Illuminate\Support\Facades\URL;

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
            // Inject a live, signed one-click unsubscribe link when we have a user.
            // Transactional/auth templates set $ungated=true and hide it in the footer,
            // so this is safe to attach unconditionally. Caller-supplied URLs win.
            $data = $this->data;
            if ($this->recipientUserId && empty($data['unsubscribe_url'])) {
                try {
                    $data['unsubscribe_url'] = URL::signedRoute('email.unsubscribe', [
                        'user_id' => $this->recipientUserId,
                        'gate'    => $this->gateForTemplate($this->template),
                    ]);
                } catch (\Throwable $routeEx) {
                    Log::warning('SendEmailJob: email.unsubscribe route not defined — skipping unsubscribe link', [
                        'template' => $this->template,
                        'user_id'  => $this->recipientUserId,
                    ]);
                    $data['unsubscribe_url'] = null;
                }
            }

            Mail::to($email)->send(new GenericMailable(
                template: $this->template,
                data: $data,
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

    /**
     * Map an email template to the notify_* gate its unsubscribe link should flip.
     * Only returns keys actually seeded in AuthService::register(); anything
     * unmapped falls back to the master 'notify_email' gate.
     */
    private function gateForTemplate(string $template): string
    {
        $segments = explode('.', $template);
        $domain   = $segments[1] ?? '';

        return match ($domain) {
            'incident'           => 'notify_incident',
            'plan', 'document'   => 'notify_plan_change',
            'vault'              => 'notify_attestation',
            'steward'            => 'notify_assignment',
            'bp', 'business'     => 'notify_payment',
            'support'            => 'notify_message',
            'digest', 'summary'  => 'notify_summary',
            default              => 'notify_email',
        };
    }
}
