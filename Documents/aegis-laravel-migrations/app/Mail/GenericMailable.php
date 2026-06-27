<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic Aegis Mailable. Renders the given Blade template (e.g. 'emails.plan.10-plan-signed')
 * with the supplied data array. Used by SendEmailJob so we don't need a Mailable class per template.
 *
 * Subject line is derived from the template name (last path segment, hyphens → spaces, title cased)
 * unless overridden via $data['subject'].
 */
class GenericMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string  $template,
        public array   $data = [],
        public ?string $userId = null
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->data['subject'] ?? $this->deriveSubject($this->template);

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        // Inject userId into view data so templates can reference recipient if needed.
        $payload = array_merge($this->data, ['user_id' => $this->userId ?? ($this->data['user_id'] ?? null)]);

        return new Content(
            view: $this->template,
            with: $payload,
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * Derive a human subject from a template path.
     * "emails.plan.10-plan-signed" → "Plan Signed"
     */
    private function deriveSubject(string $template): string
    {
        $segments = explode('.', $template);
        $last     = end($segments);
        // Strip a leading "NN-" or "NNa-" prefix
        $clean    = preg_replace('/^\d+[a-z]?-/i', '', $last);
        $words    = str_replace(['-', '_'], ' ', $clean ?? '');
        return 'Aegis · ' . ucwords(trim($words));
    }
}
