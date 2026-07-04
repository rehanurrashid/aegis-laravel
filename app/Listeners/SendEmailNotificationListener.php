<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Admin\UserLocked;
use App\Events\Auth\PasswordReset;
use App\Events\Auth\UserRegistered;
use App\Events\Business\ContractCreated;
use App\Events\Referral\ReferralAccepted;
use App\Events\Referral\ReferralCancelled;
use App\Events\Referral\ReferralClosed;
use App\Events\Referral\ReferralDeclined;
use App\Events\Referral\ReferralSent;
use App\Events\Network\ConnectionAccepted;
use App\Events\Network\ConnectionRequestSent;
use App\Events\Business\EngagementRequested;
use App\Events\Service\ServiceRequestSubmitted;
use App\Events\Business\ContractSigned;
use App\Events\Business\InvoicePaid;
use App\Events\Business\InvoiceSent;
use App\Events\Business\PayoutReleased;
use App\Events\Business\ProposalAccepted;
use App\Events\Incident\IncidentClosed;
use App\Events\Plan\AnnualReviewCompleted;
use App\Events\Plan\AnnualReviewDue;
use App\Events\Plan\PlanSigned;
use App\Events\Plan\VaultAttested;
use App\Events\Steward\StewardAccepted;
use App\Events\Steward\StewardDeclined;
use App\Events\Steward\StewardDesignated;
use App\Events\Steward\StewardRemoved;
use App\Events\Support\TicketCreated;
use App\Events\Support\TicketReplied;
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
            $event instanceof ContractCreated         => $this->contractCreated($event),
            $event instanceof ContractSigned          => $this->contractSigned($event),
            $event instanceof InvoiceSent             => $this->invoiceSent($event),
            $event instanceof InvoicePaid             => $this->invoicePaid($event),
            $event instanceof PayoutReleased          => $this->payoutReleased($event),
            $event instanceof TicketCreated           => $this->ticketCreated($event),
            $event instanceof TicketReplied           => $this->ticketReplied($event),
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

    // ── T58/T59: service request ─────────────────────────────────────────────
    private function serviceRequestSubmitted(ServiceRequestSubmitted $e): array
    {
        $req = $e->request;
        return [
            // T58 — practitioner receives the request
            [
                'user_id'  => $req->practitioner_id,
                'gate_key' => 'notify_services',
                'template' => 'emails.gaps.58-service-inquiry-received',
                'data'     => ['service_request_id' => $req->id],
            ],
            // T59 — requester gets confirmation
            [
                'user_id'  => $req->inquirer_id,
                'gate_key' => 'notify_services',
                'template' => 'emails.gaps.59-service-inquiry-responded',
                'data'     => ['service_request_id' => $req->id],
            ],
        ];
    }
}
