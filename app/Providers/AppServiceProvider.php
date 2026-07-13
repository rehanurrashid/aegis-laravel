<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events;
use App\Listeners;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Wires every domain event to its activity-fanout listener, gated-email listener,
     * and (for incidents) the ungated incident-alert listener. Stripe webhooks are routed
     * to StripeEventListener via Cashier's WebhookReceived event.
     */
    public function boot(): void
    {
        // ── Activity fan-out (safety net for events Services don't write inline) ─
        Event::listen(Events\Auth\UserRegistered::class,           Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Plan\AnnualReviewDue::class,          Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\ContractSigned::class,       Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\InvoiceSent::class,          Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Steward\AlternateCSActivated::class,  Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Admin\UserRoleChanged::class,         Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Admin\RefundProcessed::class,         Listeners\ActivityFanoutListener::class);
        Event::listen(Events\News\NewsPostPublished::class,        Listeners\ActivityFanoutListener::class);
        Event::listen(Events\News\NewsCommented::class,            Listeners\ActivityFanoutListener::class);
        Event::listen(Events\News\EventRsvpReceived::class,        Listeners\ActivityFanoutListener::class);
        Event::listen(Events\News\EventSubmitted::class,           Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Steward\StewardResigned::class,       Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\ContractCancelled::class,    Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\InvoiceVoided::class,        Listeners\ActivityFanoutListener::class);
        // NOTE: ProposalSubmitted removed from ActivityFanoutListener — was double-firing.
        // ProposalService::submit() writes ActivityService::log() directly for both parties.
        Event::listen(Events\Business\ProposalWithdrawn::class,    Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Incident\IncidentReopened::class,     Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Incident\IncidentWithdrawn::class,    Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Admin\HelpArticlePublished::class,    Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Admin\PayoutReleasedManually::class,  Listeners\ActivityFanoutListener::class);

        // ── Gated email notifications (respects notify_* user prefs) ─────────────
        Event::listen(Events\Auth\UserRegistered::class,           Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Auth\PasswordReset::class,            Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Plan\PlanSigned::class,               Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Plan\VaultAttested::class,            Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Plan\AnnualReviewDue::class,          Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Plan\AnnualReviewCompleted::class,    Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Steward\StewardDesignated::class,     Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Steward\StewardAccepted::class,       Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Steward\StewardDeclined::class,       Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Steward\StewardRemoved::class,        Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Steward\SsSuspended::class,           Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Steward\SsReinstated::class,          Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Incident\IncidentClosed::class,       Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ProposalAccepted::class,     Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ProposalDeclined::class,     Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ContractCreated::class,      Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ContractSigned::class,       Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\InvoiceSent::class,          Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\InvoicePaid::class,          Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\PayoutReleased::class,       Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Support\TicketCreated::class,         Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Support\TicketReplied::class,         Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Support\FeedbackReceived::class,      Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Support\TicketResolved::class,        Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Admin\UserLocked::class,              Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Auth\EmailVerified::class,            Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\News\NewsPostPublished::class,        Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\News\EventRsvpReceived::class,        Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\News\NewsCommented::class,            Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\News\EventSubmitted::class,           Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Steward\StewardResigned::class,       Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ContractCancelled::class,    Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\InvoiceVoided::class,        Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Admin\HelpArticlePublished::class,    Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Admin\PayoutReleasedManually::class,  Listeners\SendEmailNotificationListener::class);

        // ── Referral events (gated by notify_referral_received / notify_referral_sent) ──
        Event::listen(Events\Referral\ReferralSent::class,       Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Referral\ReferralAccepted::class,   Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Referral\ReferralDeclined::class,   Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Referral\ReferralClosed::class,     Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Referral\ReferralCancelled::class,  Listeners\SendEmailNotificationListener::class);

        // ── Network connection events ─────────────────────────────────────────
        Event::listen(Events\Network\ConnectionAccepted::class,    Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Network\ConnectionAccepted::class,    Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Network\ConnectionRequestSent::class, Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Network\ConnectionRequestSent::class, Listeners\SendEmailNotificationListener::class);

        // Auth security events
        Event::listen(Events\Auth\MfaEnabled::class,            Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Auth\MfaDisabled::class,           Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Auth\NewDeviceLogin::class,        Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Auth\AccountClosed::class,         Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Auth\UserLoggedIn::class,          Listeners\ActivityFanoutListener::class);

        // Plan events
        Event::listen(Events\Plan\PlanReadyForCs::class,        Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Plan\PlanReadyForSs::class,        Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Plan\PlanVersionUpdated::class,    Listeners\SendEmailNotificationListener::class);

        // Vault events
        Event::listen(Events\Plan\VaultItemShared::class,       Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Plan\VaultUnsealed::class,         Listeners\SendEmailNotificationListener::class);

        // Subscription / account events
        Event::listen(Events\Business\SubscriptionCancelled::class,   Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\SubscriptionTierChanged::class,  Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\MaatAddonChanged::class,         Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Account\SubscriptionRenewalUpcoming::class, Listeners\SendEmailNotificationListener::class);

        // CS Engagement Contract flow (Rev 2 §0.7)
        Event::listen(Events\Incident\IncidentReadyForClosure::class,   Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Incident\IncidentReadyForClosure::class,   Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Incident\IncidentClosureVerified::class,   Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Incident\IncidentClosureVerified::class,   Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Incident\IncidentAutoClosed::class,        Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Incident\IncidentAutoClosed::class,        Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Cs\CsInvoiceAutoGenerated::class,          Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Cs\CsInvoiceAutoGenerated::class,          Listeners\ActivityFanoutListener::class);

        // Dispute system (Rev 2 §0.8)
        Event::listen(Events\Dispute\DisputeOpened::class,              Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Dispute\DisputeOpened::class,              Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Dispute\DisputeReplied::class,             Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Dispute\DisputeReplied::class,             Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Dispute\DisputeResolved::class,            Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Dispute\DisputeResolved::class,            Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Stripe\PaymentFailed::class,              Listeners\SendEmailNotificationListener::class);

        // Document events
        Event::listen(Events\Document\DocumentRequested::class,           Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Document\DocumentReleaseRequested::class,    Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Document\DocumentUpdated::class,             Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Document\DocumentSigned::class,              Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Document\DocumentArchived::class,            Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Document\DocumentAmendmentRequested::class,  Listeners\SendEmailNotificationListener::class);

        // Messaging
        Event::listen(Events\Messages\MessageSent::class,       Listeners\SendEmailNotificationListener::class);

        // Contract milestones
        Event::listen(Events\Business\MilestoneSubmitted::class,  Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\MilestoneApproved::class,   Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ProposalSubmitted::class,   Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ProposalWithdrawn::class,   Listeners\SendEmailNotificationListener::class);

        // ── Wave 2: Escrow events ─────────────────────────────────────────────
        Event::listen(Events\Business\EscrowFunded::class,               Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\EscrowFunded::class,               Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\ContractFullyFunded::class,        Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ContractFullyFunded::class,        Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\ContractCompleted::class,          Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ContractCompleted::class,          Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\ContractReviewSubmitted::class,    Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ContractReviewSubmitted::class,    Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\MilestoneReadyForReview::class,    Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\MilestoneRevisionRequested::class, Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\MilestoneRevisionRequested::class, Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\MilestoneReleased::class,          Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\MilestoneReleased::class,          Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\MilestoneRefunded::class,          Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\MilestoneRefunded::class,          Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\MilestoneAutoReleased::class,      Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\MilestoneAutoReleased::class,      Listeners\ActivityFanoutListener::class);

        // Service request response
        Event::listen(Events\Service\ServiceRequestResponded::class, Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Service\SessionCancelled::class,        Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Service\SessionCompleted::class,        Listeners\SendEmailNotificationListener::class);

        // Steward role change
        Event::listen(Events\Steward\StewardRoleChangeRequested::class, Listeners\SendEmailNotificationListener::class);

        // Incident escalation (through SendIncidentAlertsListener — ungated)
        Event::listen(Events\Incident\IncidentEscalated::class,  Listeners\SendIncidentAlertsListener::class);

        // ── BP engagement requests (hire / quote / consultation) ──────────────
        Event::listen(Events\Business\EngagementRequested::class,  Listeners\SendEmailNotificationListener::class);

        // ── Service request events (T58/T59) ──────────────────────────────────
        Event::listen(Events\Service\ServiceRequestSubmitted::class, Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Service\ServiceRequestSubmitted::class, Listeners\SendEmailNotificationListener::class);

        // ── Wave 2: Session deposit / balance / refund events ─────────────────
        Event::listen(Events\Service\SessionDepositPaid::class,      Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Service\SessionBalancePaid::class,      Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Service\SessionRefundRequested::class,  Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Service\SessionRefundApproved::class,   Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Service\SessionRefundDenied::class,     Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Service\SessionRefundEscalated::class,  Listeners\SendEmailNotificationListener::class);

        // ── UNGATED incident alerts (bypass notify_* prefs entirely) ─────────────
        Event::listen(Events\Incident\IncidentReported::class,     Listeners\SendIncidentAlertsListener::class);
        Event::listen(Events\Incident\IncidentVerified::class,     Listeners\SendIncidentAlertsListener::class);
        Event::listen(Events\Incident\IncidentActivated::class,    Listeners\SendIncidentAlertsListener::class);
        Event::listen(Events\Incident\IncidentReopened::class,     Listeners\SendIncidentAlertsListener::class);
        Event::listen(Events\Incident\IncidentWithdrawn::class,    Listeners\SendIncidentAlertsListener::class);

        // ── Stripe webhooks (Cashier) ────────────────────────────────────────────
        Event::listen(WebhookReceived::class,                      Listeners\StripeEventListener::class);
    }
}
