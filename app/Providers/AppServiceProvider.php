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
        Event::listen(Events\Steward\StewardResigned::class,       Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\ContractCancelled::class,    Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\InvoiceVoided::class,        Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Business\ProposalSubmitted::class,    Listeners\ActivityFanoutListener::class);
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
        Event::listen(Events\Incident\IncidentClosed::class,       Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ProposalAccepted::class,     Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ContractCreated::class,      Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\ContractSigned::class,       Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\InvoiceSent::class,          Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\InvoicePaid::class,          Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Business\PayoutReleased::class,       Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Support\TicketCreated::class,         Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Support\TicketReplied::class,         Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Admin\UserLocked::class,              Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\Auth\EmailVerified::class,            Listeners\SendEmailNotificationListener::class);
        Event::listen(Events\News\NewsPostPublished::class,        Listeners\SendEmailNotificationListener::class);
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
        Event::listen(Events\Network\ConnectionRequestSent::class, Listeners\SendEmailNotificationListener::class);

        // ── BP engagement requests (hire / quote / consultation) ──────────────
        Event::listen(Events\Business\EngagementRequested::class,  Listeners\SendEmailNotificationListener::class);

        // ── Service request events (T58/T59) ──────────────────────────────────
        Event::listen(Events\Service\ServiceRequestSubmitted::class, Listeners\ActivityFanoutListener::class);
        Event::listen(Events\Service\ServiceRequestSubmitted::class, Listeners\SendEmailNotificationListener::class);

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
