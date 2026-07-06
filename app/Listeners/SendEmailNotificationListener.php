<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Admin\UserLocked;
use App\Events\Auth\PasswordReset;
use App\Events\Auth\UserRegistered;
use App\Events\Business\ContractCancelled;
use App\Events\Business\ContractCreated;
use App\Events\Referral\ReferralAccepted;
use App\Events\Referral\ReferralCancelled;
use App\Events\Referral\ReferralClosed;
use App\Events\Referral\ReferralDeclined;
use App\Events\Referral\ReferralSent;
use App\Events\Network\ConnectionAccepted;
use App\Events\Network\ConnectionRequestSent;
use App\Events\News\EventRsvpReceived;
use App\Events\News\EventSubmitted;
use App\Events\Auth\MfaEnabled;
use App\Events\Auth\MfaDisabled;
use App\Events\Auth\NewDeviceLogin;
use App\Events\Auth\AccountClosed;
use App\Events\Plan\PlanReadyForCs;
use App\Events\Plan\PlanReadyForSs;
use App\Events\Plan\PlanVersionUpdated;
use App\Events\Plan\VaultItemShared;
use App\Events\Plan\VaultUnsealed;
use App\Events\Account\SubscriptionCancelled;
use App\Events\Account\SubscriptionTierChanged;
use App\Events\Account\MaatAddonChanged;
use App\Events\Document\DocumentRequested;
use App\Events\Document\DocumentReleaseRequested;
use App\Events\Document\DocumentUpdated;
use App\Events\Messages\MessageSent;
use App\Events\Business\MilestoneSubmitted;
use App\Events\Business\MilestoneApproved;
use App\Events\Service\ServiceRequestResponded;
use App\Events\Steward\StewardRoleChangeRequested;
use App\Events\News\NewsCommented;
use App\Events\News\NewsPostPublished;
use App\Events\Business\EngagementRequested;
use App\Events\Service\ServiceRequestSubmitted;
use App\Events\Business\ContractSigned;
use App\Events\Business\InvoicePaid;
use App\Events\Business\InvoiceSent;
use App\Events\Business\PayoutReleased;
use App\Events\Business\ProposalAccepted;
use App\Events\Business\ProposalDeclined;
use App\Events\Incident\IncidentClosed;
use App\Events\Plan\AnnualReviewCompleted;
use App\Events\Plan\AnnualReviewDue;
use App\Events\Plan\PlanSigned;
use App\Events\Plan\VaultAttested;
use App\Events\Steward\StewardAccepted;
use App\Events\Steward\StewardDeclined;
use App\Events\Steward\StewardDesignated;
use App\Events\Steward\StewardRemoved;
use App\Events\Support\FeedbackReceived;
use App\Events\Support\TicketCreated;
use App\Events\Support\TicketReplied;
use App\Events\Support\TicketResolved;
use App\Jobs\SendEmailJob;
use App\Models\BpPayout;
use App\Models\PlanSteward;
use App\Services\ActivityService;
use App\Services\NotificationService;

/**
 * Resolves the email notifications for each event, checks notify_* gates,
 * then dispatches SendEmailJob for each gated recipient.
 *
 * Incident events are NOT handled here — they go through SendIncidentAlertsListener
 * which is UNGATED.
 */
class SendEmailNotificationListener
{
    public function __construct(
        private NotificationService $notifications,
        private ActivityService $activity
    ) {}

    public function handle(object $event): void
    {
        $notifications = $this->resolve($event);
        foreach ($notifications as $n) {
            if ($this->notifications->shouldSend($n['user_id'], $n['gate_key'])) {
                SendEmailJob::dispatch(
                    $n['template'],
                    $n['data'] ?? [],
                    $n['user_id']
                );
            }
        }
    }

