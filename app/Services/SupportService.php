<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Support\FeedbackReceived;
use App\Events\Support\TicketResolved;

use App\Enums\ActivitySeverity;
use App\Events\Support\TicketCreated;
use App\Events\Support\TicketReplied;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\HelpArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SupportService
{
    public function __construct(private ActivityService $activity) {}

    public function createTicket(User $submitter, array $data): Complaint
    {
        $ticket = Complaint::create([
            'id'                 => 'cpt_' . Str::lower(Str::random(12)),
            'submitter_id'       => $submitter->id,
            'subject'            => $data['subject'],
            'body'               => $data['body'],
            'category'           => $data['category'] ?? 'support_ticket',
            'submission_channel' => $data['channel'] ?? 'ticket',
            'priority'           => $data['priority'] ?? 'normal',
            'status'             => 'open',
            'created_at'         => now(),
        ]);

        $this->activity->log(
            $submitter->id, $this->portalFor($submitter), 'support',
            ActivitySeverity::Info, 'ticket_created', 'Support ticket submitted',
            Str::limit($data['subject'], 140), 'complaint', $ticket->id,
            null, 'log', $submitter->id
        );

        event(new TicketCreated($ticket));
        return $ticket;
    }

    public function submitFeedback(User $submitter, string $body, string $channel = 'in_app'): Complaint
    {
        $complaint = Complaint::create([
            'id'                 => 'cpt_' . Str::lower(Str::random(12)),
            'submitter_id'       => $submitter->id,
            'subject'            => 'User Feedback',
            'body'               => $body,
            'category'           => 'feedback',
            'submission_channel' => $channel,
            'priority'           => 'low',
            'status'             => 'open',
            'created_at'         => now(),
        ]);

        $this->activity->log(
            $submitter->id, $this->portalFor($submitter), 'support',
            ActivitySeverity::Info, 'feedback_submitted', 'Feedback submitted',
            Str::limit($body, 140), 'complaint', $complaint->id,
            null, 'log', $submitter->id
        );

        event(new FeedbackReceived($complaint));

        return $complaint;
    }

    /**
     * Reply to a ticket. If !$isInternal, ActivityService::log() fires for submitter (UC-XP-015).
     */
    public function replyToTicket(Complaint $ticket, User $author, string $body, bool $isInternal = false): ComplaintReply
    {
        $reply = ComplaintReply::create([
            'id'           => 'cpr_' . Str::lower(Str::random(12)),
            'complaint_id' => $ticket->id,
            'author_id'    => $author->id,
            'body'         => $body,
            'is_internal'  => $isInternal ? 1 : 0,
            'created_at'   => now(),
        ]);

        if (!$isInternal && $author->id !== $ticket->submitter_id) {
            $this->activity->log(
                $ticket->submitter_id,                          // 1  userId
                $this->portalFor($ticket->submitter),           // 2  portal
                'support',                                      // 3  module
                ActivitySeverity::Info,                         // 4  severity
                'support_reply',                                // 5  action
                'Support replied to your ticket',              // 6  title
                Str::limit($body, 140),                         // 7  description
                'complaint',                                    // 8  linkableType
                $ticket->id,                                    // 9  linkableId
                $author->id,                                    // 10 relatedUserId
                'notification',                                 // 11 entryType ← correct order
                $author->id                                     // 12 actorId
            );
            event(new TicketReplied($ticket, $reply));
        }

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return $reply;
    }

    public function closeSelfTicket(Complaint $ticket, User $actor): Complaint
    {
        if ($ticket->submitter_id !== $actor->id) {
            abort(403, 'Only the submitter can self-close.');
        }
        $ticket->update(['status' => 'closed', 'resolved_at' => now()]);

        $this->activity->log(
            $actor->id, $this->portalFor($actor), 'support',
            ActivitySeverity::Info, 'ticket_closed', 'Support ticket marked as resolved',
            Str::limit($ticket->subject, 140), 'complaint', $ticket->id,
            null, 'log', $actor->id
        );

        event(new TicketResolved($ticket->fresh()));

        return $ticket->fresh();
    }

    public function getForUser(string $userId): Collection
    {
        return Complaint::where('submitter_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getTicketDetail(string $ticketId): ?array
    {
        $ticket = Complaint::find($ticketId);
        if (!$ticket) return null;
        $replies = ComplaintReply::where('complaint_id', $ticketId)
            ->where('is_internal', 0)
            ->orderBy('created_at')
            ->get();
        return ['ticket' => $ticket, 'replies' => $replies];
    }

    public function getHelpArticles(?string $category = null): Collection
    {
        $q = HelpArticle::where('published', 1);
        if ($category) $q->where('category', $category);
        return $q->orderByDesc('updated_at')->get();
    }

    private function portalFor($user): string
    {
        $role = $user?->role ?? 'practitioner';
        return match ($role) {
            'practitioner'       => 'provider',
            'continuity_steward' => 'continuity_steward',
            'support_steward'    => 'support_steward',
            'business_partner'   => 'business_partner',
            'admin'              => 'admin',
            default              => 'provider',
        };
    }
}
