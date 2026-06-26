<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ActivitySeverity;
use App\Enums\UserRole;
use App\Events\Admin\RefundProcessed;
use App\Events\Admin\UserLocked;
use App\Events\Admin\UserRoleChanged;
use App\Events\Auth\UserRegistered;
use App\Events\Business\ContractSigned;
use App\Events\Business\InvoicePaid;
use App\Events\Business\InvoiceSent;
use App\Events\Business\PayoutReleased;
use App\Events\Business\ProposalAccepted;
use App\Events\Incident\IncidentActivated;
use App\Events\Incident\IncidentClosed;
use App\Events\Incident\IncidentEscalated;
use App\Events\Incident\IncidentReported;
use App\Events\Incident\IncidentVerified;
use App\Events\Plan\AnnualReviewCompleted;
use App\Events\Plan\AnnualReviewDue;
use App\Events\Plan\PlanSigned;
use App\Events\Plan\VaultAttested;
use App\Events\Steward\AlternateCSActivated;
use App\Events\Steward\StewardAccepted;
use App\Events\Steward\StewardDeclined;
use App\Events\Steward\StewardDesignated;
use App\Events\Steward\StewardRemoved;
use App\Events\Support\TicketCreated;
use App\Events\Support\TicketReplied;
use App\Models\BpContract;
use App\Models\User;
use App\Services\ActivityService;

/**
 * Default-route listener. Most Services already write their own activity events
 * inline (more contextual). This listener picks up edge-case events that the
 * Services don't handle directly — and acts as a safety net.
 */
class ActivityFanoutListener
{
    public function __construct(private ActivityService $activity) {}

    public function handle(object $event): void
    {
        $fanouts = $this->resolveFanout($event);
        foreach ($fanouts as $f) {
            $this->activity->log(...$f);
        }
    }

    /**
     * @return array<int, array> arguments to pass to ActivityService::log()
     */
    private function resolveFanout(object $event): array
    {
        return match (true) {
            $event instanceof UserRegistered             => $this->userRegistered($event),
            $event instanceof AnnualReviewDue            => $this->annualReviewDue($event),
            $event instanceof ContractSigned             => $this->contractSigned($event),
            $event instanceof InvoiceSent                => $this->invoiceSent($event),
            $event instanceof AlternateCSActivated       => $this->alternateActivated($event),
            $event instanceof UserRoleChanged            => $this->roleChanged($event),
            $event instanceof RefundProcessed            => $this->refundProcessed($event),
            // Other events (PlanSigned, VaultAttested, IncidentActivated, ProposalAccepted, TicketReplied, …)
            // are already handled inline by their respective Services. We return [] to avoid duplicates.
            default => [],
        };
    }

    private function userRegistered(UserRegistered $event): array
    {
        return [[
            $event->user->id,
            $this->portalFor($event->user->role),
            'account',
            ActivitySeverity::Info,
            'welcome',
            'Welcome to Aegis',
            'Take a moment to complete your profile and add your first steward.',
            'user',
            $event->user->id,
            null,
        ]];
    }

    private function annualReviewDue(AnnualReviewDue $event): array
    {
        return [[
            $event->plan->practitioner_id,
            'provider',
            'plan',
            ActivitySeverity::Warning,
            'annual_review_due',
            'Annual review due in ' . $event->daysUntilDue . ' days',
            'Complete your annual review to keep your Continuity Plan active.',
            'continuity_plan',
            $event->plan->id,
            null,
        ]];
    }

    private function contractSigned(ContractSigned $event): array
    {
        // Both parties already notified inline in ContractService::sign().
        // This is a safety net only when both signed simultaneously.
        return [];
    }

    private function invoiceSent(InvoiceSent $event): array
    {
        // Already handled inline in InvoiceService::send()
        return [];
    }

    private function alternateActivated(AlternateCSActivated $event): array
    {
        return [[
            $event->alternate->steward_id,
            'continuity_steward',
            'incident',
            ActivitySeverity::Critical,
            'alternate_cs_activated',
            'You have been activated as alternate Continuity Steward',
            'The primary CS could not be reached. Please review the incident immediately.',
            'critical_incident',
            $event->incident->id,
            null,
        ]];
    }

    private function roleChanged(UserRoleChanged $event): array
    {
        return [[
            $event->user->id,
            $this->portalFor($event->after),
            'account',
            ActivitySeverity::Warning,
            'role_changed',
            'Your account role was changed',
            "From {$event->before} to {$event->after}.",
            'user',
            $event->user->id,
            null,
        ]];
    }

    private function refundProcessed(RefundProcessed $event): array
    {
        // No user_id on event — would require ledger lookup. Hook left for future expansion.
        return [];
    }

    private function portalFor(UserRole|string $role): string
    {
        $value = $role instanceof UserRole ? $role->value : $role;

        return match ($value) {
            'practitioner'       => 'provider',
            'continuity_steward' => 'continuity_steward',
            'support_steward'    => 'support_steward',
            'business_partner'   => 'business_partner',
            'admin'              => 'admin',
            default              => 'provider',
        };
    }
}