    /**
     * @return array<int, array{user_id:string,gate_key:string,template:string,data?:array}>
     */
    private function resolve(object $event): array
    {
        return match (true) {
            $event instanceof UserRegistered          => $this->userRegistered($event),
            $event instanceof PasswordReset           => $this->passwordReset($event),
            $event instanceof PlanSigned              => $this->planSigned($event),
            $event instanceof VaultAttested           => $this->vaultAttested($event),
            $event instanceof AnnualReviewDue         => $this->annualReviewDue($event),
            $event instanceof AnnualReviewCompleted   => $this->annualReviewCompleted($event),
            $event instanceof StewardDesignated       => $this->stewardDesignated($event),
            $event instanceof StewardAccepted         => $this->stewardAccepted($event),
            $event instanceof StewardDeclined         => $this->stewardDeclined($event),
            $event instanceof StewardRemoved          => $this->stewardRemoved($event),
            $event instanceof IncidentClosed          => $this->incidentClosed($event),
            $event instanceof ProposalAccepted        => $this->proposalAccepted($event),
            $event instanceof ProposalDeclined        => $this->proposalDeclined($event),
            $event instanceof ContractCreated         => $this->contractCreated($event),
            $event instanceof ContractSigned          => $this->contractSigned($event),
            $event instanceof ContractCancelled       => $this->contractCancelled($event),
            $event instanceof InvoiceSent             => $this->invoiceSent($event),
            $event instanceof InvoicePaid             => $this->invoicePaid($event),
            $event instanceof PayoutReleased          => $this->payoutReleased($event),
            $event instanceof TicketCreated           => $this->ticketCreated($event),
            $event instanceof TicketReplied           => $this->ticketReplied($event),
            $event instanceof FeedbackReceived        => $this->feedbackReceived($event),
            $event instanceof TicketResolved          => $this->ticketResolved($event),
            $event instanceof UserLocked              => $this->userLocked($event),
            $event instanceof ReferralSent            => $this->referralSent($event),
            $event instanceof ReferralAccepted        => $this->referralAccepted($event),
            $event instanceof ReferralDeclined        => $this->referralDeclined($event),
            $event instanceof ReferralClosed          => $this->referralClosed($event),
            $event instanceof ReferralCancelled       => $this->referralCancelled($event),
            $event instanceof ConnectionAccepted      => $this->connectionAccepted($event),
            $event instanceof ConnectionRequestSent   => $this->connectionRequestSent($event),
            $event instanceof EngagementRequested     => $this->engagementRequested($event),
            $event instanceof ServiceRequestSubmitted => $this->serviceRequestSubmitted($event),
            $event instanceof NewsPostPublished       => [],   // fan-out to subscribers handled by ActivityFanoutListener
            $event instanceof EventRsvpReceived       => $this->eventRsvpReceived($event),
            $event instanceof NewsCommented           => $this->newsCommented($event),
            $event instanceof EventSubmitted          => $this->eventSubmitted($event),
            $event instanceof MfaEnabled              => $this->mfaEnabled($event),
            $event instanceof MfaDisabled             => $this->mfaDisabled($event),
            $event instanceof NewDeviceLogin          => $this->newDeviceLogin($event),
            $event instanceof AccountClosed           => $this->accountClosed($event),
            $event instanceof PlanReadyForCs          => $this->planReadyForCs($event),
            $event instanceof PlanReadyForSs          => $this->planReadyForSs($event),
            $event instanceof PlanVersionUpdated      => $this->planVersionUpdated($event),
            $event instanceof VaultItemShared         => $this->vaultItemShared($event),
            $event instanceof VaultUnsealed           => $this->vaultUnsealed($event),
            $event instanceof SubscriptionCancelled   => $this->subscriptionCancelled($event),
            $event instanceof SubscriptionTierChanged => $this->subscriptionTierChanged($event),
            $event instanceof MaatAddonChanged        => $this->maatAddonChanged($event),
            $event instanceof DocumentRequested       => $this->documentRequested($event),
            $event instanceof DocumentReleaseRequested => $this->documentReleaseRequested($event),
            $event instanceof DocumentUpdated         => $this->documentUpdated($event),
            $event instanceof MessageSent             => $this->messageSent($event),
            $event instanceof MilestoneSubmitted      => $this->milestoneSubmitted($event),
            $event instanceof MilestoneApproved       => $this->milestoneApproved($event),
            $event instanceof ServiceRequestResponded => $this->serviceRequestResponded($event),
            $event instanceof StewardRoleChangeRequested => $this->stewardRoleChangeRequested($event),
            default                                   => [],
        };
    }

