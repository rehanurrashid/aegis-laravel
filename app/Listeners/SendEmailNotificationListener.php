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
use App\Events\Business\SubscriptionCancelled;
use App\Events\Business\SubscriptionTierChanged;
use App\Events\Business\MaatAddonChanged;
use App\Events\Account\SubscriptionRenewalUpcoming;
use App\Events\Stripe\PaymentFailed;
use App\Events\Document\DocumentRequested;
use App\Events\Document\DocumentReleaseRequested;
use App\Events\Document\DocumentUpdated;
use App\Events\Messages\MessageSent;
use App\Events\Business\MilestoneSubmitted;
use App\Events\Business\MilestoneApproved;
use App\Events\Service\ServiceRequestResponded;
use App\Events\Service\SessionCancelled;
use App\Events\Service\SessionCompleted;
use App\Events\Service\SessionDepositPaid;
use App\Events\Service\SessionBalancePaid;
use App\Events\Service\SessionRefundRequested;
use App\Events\Service\SessionRefundApproved;
use App\Events\Service\SessionRefundDenied;
use App\Events\Service\SessionRefundEscalated;
use App\Events\Steward\StewardRoleChangeRequested;
use App\Events\News\NewsCommented;
use App\Events\News\NewsPostPublished;
use App\Events\Business\EngagementRequested;
use App\Events\Service\ServiceRequestSubmitted;
use App\Events\Business\ContractSigned;
use App\Events\Business\ContractFullyFunded;
use App\Events\Business\EscrowFunded;
use App\Events\Business\MilestoneReadyForReview;
use App\Events\Business\MilestoneRevisionRequested;
use App\Events\Business\MilestoneReleased;
use App\Events\Business\MilestoneRefunded;
use App\Events\Business\MilestoneAutoReleased;
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
            $event instanceof SessionCancelled        => $this->sessionCancelled($event),
            $event instanceof SessionCompleted        => $this->sessionCompleted($event),
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
            $event instanceof SubscriptionRenewalUpcoming => $this->subscriptionRenewalUpcoming($event),
            $event instanceof PaymentFailed              => $this->paymentFailed($event),
            $event instanceof DocumentRequested       => $this->documentRequested($event),
            $event instanceof DocumentReleaseRequested => $this->documentReleaseRequested($event),
            $event instanceof DocumentUpdated         => $this->documentUpdated($event),
            $event instanceof MessageSent             => $this->messageSent($event),
            $event instanceof MilestoneSubmitted      => $this->milestoneSubmitted($event),
            $event instanceof MilestoneApproved       => $this->milestoneApproved($event),
            $event instanceof ProposalSubmitted       => $this->proposalSubmittedBp($event),
            $event instanceof ProposalWithdrawn          => $this->proposalWithdrawnBp($event),
            $event instanceof ServiceRequestResponded     => $this->serviceRequestResponded($event),
            // Wave 2 — Escrow events
            $event instanceof EscrowFunded                => $this->escrowFunded($event),
            $event instanceof ContractFullyFunded         => $this->contractFullyFunded($event),
            $event instanceof MilestoneReadyForReview     => $this->milestoneReadyForReview($event),
            $event instanceof MilestoneRevisionRequested  => $this->milestoneRevisionRequested($event),
            $event instanceof MilestoneReleased           => $this->milestoneReleased($event),
            $event instanceof MilestoneRefunded           => $this->milestoneRefunded($event),
            $event instanceof MilestoneAutoReleased       => $this->milestoneAutoReleased($event),
            $event instanceof SessionDepositPaid      => $this->sessionDepositPaid($event),
            $event instanceof SessionBalancePaid      => $this->sessionBalancePaid($event),
            $event instanceof SessionRefundRequested  => $this->sessionRefundRequested($event),
            $event instanceof SessionRefundApproved   => $this->sessionRefundApproved($event),
            $event instanceof SessionRefundDenied     => $this->sessionRefundDenied($event),
            $event instanceof SessionRefundEscalated  => $this->sessionRefundEscalated($event),
            $event instanceof StewardRoleChangeRequested => $this->stewardRoleChangeRequested($event),
            // CS Engagement Contract flow
            $event instanceof \App\Events\Incident\IncidentReadyForClosure => $this->incidentReadyForClosure($event),
            $event instanceof \App\Events\Incident\IncidentClosureVerified => $this->incidentClosureVerified($event),
            $event instanceof \App\Events\Incident\IncidentAutoClosed     => $this->incidentAutoClosed($event),
            $event instanceof \App\Events\Cs\CsInvoiceAutoGenerated       => $this->csInvoiceAutoGenerated($event),
            // Dispute system
            $event instanceof \App\Events\Dispute\DisputeOpened           => $this->disputeOpened($event),
            $event instanceof \App\Events\Dispute\DisputeReplied          => $this->disputeReplied($event),
            $event instanceof \App\Events\Dispute\DisputeResolved         => $this->disputeResolved($event),
            default                                   => [],
        };
    }

    private function userRegistered(UserRegistered $e): array
    {
        $user   = $e->user;
        $role   = $user->role instanceof \App\Enums\UserRole ? $user->role->value : (string) $user->role;
        $csType = $user->cs_account_type instanceof \App\Enums\CsAccountType
            ? $user->cs_account_type->value
            : (string) ($user->cs_account_type ?? '');

        // Paid roles (practitioner, BP, business CS) receive the welcome email
        // AFTER their subscription is activated (fired in OnboardingController::subscribe()).
        // Free roles (invited CS, SS) have no payment step — send welcome now.
        $isPaid = $role === 'practitioner'
            || $role === 'business_partner'
            || ($role === 'continuity_steward' && $csType === 'business');

        if ($isPaid) {
            return []; // welcome fires in OnboardingController::subscribe()
        }

        return [['user_id' => $user->id, 'gate_key' => 'notify_account',
                 'template' => 'emails.account.01-welcome',
                 'data' => ['user_id' => $user->id]]];
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
        $payout = $e->payout;
        $recipientId = $payout instanceof BpPayout ? $payout->bp_id : $payout->cs_id;

        $result = [[
            // Recipient (BP/CS) — payout notification
            'user_id'  => $recipientId,
            'gate_key' => 'notify_payment',
            'template' => 'emails.business.48-payout-released',
            'data'     => ['payout_id' => $payout->id],
        ]];

        // Provider confirmation (only for BP payouts that have a provider_id)
        if ($payout instanceof BpPayout && !empty($payout->provider_id)) {
            $amount = '$' . number_format(($payout->amount_cents ?? 0) / 100, 2);
            $bpName = \App\Models\User::find($recipientId)?->display_name ?? 'Business Partner';
            $result[] = [
                'user_id'  => $payout->provider_id,
                'gate_key' => 'notify_payment',
                'template' => 'emails.business.48-payout-released',
                'data'     => [
                    'payout_id'    => $payout->id,
                    'payout_amount'=> $amount,
                    'bp_name'      => $bpName,
                ],
            ];
        }

        return $result;
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
                'device_label'   => $e->device ?? 'Unknown device',
                'location_label' => $e->ipAddress ?? null,
                'login_at'       => now()->format('F j, Y \\a\\t g:i A T'),
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
    private function subscriptionRenewalUpcoming(SubscriptionRenewalUpcoming $e): array
    {
        return [[
            'user_id'  => $e->user->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.admin.55-renewal-upcoming',
            'data'     => [
                'user_name'    => $e->user->display_name,
                'renewal_date' => $e->renewalDate ?? null,
                'plan_label'   => $e->planLabel   ?? null,
                'amount_cents' => $e->amountCents  ?? 0,
            ],
        ]];
    }

    private function paymentFailed(PaymentFailed $e): array
    {
        $user = $e->user;
        return [
            'to'        => $user->email,
            'subject'   => 'Payment failed — action required',
            'template'  => 'admin.53-payment-failed',
            'variables' => [
                'display_name'   => $user->display_name,
                'amount'         => '$' . number_format($e->amountCents / 100, 2),
                'failure_reason' => $e->failureReason,
                'next_retry'     => $e->retryDate ?? 'No retry scheduled',
                'update_url'     => url('/provider/settings?tab=invoices'),
            ],
        ];
    }

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

    // ── Proposal submitted (BP confirmation) ─────────────────────────────────
    private function proposalSubmittedBp(\App\Events\Business\ProposalSubmitted $e): array
    {
        $job = \App\Models\BpJob::find($e->proposal->job_id);
        if (!$job) return [];
        return [
            // Confirmation to BP (actor)
            [
                'user_id'  => $e->proposal->bp_id,
                'gate_key' => 'notify_payment',
                'template' => 'emails.bp.32-support-request-received',
                'data'     => [
                    'proposal_id'       => $e->proposal->id,
                    'proposal_title'    => $job->title,
                    'practitioner_name' => \App\Models\User::find($job->practitioner_id)?->display_name ?? 'Practitioner',
                    'submitted_at'      => $e->proposal->submitted_at?->toFormattedDateString() ?? now()->toFormattedDateString(),
                ],
            ],
        ];
    }

    // ── Proposal withdrawn (provider notification) ─────────────────────────────
    private function proposalWithdrawnBp(\App\Events\Business\ProposalWithdrawn $e): array
    {
        $job = \App\Models\BpJob::find($e->proposal->job_id);
        if (!$job) return [];
        return [
            // Notify the provider that a BP withdrew
            [
                'user_id'  => $job->practitioner_id,
                'gate_key' => 'notify_payment',
                'template' => 'emails.bp.34-proposal-declined',  // reuse decline template (closest match)
                'data'     => [
                    'proposal_id'       => $e->proposal->id,
                    'proposal_title'    => $job->title,
                    'bp_name'           => $e->actor->display_name,
                    'practitioner_name' => \App\Models\User::find($job->practitioner_id)?->display_name ?? 'Practitioner',
                    'decline_reason'    => $e->actor->display_name . ' has withdrawn their proposal.',
                    'declined_at'       => now()->toFormattedDateString(),
                ],
            ],
        ];
    }

    // ── Service request submitted ─────────────────────────────────────────────
    private function serviceRequestSubmitted(ServiceRequestSubmitted $e): array {
        return [[
            'user_id'  => $e->request->practitioner_id,
            'gate_key' => 'notify_email',
            'template' => 'emails.gaps.58-service-inquiry-received',
            'data'     => [
                'practitioner_name'  => \App\Models\User::find($e->request->practitioner_id)?->display_name ?? 'Practitioner',
                'inquirer_name'      => $e->requester->display_name ?? 'A provider',
                'service_title'      => $e->request->service?->title ?? 'a service',
                'inquiry_message'    => $e->request->message ?? null,
                'service_request_url'=> rtrim(config('app.url'), '/') . '/provider/services',
            ],
        ]];
    }

    // ── Service request responded ─────────────────────────────────────────────
    private function serviceRequestResponded(ServiceRequestResponded $e): array {
        $inquirer    = \App\Models\User::find($e->request->inquirer_id);
        $practitioner = \App\Models\User::find($e->request->practitioner_id);
        if (!$inquirer) return [];
        return [[
            'user_id'  => $e->request->inquirer_id,
            'gate_key' => 'notify_email',
            'template' => 'emails.gaps.59-service-inquiry-responded',
            'data'     => [
                'inquirer_name'    => $inquirer->display_name ?? 'there',
                'practitioner_name'=> $practitioner?->display_name ?? 'The practitioner',
                'status_label'     => $e->outcome,
                'service_title'    => $e->request->service?->title ?? '',
                'response_note'    => $e->request->response_note ?? null,
                'service_url'      => rtrim(config('app.url'), '/') . '/provider/services',
            ],
        ]];
    }

    // ── Session cancelled ─────────────────────────────────────────────────────
    private function sessionCancelled(SessionCancelled $e): array {
        // Notify the OTHER party (not the actor who cancelled)
        $session      = $e->session;
        $actorId      = $e->actor->id;
        $recipientId  = $actorId === $session->practitioner_id
            ? $session->client_id
            : $session->practitioner_id;
        $recipient    = \App\Models\User::find($recipientId);
        if (!$recipient) return [];
        return [[
            'user_id'  => $recipientId,
            'gate_key' => 'notify_email',
            'template' => 'emails.services.60-session-cancelled',
            'data'     => [
                'recipient_name'   => $recipient->display_name ?? 'there',
                'other_party_name' => $e->actor->display_name,
                'service_title'    => $session->service?->title ?? 'session',
                'scheduled_date'   => $session->scheduled_at?->format('M j, Y g:i A') ?? null,
                'cancel_reason'    => $e->reason,
                'services_url'     => rtrim(config('app.url'), '/') . '/provider/services',
            ],
        ]];
    }

    // ── Session completed / payment released ──────────────────────────────────
    private function sessionCompleted(SessionCompleted $e): array {
        $amount = '$' . number_format($e->amountCents / 100, 2);
        $payoutNote = $e->practitioner->stripe_connected
            ? 'Transfer to your connected Stripe account is underway.'
            : 'Payment will be released once you connect your Stripe account in Settings.';
        return [[
            'user_id'  => $e->practitioner->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.services.61-session-completed',
            'data'     => [
                'practitioner_name' => $e->practitioner->display_name ?? 'there',
                'client_name'       => $e->client->display_name ?? 'Your client',
                'service_title'     => $e->session->service?->title ?? 'your service',
                'amount'            => $amount,
                'payout_note'       => $payoutNote,
                'services_url'      => rtrim(config('app.url'), '/') . '/provider/services',
            ],
        ]];
    }

    // ── Session deposit paid ──────────────────────────────────────────────────
    // Notifies provider (payment received) and client (receipt confirmation)
    private function sessionDepositPaid(SessionDepositPaid $e): array {
        $dep     = '$' . number_format($e->depositCents / 100, 2);
        $bal     = '$' . number_format(($e->session->agreed_amount_cents - $e->depositCents) / 100, 2);
        $date    = $e->session->scheduled_at?->format('M j, Y g:i A T') ?? null;
        $payNote = $e->practitioner->stripe_connected
            ? 'Funds will be transferred to your connected Stripe account.'
            : 'Funds will be released once you connect your Stripe account in Settings.';
        $url     = rtrim(config('app.url'), '/') . '/provider/services';
        return [
            // Provider — payment received
            [
                'user_id'  => $e->practitioner->id,
                'gate_key' => 'notify_email',
                'template' => 'emails.services.62-session-deposit-paid',
                'data'     => [
                    'recipient_name'   => $e->practitioner->display_name ?? 'there',
                    'other_party_name' => $e->client->display_name ?? 'Your client',
                    'service_title'    => $e->session->service?->title ?? 'your service',
                    'deposit_amount'   => $dep,
                    'balance_due'      => $bal,
                    'session_date'     => $date,
                    'payout_note'      => $payNote,
                    'services_url'     => $url,
                ],
            ],
            // Client — receipt confirmation
            [
                'user_id'  => $e->client->id,
                'gate_key' => 'notify_email',
                'template' => 'emails.services.62-session-deposit-paid',
                'data'     => [
                    'recipient_name'   => $e->client->display_name ?? 'there',
                    'other_party_name' => null, // not shown in client copy
                    'service_title'    => $e->session->service?->title ?? 'your session',
                    'deposit_amount'   => $dep,
                    'balance_due'      => $bal,
                    'session_date'     => $date,
                    'payout_note'      => 'The remaining balance will be collected when you confirm the session complete.',
                    'services_url'     => $url,
                ],
            ],
        ];
    }

    // ── Session balance paid ──────────────────────────────────────────────────
    // Notifies provider (full payment received)
    private function sessionBalancePaid(SessionBalancePaid $e): array {
        $total   = '$' . number_format($e->session->agreed_amount_cents / 100, 2);
        $dep     = '$' . number_format(($e->session->deposit_cents ?? 0) / 100, 2);
        $bal     = '$' . number_format($e->balanceCents / 100, 2);
        $payNote = $e->practitioner->stripe_connected
            ? 'Full transfer underway to your connected Stripe account.'
            : 'Payment will be released once you connect your Stripe account in Settings.';
        $url     = rtrim(config('app.url'), '/') . '/provider/services';
        return [[
            'user_id'  => $e->practitioner->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.services.63-session-balance-paid',
            'data'     => [
                'recipient_name'   => $e->practitioner->display_name ?? 'there',
                'other_party_name' => $e->client->display_name ?? 'Your client',
                'service_title'    => $e->session->service?->title ?? 'your service',
                'total_amount'     => $total,
                'deposit_amount'   => $dep,
                'balance_amount'   => $bal,
                'payout_note'      => $payNote,
                'services_url'     => $url,
            ],
        ]];
    }

    // ── Session refund requested ──────────────────────────────────────────────
    // Notifies provider — must review within DISPUTE_RESPONDENT_REPLY_DAYS
    private function sessionRefundRequested(SessionRefundRequested $e): array {
        $rr       = $e->refundRequest;
        $amount   = '$' . number_format($rr->amount_requested_cents / 100, 2);
        $replyDays = (int) (env('DISPUTE_RESPONDENT_REPLY_DAYS', 5));
        $typeLabel = $rr->refund_type instanceof \App\Enums\SessionRefundType
            ? $rr->refund_type->label()
            : ucfirst(str_replace('_', ' ', (string) $rr->refund_type));
        return [[
            'user_id'  => $e->practitioner->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.services.64-session-refund-requested',
            'data'     => [
                'recipient_name'    => $e->practitioner->display_name ?? 'there',
                'client_name'       => $e->client->display_name ?? 'Your client',
                'service_title'     => $e->session->service?->title ?? 'the session',
                'amount_requested'  => $amount,
                'refund_type_label' => $typeLabel,
                'reason'            => $rr->reason ?? null,
                'reason_detail'     => $rr->reason_detail ?? null,
                'reply_days'        => $replyDays,
                'services_url'      => rtrim(config('app.url'), '/') . '/provider/services',
            ],
        ]];
    }

    // ── Session refund approved ───────────────────────────────────────────────
    // Notifies client — refund is on its way
    private function sessionRefundApproved(SessionRefundApproved $e): array {
        $amount = '$' . number_format($e->refundedCents / 100, 2);
        return [[
            'user_id'  => $e->client->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.services.65-session-refund-approved',
            'data'     => [
                'recipient_name' => $e->client->display_name ?? 'there',
                'service_title'  => $e->session->service?->title ?? 'your session',
                'refund_amount'  => $amount,
                'services_url'   => rtrim(config('app.url'), '/') . '/provider/services',
            ],
        ]];
    }

    // ── Session refund denied ─────────────────────────────────────────────────
    // Notifies client — they can escalate
    private function sessionRefundDenied(SessionRefundDenied $e): array {
        return [[
            'user_id'  => $e->client->id,
            'gate_key' => 'notify_email',
            'template' => 'emails.services.66-session-refund-denied',
            'data'     => [
                'recipient_name' => $e->client->display_name ?? 'there',
                'service_title'  => $e->session->service?->title ?? 'your session',
                'provider_note'  => $e->providerNote ?? null,
                'services_url'   => rtrim(config('app.url'), '/') . '/provider/services',
            ],
        ]];
    }

    // ── Session refund escalated ──────────────────────────────────────────────
    // Notifies both parties — dispute is now open
    private function sessionRefundEscalated(SessionRefundEscalated $e): array {
        $amount    = '$' . number_format($e->refundRequest->amount_requested_cents / 100, 2);
        $disputeId = strtoupper(substr($e->dispute->id, 0, 8));
        $replyDays = (int) (env('DISPUTE_RESPONDENT_REPLY_DAYS', 5));
        $url       = rtrim(config('app.url'), '/') . '/provider/services';
        return [
            // Client — confirmation their dispute was filed
            [
                'user_id'  => $e->client->id,
                'gate_key' => 'notify_email',
                'template' => 'emails.services.67-session-refund-escalated',
                'data'     => [
                    'recipient_name' => $e->client->display_name ?? 'there',
                    'service_title'  => $e->session->service?->title ?? 'your session',
                    'dispute_id'     => $disputeId,
                    'amount'         => $amount,
                    'to_provider'    => false,
                    'services_url'   => $url,
                ],
            ],
            // Provider — dispute opened, must respond
            [
                'user_id'  => $e->practitioner->id,
                'gate_key' => 'notify_email',
                'template' => 'emails.services.67-session-refund-escalated',
                'data'     => [
                    'recipient_name' => $e->practitioner->display_name ?? 'there',
                    'client_name'    => $e->client->display_name ?? 'A client',
                    'service_title'  => $e->session->service?->title ?? 'a session',
                    'dispute_id'     => $disputeId,
                    'amount'         => $amount,
                    'to_provider'    => true,
                    'reply_days'     => $replyDays,
                    'services_url'   => $url,
                ],
            ],
        ];
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

    // ── CS Engagement Contract flow (Rev 2 §0.7) ────────────────────────

    private function incidentReadyForClosure(\App\Events\Incident\IncidentReadyForClosure $e): array
    {
        $incident = $e->incident;
        $plan     = \App\Models\ContinuityPlan::find($incident->plan_id);
        if (!$plan) return [];

        $provider = \App\Models\User::find($plan->practitioner_id);
        $ssIds    = \App\Models\PlanSteward::where('plan_id', $incident->plan_id)
            ->where('steward_type', 'support_steward')
            ->where('status', 'active')
            ->pluck('steward_id');

        $recipients = collect([$provider])
            ->merge(\App\Models\User::whereIn('id', $ssIds)->get())
            ->filter();

        return $recipients->map(fn ($u) => [
            'to'       => $u->email,
            'user_id'  => $u->id,
            'gate_key' => 'notify_incident',
            'template' => 'emails.incident.30-ready-for-closure',
            'data'     => [
                'user_name'    => $u->display_name,
                'incident_id'  => $incident->id,
                'is_provider'  => $u->id === $provider?->id,
            ],
        ])->all();
    }

    private function incidentClosureVerified(\App\Events\Incident\IncidentClosureVerified $e): array
    {
        $incident = $e->incident;
        $plan     = \App\Models\ContinuityPlan::find($incident->plan_id);
        if (!$plan) return [];

        // Notify the CS(s) so they know they can now close-with-invoice
        $csIds = \App\Models\PlanSteward::where('plan_id', $incident->plan_id)
            ->where('steward_type', 'continuity_steward')
            ->where('status', 'active')
            ->pluck('steward_id');

        return \App\Models\User::whereIn('id', $csIds)->get()->map(fn ($cs) => [
            'to'       => $cs->email,
            'user_id'  => $cs->id,
            'gate_key' => 'notify_incident',
            'template' => 'emails.incident.31-closure-verified',
            'data'     => [
                'cs_name'       => $cs->display_name,
                'verifier_name' => $e->verifier->display_name ?? 'the system',
                'verifier_role' => $e->verifierRole,
                'incident_id'   => $incident->id,
            ],
        ])->all();
    }

    private function incidentAutoClosed(\App\Events\Incident\IncidentAutoClosed $e): array
    {
        $incident = $e->incident;
        $plan     = \App\Models\ContinuityPlan::find($incident->plan_id);
        if (!$plan) return [];

        $provider = \App\Models\User::find($plan->practitioner_id);

        return array_filter([
            $provider ? [
                'to'       => $provider->email,
                'user_id'  => $provider->id,
                'gate_key' => 'notify_incident',
                'template' => 'emails.incident.32-auto-closed',
                'data'     => [
                    'user_name'    => $provider->display_name,
                    'incident_id'  => $incident->id,
                    'window_days'  => $e->windowDays,
                ],
            ] : null,
        ]);
    }

    private function csInvoiceAutoGenerated(\App\Events\Cs\CsInvoiceAutoGenerated $e): array
    {
        $invoice  = $e->invoice;
        $provider = \App\Models\User::find($invoice->practitioner_id);
        if (!$provider) return [];

        return [[
            'to'       => $provider->email,
            'user_id'  => $provider->id,
            'gate_key' => 'notify_finance',
            'template' => 'emails.cs.60-auto-invoice-generated',
            'data'     => [
                'user_name'      => $provider->display_name,
                'invoice_number' => $invoice->invoice_number,
                'total_dollars'  => number_format($invoice->total_cents / 100, 2),
                'auto_charged'   => $e->autoCharged,
                'incident_id'    => $e->incidentId,
            ],
        ]];
    }

    // ── Dispute system (Rev 2 §0.8) ────────────────────────────────────

    private function disputeOpened(\App\Events\Dispute\DisputeOpened $e): array
    {
        $d          = $e->dispute;
        $respondent = \App\Models\User::find($d->respondent_id);
        if (!$respondent) return [];

        return [[
            'to'       => $respondent->email,
            'user_id'  => $respondent->id,
            'gate_key' => 'notify_dispute',
            'template' => 'emails.disputes.70-opened',
            'data'     => [
                'respondent_name' => $respondent->display_name,
                'dispute_id'      => $d->id,
                'reason'          => $d->reason?->label(),
                'amount_dollars'  => number_format($d->amount_disputed_cents / 100, 2),
                'reply_by_days'   => (int) env('DISPUTE_RESPONDENT_REPLY_DAYS', 5),
            ],
        ]];
    }

    private function disputeReplied(\App\Events\Dispute\DisputeReplied $e): array
    {
        $d = $e->dispute;
        $counterpartyId = $e->message->author_id === $d->disputer_id ? $d->respondent_id : $d->disputer_id;
        $counterparty   = \App\Models\User::find($counterpartyId);
        if (!$counterparty) return [];

        return [[
            'to'       => $counterparty->email,
            'user_id'  => $counterparty->id,
            'gate_key' => 'notify_dispute',
            'template' => 'emails.disputes.71-replied',
            'data'     => [
                'user_name'   => $counterparty->display_name,
                'dispute_id'  => $d->id,
                'author_role' => $e->message->author_role,
            ],
        ]];
    }

    private function disputeResolved(\App\Events\Dispute\DisputeResolved $e): array
    {
        $d          = $e->dispute;
        $disputer   = \App\Models\User::find($d->disputer_id);
        $respondent = \App\Models\User::find($d->respondent_id);

        $mails = [];
        foreach ([$disputer, $respondent] as $u) {
            if (!$u) continue;
            $mails[] = [
                'to'       => $u->email,
                'user_id'  => $u->id,
                'gate_key' => 'notify_dispute',
                'template' => 'emails.disputes.72-resolved',
                'data'     => [
                    'user_name'          => $u->display_name,
                    'dispute_id'         => $d->id,
                    'resolution_label'   => $d->resolution?->label(),
                    'resolution_summary' => $d->resolution_summary,
                ],
            ];
        }
        return $mails;
    }
    // ═══════════════════════════════════════════════════════════════════════
    // WAVE 2 — ESCROW EVENT HANDLERS
    // ═══════════════════════════════════════════════════════════════════════

    private function escrowFunded(EscrowFunded $e): array
    {
        $ms = $e->milestone;
        // Notify BP that funds are now in escrow and work can begin
        return [[
            'user_id'  => $e->contract->bp_id,
            'gate_key' => 'notify_contract',
            'template' => 'emails.business.53-milestone-funded',
            'data'     => [
                'contract_id'  => $e->contract->id,
                'milestone_id' => $ms?->id,
                'amount_cents' => $e->amountCents,
                'provider_id'  => $e->provider->id,
            ],
        ]];
    }

    private function contractFullyFunded(ContractFullyFunded $e): array
    {
        return [[
            'user_id'  => $e->contract->bp_id,
            'gate_key' => 'notify_contract',
            'template' => 'emails.business.52-contract-funded',
            'data'     => [
                'contract_id' => $e->contract->id,
                'provider_id' => $e->provider->id,
            ],
        ]];
    }

    private function milestoneReadyForReview(MilestoneReadyForReview $e): array
    {
        return [[
            'user_id'  => $e->milestone->contract->practitioner_id,
            'gate_key' => 'notify_contract',
            'template' => 'emails.business.54-milestone-submitted',
            'data'     => [
                'milestone_id'   => $e->milestone->id,
                'contract_id'    => $e->milestone->contract_id,
                'submission_id'  => $e->submission->id,
                'bp_id'          => $e->milestone->contract->bp_id,
                'auto_release_at'=> $e->milestone->auto_release_at?->toIso8601String(),
            ],
        ]];
    }

    private function milestoneRevisionRequested(MilestoneRevisionRequested $e): array
    {
        return [[
            'user_id'  => $e->milestone->contract->bp_id,
            'gate_key' => 'notify_contract',
            'template' => 'emails.business.56-milestone-revision-requested',
            'data'     => [
                'milestone_id'   => $e->milestone->id,
                'contract_id'    => $e->milestone->contract_id,
                'revision_notes' => $e->revisionNotes,
                'provider_id'    => $e->provider->id,
                'revision_count' => $e->milestone->revision_count,
            ],
        ]];
    }

    private function milestoneReleased(MilestoneReleased $e): array
    {
        return [[
            'user_id'  => $e->milestone->contract->bp_id,
            'gate_key' => 'notify_contract',
            'template' => 'emails.business.55-milestone-approved',
            'data'     => [
                'milestone_id' => $e->milestone->id,
                'contract_id'  => $e->milestone->contract_id,
                'payout_id'    => $e->payout->id,
                'amount_cents' => $e->payout->amount_cents,
                'approver_id'  => $e->approver->id,
            ],
        ]];
    }

    private function milestoneRefunded(MilestoneRefunded $e): array
    {
        $contract = $e->milestone->contract;
        // Notify provider (got money back) and BP (lost escrow)
        return [
            [
                'user_id'  => $contract->practitioner_id,
                'gate_key' => 'notify_contract',
                'template' => 'emails.business.59-milestone-refunded',
                'data'     => [
                    'milestone_id'   => $e->milestone->id,
                    'contract_id'    => $contract->id,
                    'refunded_cents' => $e->refundedCents,
                    'reason'         => $e->reason,
                    'recipient_role' => 'provider',
                ],
            ],
            [
                'user_id'  => $contract->bp_id,
                'gate_key' => 'notify_contract',
                'template' => 'emails.business.59-milestone-refunded',
                'data'     => [
                    'milestone_id'   => $e->milestone->id,
                    'contract_id'    => $contract->id,
                    'refunded_cents' => $e->refundedCents,
                    'reason'         => $e->reason,
                    'recipient_role' => 'bp',
                ],
            ],
        ];
    }

    private function milestoneAutoReleased(MilestoneAutoReleased $e): array
    {
        $contract = $e->milestone->contract;
        return [
            [
                'user_id'  => $contract->bp_id,
                'gate_key' => 'notify_contract',
                'template' => 'emails.business.57-milestone-auto-released',
                'data'     => [
                    'milestone_id' => $e->milestone->id,
                    'contract_id'  => $contract->id,
                    'payout_id'    => $e->payout->id,
                    'amount_cents' => $e->payout->amount_cents,
                ],
            ],
            [
                'user_id'  => $contract->practitioner_id,
                'gate_key' => 'notify_contract',
                'template' => 'emails.business.57-milestone-auto-released',
                'data'     => [
                    'milestone_id' => $e->milestone->id,
                    'contract_id'  => $contract->id,
                    'amount_cents' => $e->payout->amount_cents,
                    'recipient_role' => 'provider',
                ],
            ],
        ];
    }

}