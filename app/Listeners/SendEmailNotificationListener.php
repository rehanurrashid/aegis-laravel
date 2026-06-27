<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Admin\UserLocked;
use App\Events\Auth\PasswordReset;
use App\Events\Auth\UserRegistered;
use App\Events\Business\ContractCreated;
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
use App\Events\Auth\MfaEnabled;
use App\Events\Auth\MfaDisabled;
use App\Events\Incident\IncidentEscalated;
use App\Events\Admin\UserRoleChanged;
use App\Events\Business\ContractCancelled;
use App\Events\Business\ProposalDeclined;
use App\Events\Business\SubscriptionTierChanged;
use App\Events\Business\SubscriptionCancelled;
use App\Events\Service\ServiceRequestSubmitted;
use App\Events\Service\ServiceRequestResponded;
use App\Events\Plan\DocumentRequested;
use App\Events\Plan\VaultItemShared;
use App\Events\Network\ConnectionAccepted;
use App\Events\Network\ReferralReceived;
use App\Events\Network\ReferralResponded;
use App\Events\Support\FeedbackReceived;
use App\Events\Support\TicketResolved;
use App\Events\Account\AccountClosed;
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
            $event instanceof MfaEnabled              => $this->mfaEnabled($event),
            $event instanceof MfaDisabled             => $this->mfaDisabled($event),
            $event instanceof IncidentEscalated       => $this->incidentEscalated($event),
            $event instanceof UserRoleChanged         => $this->userRoleChanged($event),
            $event instanceof ContractCancelled       => $this->contractCancelled($event),
            $event instanceof ProposalDeclined        => $this->proposalDeclined($event),
            $event instanceof SubscriptionTierChanged => $this->subscriptionTierChanged($event),
            $event instanceof SubscriptionCancelled   => $this->subscriptionCancelled($event),
            $event instanceof ServiceRequestSubmitted => $this->serviceRequestSubmitted($event),
            $event instanceof ServiceRequestResponded => $this->serviceRequestResponded($event),
            $event instanceof DocumentRequested       => $this->documentRequested($event),
            $event instanceof VaultItemShared         => $this->vaultItemShared($event),
            $event instanceof ConnectionAccepted      => $this->connectionAccepted($event),
            $event instanceof ReferralReceived        => $this->referralReceived($event),
            $event instanceof ReferralResponded       => $this->referralResponded($event),
            $event instanceof FeedbackReceived        => $this->feedbackReceived($event),
            $event instanceof TicketResolved          => $this->ticketResolved($event),
            $event instanceof AccountClosed           => $this->accountClosed($event),
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
                'template' => $r['portal'] === 'support_steward'
                    ? 'emails.plan.13-vault-attested-ss'
                    : 'emails.vault.12-vault-attested',
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
        $isSs = $e->steward->steward_type === 'support_steward';
        return [['user_id' => $e->steward->steward_id, 'gate_key' => 'notify_steward',
                 'template' => $isSs
                     ? 'emails.steward.20-ss-invite-internal'
                     : 'emails.steward.07-steward-invitation',
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

    private function mfaEnabled(MfaEnabled $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_account',
                 'template' => 'emails.auth.05-mfa-enabled',
                 'data' => ['user_id' => $e->user->id]]];
    }

    private function mfaDisabled(MfaDisabled $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_account',
                 'template' => 'emails.auth.06-mfa-disabled',
                 'data' => ['user_id' => $e->user->id]]];
    }

    private function incidentEscalated(IncidentEscalated $e): array
    {
        $rows = [['user_id' => $e->incident->practitioner_id, 'gate_key' => 'notify_incident',
                  'template' => 'emails.incident.30-incident-escalated',
                  'data' => ['incident_id' => $e->incident->id, 'reason' => $e->reason]]];
        foreach ($this->stewardRecipients($e->incident->plan_id) as $r) {
            $rows[] = ['user_id' => $r['user_id'], 'gate_key' => 'notify_incident',
                       'template' => 'emails.incident.30-incident-escalated',
                       'data' => ['incident_id' => $e->incident->id, 'reason' => $e->reason]];
        }
        return $rows;
    }

    private function userRoleChanged(UserRoleChanged $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_account',
                 'template' => 'emails.admin.50-account-action',
                 'data' => ['user_id' => $e->user->id, 'before' => $e->before, 'after' => $e->after]]];
    }

    private function contractCancelled(ContractCancelled $e): array
    {
        return [
            ['user_id' => $e->contract->practitioner_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.gaps.67-contract-cancelled',
             'data' => ['contract_id' => $e->contract->id, 'reason' => $e->reason]],
            ['user_id' => $e->contract->bp_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.gaps.67-contract-cancelled',
             'data' => ['contract_id' => $e->contract->id, 'reason' => $e->reason]],
        ];
    }

    private function proposalDeclined(ProposalDeclined $e): array
    {
        return [['user_id' => $e->proposal->bp_id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.bp.34-proposal-declined',
                 'data' => ['proposal_id' => $e->proposal->id, 'reason' => $e->reason]]];
    }

    private function subscriptionTierChanged(SubscriptionTierChanged $e): array
    {
        $template = $e->direction === 'downgrade'
            ? 'emails.admin.52-plan-downgraded'
            : 'emails.admin.51-plan-upgraded';
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_payment',
                 'template' => $template,
                 'data' => ['user_id' => $e->user->id, 'tier' => $e->tier]]];
    }

    private function subscriptionCancelled(SubscriptionCancelled $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.gaps.68-subscription-cancelled',
                 'data' => ['user_id' => $e->user->id]]];
    }

    private function serviceRequestSubmitted(ServiceRequestSubmitted $e): array
    {
        return [['user_id' => $e->request->practitioner_id, 'gate_key' => 'notify_referral',
                 'template' => 'emails.gaps.58-service-inquiry-received',
                 'data' => ['service_request_id' => $e->request->id]]];
    }

    private function serviceRequestResponded(ServiceRequestResponded $e): array
    {
        return [['user_id' => $e->request->inquirer_id, 'gate_key' => 'notify_referral',
                 'template' => 'emails.gaps.59-service-inquiry-responded',
                 'data' => ['service_request_id' => $e->request->id, 'outcome' => $e->outcome]]];
    }

    private function documentRequested(DocumentRequested $e): array
    {
        $rows = [];
        foreach ($this->stewardRecipients($e->plan->id) as $r) {
            $rows[] = ['user_id' => $r['user_id'], 'gate_key' => 'notify_plan',
                       'template' => 'emails.gaps.60-document-requested',
                       'data' => ['document_id' => $e->document->id, 'plan_id' => $e->plan->id]];
        }
        return $rows;
    }

    private function vaultItemShared(VaultItemShared $e): array
    {
        $rows = [];
        foreach ($e->stewardIds as $sid) {
            $rows[] = ['user_id' => $sid, 'gate_key' => 'notify_vault',
                       'template' => 'emails.gaps.63-vault-item-shared',
                       'data' => ['vault_item_id' => $e->item->id]];
        }
        return $rows;
    }

    private function connectionAccepted(ConnectionAccepted $e): array
    {
        return [['user_id' => $e->connection->user_a_id, 'gate_key' => 'notify_account',
                 'template' => 'emails.network.43-connection-accepted',
                 'data' => ['connection_id' => $e->connection->id]]];
    }

    private function referralReceived(ReferralReceived $e): array
    {
        return [['user_id' => $e->recipient->id, 'gate_key' => 'notify_referral',
                 'template' => 'emails.network.44-referral-received',
                 'data' => ['referral_id' => $e->referral->id]]];
    }

    private function referralResponded(ReferralResponded $e): array
    {
        return [['user_id' => $e->referral->sender_id, 'gate_key' => 'notify_referral',
                 'template' => 'emails.network.45-referral-responded',
                 'data' => ['referral_id' => $e->referral->id, 'outcome' => $e->outcome]]];
    }

    private function feedbackReceived(FeedbackReceived $e): array
    {
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

    private function accountClosed(AccountClosed $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_account',
                 'template' => 'emails.auth.09-account-closure',
                 'data' => ['user_id' => $e->user->id, 'reason' => $e->reason]]];
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
}