    private function userRegistered(UserRegistered $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_account',
                 'template' => 'emails.account.01-welcome',
                 'data' => ['user_id' => $e->user->id]]];
    }

    private function passwordReset(PasswordReset $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_account',
                 'template' => 'emails.account.06-password-changed',
                 'data' => ['user_id' => $e->user->id]]];
    }

    private function planSigned(PlanSigned $e): array
    {
        $rows = [];
        foreach ($this->stewardRecipients($e->plan->id) as $r) {
            $rows[] = [
                'user_id' => $r['user_id'],
                'gate_key' => 'notify_plan',
                'template' => 'emails.plan.10-plan-signed',
                'data' => ['plan_id' => $e->plan->id, 'practitioner_id' => $e->practitioner->id],
            ];
        }
        $rows[] = [
            'user_id' => $e->practitioner->id, 'gate_key' => 'notify_plan',
            'template' => 'emails.plan.11-plan-signed-confirmation',
            'data' => ['plan_id' => $e->plan->id],
        ];
        return $rows;
    }

    private function vaultAttested(VaultAttested $e): array
    {
        $rows = [];
        foreach ($this->stewardRecipients($e->plan->id) as $r) {
            $rows[] = [
                'user_id' => $r['user_id'], 'gate_key' => 'notify_vault',
                'template' => 'emails.vault.12-vault-attested',
                'data' => ['plan_id' => $e->plan->id],
            ];
        }
        return $rows;
    }

    private function annualReviewDue(AnnualReviewDue $e): array
    {
        $template = match (true) {
            $e->daysUntilDue <= 0  => 'emails.plan.14c-annual-review-overdue',
            $e->daysUntilDue <= 7  => 'emails.plan.14b-annual-review-7-days',
            default                => 'emails.plan.14a-annual-review-30-days',
        };
        return [['user_id' => $e->plan->practitioner_id, 'gate_key' => 'notify_plan',
                 'template' => $template, 'data' => ['plan_id' => $e->plan->id]]];
    }

    private function annualReviewCompleted(AnnualReviewCompleted $e): array
    {
        $rows = [['user_id' => $e->plan->practitioner_id, 'gate_key' => 'notify_plan',
                  'template' => 'emails.plan.15-annual-review-completed',
                  'data' => ['plan_id' => $e->plan->id]]];
        foreach ($this->stewardRecipients($e->plan->id) as $r) {
            $rows[] = ['user_id' => $r['user_id'], 'gate_key' => 'notify_plan',
                       'template' => 'emails.plan.15-annual-review-completed',
                       'data' => ['plan_id' => $e->plan->id]];
        }
        return $rows;
    }

    private function stewardDesignated(StewardDesignated $e): array
    {
        return [['user_id' => $e->steward->steward_id, 'gate_key' => 'notify_steward',
                 'template' => 'emails.steward.07-steward-invitation',
                 'data' => ['plan_steward_id' => $e->steward->id]]];
    }

    private function stewardAccepted(StewardAccepted $e): array
    {
        $plan = \App\Models\ContinuityPlan::find($e->steward->plan_id);
        return [['user_id' => $plan->practitioner_id, 'gate_key' => 'notify_steward',
                 'template' => 'emails.steward.08-steward-accepted',
                 'data' => ['plan_steward_id' => $e->steward->id]]];
    }

    private function stewardDeclined(StewardDeclined $e): array
    {
        $plan = \App\Models\ContinuityPlan::find($e->steward->plan_id);
        return [['user_id' => $plan->practitioner_id, 'gate_key' => 'notify_steward',
                 'template' => 'emails.steward.09-steward-declined',
                 'data' => ['plan_steward_id' => $e->steward->id]]];
    }

    private function stewardRemoved(StewardRemoved $e): array
    {
        return [['user_id' => $e->steward->steward_id, 'gate_key' => 'notify_steward',
                 'template' => 'emails.steward.11-steward-removed',
                 'data' => ['plan_steward_id' => $e->steward->id]]];
    }

    private function incidentClosed(IncidentClosed $e): array
    {
        $rows = [['user_id' => $e->incident->practitioner_id, 'gate_key' => 'notify_incident',
                  'template' => 'emails.incident.29-incident-closed',
                  'data' => ['incident_id' => $e->incident->id]]];
        foreach ($this->stewardRecipients($e->incident->plan_id) as $r) {
            $rows[] = ['user_id' => $r['user_id'], 'gate_key' => 'notify_incident',
                       'template' => 'emails.incident.29-incident-closed',
                       'data' => ['incident_id' => $e->incident->id]];
        }
        return $rows;
    }

    private function proposalAccepted(ProposalAccepted $e): array
    {
        return [['user_id' => $e->proposal->bp_id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.business.40-proposal-accepted',
                 'data' => ['proposal_id' => $e->proposal->id, 'contract_id' => $e->contract->id]]];
    }

    private function proposalDeclined(ProposalDeclined $e): array
    {
        return [['user_id' => $e->proposal->bp_id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.bp.34-proposal-declined',
                 'data' => ['proposal_id' => $e->proposal->id, 'reason' => $e->reason]]];
    }

    private function contractCreated(ContractCreated $e): array
    {
        return [
            ['user_id' => $e->contract->practitioner_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.business.41-contract-created',
             'data' => ['contract_id' => $e->contract->id]],
            ['user_id' => $e->contract->bp_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.business.41-contract-created',
             'data' => ['contract_id' => $e->contract->id]],
        ];
    }

    private function contractSigned(ContractSigned $e): array
    {
        return [
            ['user_id' => $e->contract->practitioner_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.business.42-contract-fully-executed',
             'data' => ['contract_id' => $e->contract->id]],
            ['user_id' => $e->contract->bp_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.business.42-contract-fully-executed',
             'data' => ['contract_id' => $e->contract->id]],
        ];
    }

    private function contractCancelled(ContractCancelled $e): array
    {
        $otherPartyId = $e->actor->id === $e->contract->practitioner_id
            ? $e->contract->bp_id
            : $e->contract->practitioner_id;
        return [
            // Actor confirmation
            ['user_id' => $e->actor->id, 'gate_key' => 'notify_payment',
             'template' => 'emails.gaps.67-contract-cancelled',
             'data' => ['contract_id' => $e->contract->id, 'reason' => $e->reason]],
            // Other party notification
            ['user_id' => $otherPartyId, 'gate_key' => 'notify_payment',
             'template' => 'emails.gaps.67-contract-cancelled',
             'data' => ['contract_id' => $e->contract->id, 'reason' => $e->reason]],
        ];
    }

    private function invoiceSent(InvoiceSent $e): array
    {
        return [['user_id' => $e->invoice->practitioner_id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.business.45-invoice-sent',
                 'data' => ['invoice_id' => $e->invoice->id]]];
    }

    private function invoicePaid(InvoicePaid $e): array
    {
        return [['user_id' => $e->invoice->bp_id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.business.46-invoice-paid',
                 'data' => ['invoice_id' => $e->invoice->id]]];
    }

    private function payoutReleased(PayoutReleased $e): array
    {
        $recipientId = $e->payout instanceof BpPayout ? $e->payout->bp_id : $e->payout->cs_id;
        return [['user_id' => $recipientId, 'gate_key' => 'notify_payment',
                 'template' => 'emails.business.48-payout-released',
                 'data' => ['payout_id' => $e->payout->id]]];
    }

    private function ticketCreated(TicketCreated $e): array
    {
        return [['user_id' => $e->complaint->submitter_id, 'gate_key' => 'notify_account',
                 'template' => 'emails.support.60-ticket-created',
                 'data' => ['complaint_id' => $e->complaint->id]]];
    }

    private function ticketReplied(TicketReplied $e): array
    {
        return [['user_id' => $e->complaint->submitter_id, 'gate_key' => 'notify_account',
                 'template' => 'emails.support.61-ticket-replied',
                 'data' => ['complaint_id' => $e->complaint->id, 'reply_id' => $e->reply->id]]];
    }

    private function feedbackReceived(FeedbackReceived $e): array
    {
        // Submitter confirmation only — feedback is internal, no email to admin by design
        return [['user_id' => $e->complaint->submitter_id, 'gate_key' => 'notify_account',
                 'template' => 'emails.support.49-feedback-received',
                 'data' => ['complaint_id' => $e->complaint->id]]];
    }

    private function ticketResolved(TicketResolved $e): array
    {
        return [['user_id' => $e->complaint->submitter_id, 'gate_key' => 'notify_account',
                 'template' => 'emails.support.48-ticket-resolved',
                 'data' => ['complaint_id' => $e->complaint->id]]];
    }

    private function userLocked(UserLocked $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_account',
                 'template' => 'emails.account.04-account-locked',
                 'data' => ['reason' => $e->reason]]];
    }

    // ── Referral emails ──────────────────────────────────────────────────────

    private function referralSent(ReferralSent $e): array
    {
        return [
            // Recipient: you have a new referral to review
            ['user_id'  => $e->recipient->id,
             'gate_key' => 'notify_referral_received',
             'template' => 'emails.referral.20-referral-received',
             'data'     => ['referral_id' => $e->referral->id]],
            // Sender: confirmation that referral was delivered
            ['user_id'  => $e->sender->id,
             'gate_key' => 'notify_referral_sent',
             'template' => 'emails.referral.20a-referral-sent-confirmation',
             'data'     => ['referral_id' => $e->referral->id]],
        ];
    }

    private function referralAccepted(ReferralAccepted $e): array
    {
        return [
            ['user_id'  => $e->referral->sender_id,
             'gate_key' => 'notify_referral_received',
             'template' => 'emails.referral.21-referral-accepted',
             'data'     => ['referral_id' => $e->referral->id]],
        ];
    }

    private function referralDeclined(ReferralDeclined $e): array
    {
        return [
            ['user_id'  => $e->referral->sender_id,
             'gate_key' => 'notify_referral_received',
             'template' => 'emails.referral.22-referral-declined',
             'data'     => ['referral_id' => $e->referral->id]],
        ];
    }

    private function referralClosed(ReferralClosed $e): array
    {
        $otherPartyId = $e->actor->id === $e->referral->sender_id
            ? $e->referral->recipient_id
            : $e->referral->sender_id;

        return [
            ['user_id'  => $otherPartyId,
             'gate_key' => 'notify_referral_received',
             'template' => 'emails.referral.23-referral-completed',
             'data'     => ['referral_id' => $e->referral->id, 'actor_id' => $e->actor->id]],
        ];
    }

    private function referralCancelled(ReferralCancelled $e): array
    {
        return [
            ['user_id'  => $e->referral->recipient_id,
             'gate_key' => 'notify_referral_received',
             'template' => 'emails.referral.24-referral-cancelled',
             'data'     => ['referral_id' => $e->referral->id]],
        ];
    }

    private function stewardRecipients(string $planId): array
    {
        return PlanSteward::where('plan_id', $planId)
            ->where('status', 'active')
            ->get()
            ->map(fn ($ps) => [
                'user_id' => $ps->steward_id,
                'portal'  => $ps->steward_type === 'continuity_steward' ? 'continuity_steward' : 'support_steward',
            ])
            ->toArray();
    }

    // ── T43: connection accepted — notify the requester ───────────────────────
    private function connectionAccepted(ConnectionAccepted $e): array
    {
        $conn    = $e->connection;
        $userId  = $conn->user_id;
        return [[
            'user_id'  => $userId,
            'gate_key' => 'notify_network',
            'template' => 'emails.network.43-connection-accepted',
            'data'     => ['connection_id' => $conn->id, 'accepter_id' => $e->accepter->id],
        ]];
    }

    // T42 — new connection request received by recipient
    private function connectionRequestSent(ConnectionRequestSent $e): array
    {
        return [[
            'user_id'  => $e->recipient->id,
            'gate_key' => 'notify_network',
            'template' => 'emails.network.42-connection-request',
            'data'     => [
                'practitioner_name' => $e->recipient->display_name,
                'requester_name'    => $e->requester->display_name,
                'requester_role'    => $e->requester->title ?? ($e->requester->role?->value ?? ''),
                'request_note'      => $e->networkRequest->message,
                'review_url'        => config('app.url') . '/provider/network',
            ],
        ]];
    }

    // Engagement requested — notify the BP (hire / quote / consultation)
    private function engagementRequested(EngagementRequested $e): array
    {
        // Use service-inquiry template (gaps/58) as the closest match
        // until a dedicated BP engagement template is built.
        return [[
            'user_id'  => $e->bp->id,
            'gate_key' => 'notify_business',
            'template' => 'emails.gaps.58-service-inquiry-received',
            'data'     => [
                'practitioner_name' => $e->practitioner->display_name,
                'bp_name'           => $e->bp->display_name,
                'type'              => $e->type,
                'details'           => $e->details,
            ],
        ]];
    }

    // ── T70: event RSVP confirmation ─────────────────────────────────────────
    private function eventRsvpReceived(EventRsvpReceived $e): array
    {
        $ev       = $e->event;
        $attendee = $e->attendee;
        return [[
            'user_id'  => $attendee->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.news.70-event-rsvp',
            'data'     => [
                'practitioner_name' => $attendee->display_name,
                'event_title'       => $ev->title,
                'event_date'        => $ev->starts_at?->format('l, F j, Y \a\t g:i A') ?? null,
                'event_location'    => $ev->location,
                'ceu_credits'       => (float) ($ev->ceu_credits ?? 0),
                'events_url'        => rtrim(config('app.url'), '/') . '/provider/news/events',
            ],
        ]];
    }

    // ── T71: new comment on post ─────────────────────────────────────────────
    private function newsCommented(NewsCommented $e): array
    {
        $comment = $e->comment;
        $post    = $comment->post ?? \App\Models\NewsPost::find($comment->post_id);
        if (!$post || !$post->author_id || $post->author_id === $comment->author_id) {
            return [];
        }
        $commenter = $comment->author ?? \App\Models\User::find($comment->author_id);
        return [[
            'user_id'  => $post->author_id,
            'gate_key' => 'notify_email',
            'template' => 'emails.news.71-post-commented',
            'data'     => [
                'author_name'    => \App\Models\User::find($post->author_id)?->display_name ?? 'there',
                'commenter_name' => $commenter?->display_name ?? 'A member',
                'post_title'     => $post->title,
                'comment_body'   => \Illuminate\Support\Str::limit($comment->body, 200),
                'post_url'       => rtrim(config('app.url'), '/') . '/provider/news',
            ],
        ]];
    }

    // ── T72: event submission confirmation ───────────────────────────────────
    private function eventSubmitted(EventSubmitted $e): array
    {
        return [[
            'user_id'  => $e->submitter->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.news.72-event-submitted',
            'data'     => [
                'submitter_name' => $e->submitter->display_name,
                'event_title'    => $e->event->title,
                'event_date'     => $e->event->starts_at?->format('F j, Y') ?? null,
            ],
        ]];
    }

    // ── MFA / Auth security ──────────────────────────────────────────────────
    private function mfaEnabled(MfaEnabled $e): array {
        return [[
            'user_id'  => $e->user->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.auth.05-mfa-enabled',
            'data'     => ['user_name' => $e->user->display_name],
        ]];
    }

    private function mfaDisabled(MfaDisabled $e): array {
        return [[
            'user_id'  => $e->user->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.auth.06-mfa-disabled',
            'data'     => ['user_name' => $e->user->display_name],
        ]];
    }

    private function newDeviceLogin(NewDeviceLogin $e): array {
        return [[
            'user_id'  => $e->user->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.auth.07-new-device-login',
            'data'     => [
                'user_name'   => $e->user->display_name,
                'device'      => $e->device ?? 'Unknown device',
                'ip_address'  => $e->ipAddress ?? null,
                'logged_in_at'=> now()->format('F j, Y \a\t g:i A T'),
            ],
        ]];
    }

    private function accountClosed(AccountClosed $e): array {
        return [[
            'user_id'  => $e->user->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.auth.09-account-closure',
            'data'     => ['user_name' => $e->user->display_name],
        ]];
    }

    // ── Plan ready ───────────────────────────────────────────────────────────
    private function planReadyForCs(PlanReadyForCs $e): array {
        return [[
            'user_id'  => $e->steward->id,
            'gate_key' => 'notify_steward',
            'template' => 'emails.plan.11-plan-ready-cs',
            'data'     => [
                'steward_name'      => $e->steward->display_name,
                'practitioner_name' => $e->plan->practitioner?->display_name ?? 'Your practitioner',
                'plan_url'          => rtrim(config('app.url'), '/') . '/continuity-steward/plan',
            ],
        ]];
    }

    private function planReadyForSs(PlanReadyForSs $e): array {
        return [[
            'user_id'  => $e->steward->id,
            'gate_key' => 'notify_steward',
            'template' => 'emails.plan.11-plan-ready-ss',
            'data'     => [
                'steward_name'      => $e->steward->display_name,
                'practitioner_name' => $e->plan->practitioner?->display_name ?? 'Your practitioner',
                'plan_url'          => rtrim(config('app.url'), '/') . '/support-steward/plan',
            ],
        ]];
    }

    private function planVersionUpdated(PlanVersionUpdated $e): array {
        // Notify assigned stewards that the plan changed
        $stewards = \App\Models\PlanSteward::where('plan_id', $e->plan->id)
            ->where('status', 'active')
            ->with('steward')
            ->get();
        return $stewards->map(fn($ps) => [
            'user_id'  => $ps->steward_id,
            'gate_key' => 'notify_plan',
            'template' => 'emails.plan.16-plan-version-updated',
            'data'     => [
                'steward_name'      => $ps->steward?->display_name ?? 'Steward',
                'practitioner_name' => $e->plan->practitioner?->display_name ?? 'Your practitioner',
                'change_summary'    => $e->changeSummary ?? 'The continuity plan has been updated.',
                'plan_url'          => rtrim(config('app.url'), '/') . '/provider/plan',
            ],
        ])->values()->toArray();
    }

    // ── Vault ────────────────────────────────────────────────────────────────
    private function vaultItemShared(VaultItemShared $e): array {
        return array_map(fn($sid) => [
            'user_id'  => $sid,
            'gate_key' => 'notify_vault',
            'template' => 'emails.gaps.63-vault-item-shared',
            'data'     => [
                'steward_name' => \App\Models\User::find($sid)?->display_name ?? 'Steward',
                'sharer_name'  => $e->sharer->display_name,
                'item_title'   => $e->item->title,
                'vault_url'    => rtrim(config('app.url'), '/') . '/continuity-steward/vault',
            ],
        ], $e->stewardIds ?? []);
    }

    private function vaultUnsealed(VaultUnsealed $e): array {
        // Notify the practitioner that vault is now accessible
        return [[
            'user_id'  => $e->plan->practitioner_id,
            'gate_key' => 'notify_vault',
            'template' => 'emails.incident.28-vault-unlocked',
            'data'     => [
                'practitioner_name' => $e->plan->practitioner?->display_name ?? 'Practitioner',
                'incident_id'       => $e->incident->id,
            ],
        ]];
    }

    // ── Subscription / account ───────────────────────────────────────────────
    private function subscriptionCancelled(SubscriptionCancelled $e): array {
        return [[
            'user_id'  => $e->user->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.gaps.68-subscription-cancelled',
            'data'     => [
                'user_name'    => $e->user->display_name,
                'cancelled_at' => now()->format('F j, Y'),
            ],
        ]];
    }

    private function subscriptionTierChanged(SubscriptionTierChanged $e): array {
        $template = ($e->direction ?? 'upgrade') === 'upgrade'
            ? 'emails.admin.51-plan-upgraded'
            : 'emails.admin.52-plan-downgraded';
        return [[
            'user_id'  => $e->user->id,
            'gate_key' => 'notify_email',
            'template' => $template,
            'data'     => [
                'user_name' => $e->user->display_name,
                'new_tier'  => $e->tier ?? 'updated',
            ],
        ]];
    }

    private function maatAddonChanged(MaatAddonChanged $e): array {
        return [[
            'user_id'  => $e->user->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.gaps.69-maat-addon-change',
            'data'     => [
                'user_name' => $e->user->display_name,
                'enabled'   => $e->enabled ?? true,
            ],
        ]];
    }

    // ── Documents ────────────────────────────────────────────────────────────
    private function documentRequested(DocumentRequested $e): array {
        return [[
            'user_id'  => $e->plan->practitioner_id,
            'gate_key' => 'notify_plan',
            'template' => 'emails.gaps.60-document-requested',
            'data'     => [
                'practitioner_name' => $e->plan->practitioner?->display_name ?? 'Practitioner',
                'requester_name'    => $e->requester->display_name,
                'document_title'    => $e->document->title ?? 'Document',
            ],
        ]];
    }

    private function documentReleaseRequested(DocumentReleaseRequested $e): array {
        return [[
            'user_id'  => $e->document->practitioner_id ?? $e->document->owner_id ?? null,
            'gate_key' => 'notify_plan',
            'template' => 'emails.gaps.61-document-release-requested',
            'data'     => [
                'requester_name' => $e->requester->display_name,
                'document_title' => $e->document->title ?? 'Document',
            ],
        ]];
    }

    private function documentUpdated(DocumentUpdated $e): array {
        // Notify assigned stewards
        $stewards = \App\Models\PlanSteward::where('plan_id', $e->document->plan_id ?? null)
            ->where('status', 'active')->get();
        if ($stewards->isEmpty()) return [];
        return $stewards->map(fn($ps) => [
            'user_id'  => $ps->steward_id,
            'gate_key' => 'notify_plan',
            'template' => 'emails.gaps.64-document-updated',
            'data'     => [
                'document_title' => $e->document->title ?? 'Document',
                'updater_name'   => $e->updater->display_name,
            ],
        ])->values()->toArray();
    }

    // ── Messages ─────────────────────────────────────────────────────────────
    private function messageSent(MessageSent $e): array {
        // participant_ids is a JSON array on message_threads — no pivot table
        $thread = $e->thread;
        $sender = $e->sender;
        $participantIds = collect($thread->participant_ids ?? [])
            ->filter(fn($id) => $id !== $sender->id)
            ->values();
        if ($participantIds->isEmpty()) return [];
        $recipients = \App\Models\User::whereIn('id', $participantIds)->get(['id', 'display_name']);
        return $recipients->map(fn($u) => [
            'user_id'  => $u->id,
            'gate_key' => 'notify_message',
            'template' => 'emails.messages.new-message',
            'data'     => [
                'recipient_name' => $u->display_name ?? 'Member',
                'sender_name'    => $sender->display_name,
                'message_body'   => \Illuminate\Support\Str::limit($e->message->body ?? '', 200),
                'thread_url'     => rtrim(config('app.url'), '/') . '/provider/messages',
            ],
        ])->values()->toArray();
    }

    // ── Milestones ───────────────────────────────────────────────────────────
    private function milestoneSubmitted(MilestoneSubmitted $e): array {
        $contract = $e->milestone->contract ?? \App\Models\BpContract::find($e->milestone->contract_id);
        if (!$contract) return [];
        return [[
            'user_id'  => $contract->practitioner_id,
            'gate_key' => 'notify_payment',
            'template' => 'emails.bp.36-milestone-submitted',
            'data'     => [
                'practitioner_name' => $contract->practitioner?->display_name ?? 'Practitioner',
                'bp_name'           => $contract->bp?->display_name ?? 'Business Partner',
                'milestone_title'   => $e->milestone->title,
                'contract_url'      => rtrim(config('app.url'), '/') . '/provider/contracts/' . $contract->id,
            ],
        ]];
    }

    private function milestoneApproved(MilestoneApproved $e): array {
        $contract = $e->milestone->contract ?? \App\Models\BpContract::find($e->milestone->contract_id);
        if (!$contract) return [];
        return [[
            'user_id'  => $contract->bp_id,
            'gate_key' => 'notify_payment',
            'template' => 'emails.bp.37-milestone-approved',
            'data'     => [
                'bp_name'          => $contract->bp?->display_name ?? 'Business Partner',
                'milestone_title'  => $e->milestone->title,
                'contract_url'     => rtrim(config('app.url'), '/') . '/business-partner/contracts/' . $contract->id,
            ],
        ]];
    }

    // ── Service request responded ─────────────────────────────────────────────
    private function serviceRequestResponded(ServiceRequestResponded $e): array {
        return [[
            'user_id'  => $e->request->provider_id ?? $e->request->practitioner_id,
            'gate_key' => 'notify_email',
            'template' => 'emails.gaps.59-service-inquiry-responded',
            'data'     => [
                'provider_name'  => \App\Models\User::find($e->request->provider_id ?? $e->request->practitioner_id)?->display_name ?? 'Provider',
                'response_status'=> $e->status ?? 'responded',
            ],
        ]];
    }

    // ── Steward role change ───────────────────────────────────────────────────
    private function stewardRoleChangeRequested(StewardRoleChangeRequested $e): array {
        return [[
            'user_id'  => $e->steward->steward_id,
            'gate_key' => 'notify_steward',
            'template' => 'emails.steward.23-cs-role-change',
            'data'     => [
                'steward_name'   => $e->steward->steward?->display_name ?? 'Steward',
                'requested_role' => $e->requestedRole ?? 'updated role',
                'note'           => $e->requestNote ?? null,
            ],
        ]];
    }
}
