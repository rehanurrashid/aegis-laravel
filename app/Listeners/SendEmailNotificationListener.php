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
use App\Events\Plan\DocumentReleaseRequested;
use App\Events\Plan\DocumentUpdated;
use App\Events\Plan\PlanReadyForCs;
use App\Events\Plan\PlanReadyForSs;
use App\Events\Plan\PlanVersionUpdated;
use App\Events\Plan\VaultItemShared;
use App\Events\Network\ConnectionAccepted;
use App\Events\Network\ReferralReceived;
use App\Events\Network\ReferralResponded;
use App\Events\Support\FeedbackReceived;
use App\Events\Support\TicketResolved;
use App\Events\Account\AccountClosed;
use App\Events\Auth\NewDeviceLogin;
use App\Events\Business\MaatAddonChanged;
use App\Events\Business\MilestoneApproved;
use App\Events\Business\MilestoneSubmitted;
use App\Events\Business\ProposalSubmitted;
use App\Events\Incident\IncidentTaskAssigned;
use App\Events\Steward\AlternateCSActivated;
use App\Events\Steward\StewardRoleChangeRequested;
use App\Events\Stripe\PaymentFailed;
use App\Events\Stripe\PaymentReceived;
use App\Events\Stripe\SubscriptionRenewalUpcoming;
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
            $event instanceof MilestoneSubmitted      => $this->milestoneSubmitted($event),
            $event instanceof MilestoneApproved       => $this->milestoneApproved($event),
            $event instanceof DocumentReleaseRequested => $this->documentReleaseRequested($event),
            $event instanceof DocumentUpdated         => $this->documentUpdated($event),
            $event instanceof StewardRoleChangeRequested => $this->stewardRoleChangeRequested($event),
            $event instanceof AlternateCSActivated    => $this->alternateCSActivated($event),
            $event instanceof IncidentTaskAssigned    => $this->incidentTaskAssigned($event),
            $event instanceof PlanReadyForCs          => $this->planReadyForCs($event),
            $event instanceof PlanReadyForSs          => $this->planReadyForSs($event),
            $event instanceof PlanVersionUpdated      => $this->planVersionUpdated($event),
            $event instanceof NewDeviceLogin          => $this->newDeviceLogin($event),
            $event instanceof ProposalSubmitted       => $this->proposalSubmitted($event),
            $event instanceof MaatAddonChanged        => $this->maatAddonChanged($event),
            $event instanceof PaymentFailed           => $this->paymentFailed($event),
            $event instanceof PaymentReceived         => $this->paymentReceived($event),
            $event instanceof SubscriptionRenewalUpcoming => $this->subscriptionRenewalUpcoming($event),
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
        // business/40 → BP (the "you were hired" confirmation, legacy template)
        // bp/33      → BP (the dedicated "your proposal has been accepted" template)
        // Both send to BP; bp/33 is the canonical new template.
        return [
            ['user_id' => $e->proposal->bp_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.business.40-proposal-accepted',
             'data'     => ['proposal_id' => $e->proposal->id, 'contract_id' => $e->contract->id]],
            ['user_id' => $e->proposal->bp_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.bp.33-proposal-accepted',
             'data'     => ['proposal_id' => $e->proposal->id, 'contract_id' => $e->contract->id,
                            'accepted_at' => now()->toFormattedDateString(),
                            'practitioner_name' => \App\Models\User::find($e->contract->practitioner_id)?->display_name]],
        ];
    }

    private function contractCreated(ContractCreated $e): array
    {
        // business/41 → provider (legacy "contract created" template)
        // bp/35       → both parties ("a service agreement is ready" — the dedicated BP-domain template)
        return [
            ['user_id' => $e->contract->practitioner_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.business.41-contract-created',
             'data'     => ['contract_id' => $e->contract->id]],
            ['user_id' => $e->contract->bp_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.bp.35-contract-created',
             'data'     => ['contract_id' => $e->contract->id,
                            'counterparty_name' => \App\Models\User::find($e->contract->practitioner_id)?->display_name,
                            'created_at'        => now()->toFormattedDateString()]],
        ];
    }

    private function contractSigned(ContractSigned $e): array
    {
        // business/42 → legacy "contract fully executed" template
        // gaps/66     → dedicated "your agreement is fully signed" template (both parties)
        $data = fn (string $recipientId) => [
            'contract_id'       => $e->contract->id,
            'contract_title'    => $e->contract->title,
            'counterparty_name' => \App\Models\User::find(
                $recipientId === $e->contract->practitioner_id
                    ? $e->contract->bp_id
                    : $e->contract->practitioner_id
            )?->display_name,
            'signed_at' => now()->toFormattedDateString(),
        ];
        return [
            ['user_id' => $e->contract->practitioner_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.business.42-contract-fully-executed',
             'data'     => ['contract_id' => $e->contract->id]],
            ['user_id' => $e->contract->bp_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.business.42-contract-fully-executed',
             'data'     => ['contract_id' => $e->contract->id]],
            ['user_id' => $e->contract->practitioner_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.gaps.66-contract-signed',
             'data'     => $data($e->contract->practitioner_id)],
            ['user_id' => $e->contract->bp_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.gaps.66-contract-signed',
             'data'     => $data($e->contract->bp_id)],
        ];
    }

    private function invoiceSent(InvoiceSent $e): array
    {
        // business/45 → provider (legacy "invoice sent" template)
        // bp/38       → provider ("you have received an invoice" — dedicated BP-domain template)
        return [
            ['user_id' => $e->invoice->practitioner_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.business.45-invoice-sent',
             'data'     => ['invoice_id' => $e->invoice->id]],
            ['user_id' => $e->invoice->practitioner_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.bp.38-invoice-received',
             'data'     => ['invoice_id'  => $e->invoice->id,
                            'bp_name'     => \App\Models\User::find($e->invoice->bp_id)?->display_name]],
        ];
    }

    private function invoicePaid(InvoicePaid $e): array
    {
        // business/46 → BP (legacy template)
        // bp/39       → BP ("your invoice has been paid" — dedicated BP-domain template)
        return [
            ['user_id' => $e->invoice->bp_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.business.46-invoice-paid',
             'data'     => ['invoice_id' => $e->invoice->id]],
            ['user_id' => $e->invoice->bp_id, 'gate_key' => 'notify_payment',
             'template' => 'emails.bp.39-invoice-paid',
             'data'     => ['invoice_id'        => $e->invoice->id,
                            'practitioner_name' => \App\Models\User::find($e->invoice->practitioner_id)?->display_name,
                            'paid_at'           => now()->toFormattedDateString()]],
        ];
    }

    private function payoutReleased(PayoutReleased $e): array
    {
        $recipientId = $e->payout instanceof BpPayout ? $e->payout->bp_id : $e->payout->cs_id;
        // business/48 → BP (legacy payout template)
        // bp/40       → BP ("payout released" dedicated BP-domain template)
        return [
            ['user_id' => $recipientId, 'gate_key' => 'notify_payment',
             'template' => 'emails.business.48-payout-released',
             'data'     => ['payout_id' => $e->payout->id]],
            ['user_id' => $recipientId, 'gate_key' => 'notify_payment',
             'template' => 'emails.bp.40-payout-released',
             'data'     => ['payout_id'   => $e->payout->id,
                            'released_at' => now()->toFormattedDateString()]],
        ];
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

    private function milestoneSubmitted(MilestoneSubmitted $e): array
    {
        $contract = $e->milestone->contract;
        return [['user_id' => $contract->practitioner_id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.bp.36-milestone-submitted',
                 'data' => ['milestone_id' => $e->milestone->id, 'contract_id' => $contract->id]]];
    }

    private function milestoneApproved(MilestoneApproved $e): array
    {
        $contract = $e->milestone->contract;
        return [['user_id' => $contract->bp_id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.bp.37-milestone-approved',
                 'data' => ['milestone_id' => $e->milestone->id]]];
    }

    private function documentReleaseRequested(DocumentReleaseRequested $e): array
    {
        return [['user_id' => $e->document->practitioner_id, 'gate_key' => 'notify_plan',
                 'template' => 'emails.gaps.61-document-release-requested',
                 'data' => ['document_id' => $e->document->id]]];
    }

    private function documentUpdated(DocumentUpdated $e): array
    {
        $rows = [];
        foreach ($this->stewardRecipients($e->document->plan_id) as $r) {
            $rows[] = ['user_id' => $r['user_id'], 'gate_key' => 'notify_plan',
                       'template' => 'emails.gaps.64-document-updated',
                       'data' => ['document_id' => $e->document->id, 'change_type' => $e->changeType]];
        }
        return $rows;
    }

    private function stewardRoleChangeRequested(StewardRoleChangeRequested $e): array
    {
        $plan = \App\Models\ContinuityPlan::find($e->steward->plan_id);
        if (! $plan) return [];
        return [['user_id' => $plan->practitioner_id, 'gate_key' => 'notify_steward',
                 'template' => 'emails.steward.23-cs-role-change',
                 'data' => ['plan_steward_id' => $e->steward->id,
                            'requested_role'  => $e->requestedRole,
                            'request_note'    => $e->requestNote]]];
    }

    private function alternateCSActivated(AlternateCSActivated $e): array
    {
        $plan = \App\Models\ContinuityPlan::find($e->alternate->plan_id);
        if (! $plan) return [];
        $rows = [
            ['user_id' => $plan->practitioner_id, 'gate_key' => 'notify_steward',
             'template' => 'emails.steward.25-alternate-cs-activated',
             'data' => ['plan_steward_id' => $e->alternate->id, 'incident_id' => $e->incident->id]],
        ];
        // Notify the newly activated alternate CS too.
        $rows[] = ['user_id' => $e->alternate->steward_id, 'gate_key' => 'notify_steward',
                   'template' => 'emails.steward.25-alternate-cs-activated',
                   'data' => ['plan_steward_id' => $e->alternate->id, 'incident_id' => $e->incident->id]];
        return $rows;
    }

    private function incidentTaskAssigned(IncidentTaskAssigned $e): array
    {
        return [['user_id' => $e->assignee->id, 'gate_key' => 'notify_incident',
                 'template' => 'emails.incident.29-incident-task-assigned',
                 'data' => ['incident_task_id' => $e->task->id, 'incident_id' => $e->task->incident_id]]];
    }

    private function planReadyForCs(PlanReadyForCs $e): array
    {
        return [['user_id' => $e->steward->steward_id, 'gate_key' => 'notify_plan',
                 'template' => 'emails.plan.11-plan-ready-cs',
                 'data' => ['plan_id' => $e->plan->id, 'plan_steward_id' => $e->steward->id]]];
    }

    private function planReadyForSs(PlanReadyForSs $e): array
    {
        return [['user_id' => $e->steward->steward_id, 'gate_key' => 'notify_plan',
                 'template' => 'emails.plan.11-plan-ready-ss',
                 'data' => ['plan_id' => $e->plan->id, 'plan_steward_id' => $e->steward->id]]];
    }

    private function planVersionUpdated(PlanVersionUpdated $e): array
    {
        $rows = [];
        foreach ($this->stewardRecipients($e->plan->id) as $r) {
            $rows[] = ['user_id' => $r['user_id'], 'gate_key' => 'notify_plan',
                       'template' => 'emails.plan.16-plan-version-updated',
                       'data' => ['plan_id' => $e->plan->id, 'change_summary' => $e->changeType]];
        }
        return $rows;
    }

    private function newDeviceLogin(NewDeviceLogin $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_account',
                 'template' => 'emails.auth.07-new-device-login',
                 'data' => ['device_label'   => $e->deviceLabel,
                            'location_label' => $e->locationLabel,
                            'login_at'       => $e->loginAt,
                            'support_url'    => rtrim(config('app.url'), '/') . '/support',
                            'ungated'        => true]]];
    }

    private function proposalSubmitted(ProposalSubmitted $e): array
    {
        // BpProposal has no practitioner_id column — resolve through the job relationship.
        $practitionerId = $e->proposal->job?->practitioner_id
            ?? \App\Models\BpJob::find($e->proposal->job_id)?->practitioner_id;
        if (! $practitionerId) return [];
        return [['user_id' => $practitionerId, 'gate_key' => 'notify_payment',
                 'template' => 'emails.bp.32-support-request-received',
                 'data'     => ['proposal_id' => $e->proposal->id]]];
    }

    private function maatAddonChanged(MaatAddonChanged $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.gaps.69-maat-addon-change',
                 'data' => ['addon_state'  => $e->addonState,
                            'billing_url'  => rtrim(config('app.url'), '/') . '/settings/billing']]];
    }

    private function paymentFailed(PaymentFailed $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.admin.53-payment-failed',
                 'data' => ['amount'         => '$' . number_format($e->amountCents / 100, 2),
                            'failure_reason' => $e->failureReason,
                            'retry_date'     => $e->retryDate ?? 'N/A',
                            'billing_url'    => rtrim(config('app.url'), '/') . '/settings/billing',
                            'ungated'        => true]]];
    }

    private function paymentReceived(PaymentReceived $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.admin.54-payment-receipt',
                 'data' => ['amount'       => '$' . number_format($e->amountCents / 100, 2),
                            'paid_at'      => now()->toFormattedDateString(),
                            'payment_ref'  => $e->paymentRef,
                            'period_label' => $e->periodLabel,
                            'plan_label'   => $e->planLabel,
                            'receipt_url'  => rtrim(config('app.url'), '/') . '/settings/billing']]];
    }

    private function subscriptionRenewalUpcoming(SubscriptionRenewalUpcoming $e): array
    {
        return [['user_id' => $e->user->id, 'gate_key' => 'notify_payment',
                 'template' => 'emails.admin.55-renewal-upcoming',
                 'data' => ['amount'       => '$' . number_format($e->amountCents / 100, 2),
                            'renewal_date' => $e->renewalDate,
                            'plan_label'   => $e->planLabel,
                            'billing_url'  => rtrim(config('app.url'), '/') . '/settings/billing']]];
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
