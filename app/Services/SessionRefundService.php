<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Enums\DisputeReason;
use App\Enums\ServiceSessionPaymentStatus;
use App\Enums\SessionRefundRequestStatus;
use App\Enums\SessionRefundType;
use App\Events\Service\SessionRefundApproved;
use App\Events\Service\SessionRefundDenied;
use App\Events\Service\SessionRefundEscalated;
use App\Events\Service\SessionRefundRequested;
use App\Models\Dispute;
use App\Models\ServiceSession;
use App\Models\SessionRefundRequest;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Handles the client-initiated session refund request lifecycle:
 *
 *   open()       → client submits refund request → status: pending_review
 *   approve()    → provider approves → Stripe refund issued → status: approved
 *   deny()       → provider denies  → status: denied (client may escalate)
 *   escalate()   → client escalates denied request to formal Dispute → status: escalated_to_dispute
 *   autoApprove()→ scheduled job auto-approves overdue requests (future use)
 *
 * Refunds always use reverse_transfer: true via PayoutService::refundSessionCharge()
 * so funds pull from the provider's Connect account, never held by Aegis.
 */
class SessionRefundService
{
    public function __construct(
        private ActivityService $activity,
        private PayoutService   $payouts,
        private DisputeService  $disputes,
    ) {}

    // ═══════════════════════════════════════════════════════════════════════════
    // OPEN — client initiates refund request
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * @throws \RuntimeException if session is not in a refundable state or client already has a pending request
     */
    public function open(ServiceSession $session, User $client, array $data): SessionRefundRequest
    {
        // ── Guards ────────────────────────────────────────────────────────────
        if ($session->client_id !== $client->id) {
            abort(403, 'Only the client who booked this session can request a refund.');
        }

        $paymentStatus = $session->payment_status instanceof ServiceSessionPaymentStatus
            ? $session->payment_status
            : ServiceSessionPaymentStatus::from((string) $session->payment_status);

        if (!$paymentStatus->depositCharged()) {
            throw new \RuntimeException('No payment has been made for this session — nothing to refund.');
        }

        if ($session->has_pending_refund_request) {
            throw new \RuntimeException('A refund request is already pending for this session.');
        }

        // ── Validate refund type is available for current payment status ──────
        $refundType = SessionRefundType::from($data['refund_type']);
        $available  = SessionRefundType::availableFor($paymentStatus);

        if (!in_array($refundType, $available, true)) {
            throw new \RuntimeException(
                'The selected refund type is not available for this session\'s payment status.'
            );
        }

        // ── Calculate requested amount based on type ──────────────────────────
        $amountRequestedCents = $this->calculateRefundAmount($session, $refundType);

        // ── Provider reply deadline (DISPUTE_RESPONDENT_REPLY_DAYS env) ───────
        $replyDays    = (int) (env('DISPUTE_RESPONDENT_REPLY_DAYS', 5));
        $deadlineAt   = now()->addDays($replyDays);

        // ── Create refund request ─────────────────────────────────────────────
        $provider = User::findOrFail($session->practitioner_id);
        $refund   = SessionRefundRequest::create([
            'id'                     => 'srr_' . Str::lower(Str::random(12)),
            'session_id'             => $session->id,
            'requested_by_id'        => $client->id,
            'provider_id'            => $session->practitioner_id,
            'reason'                 => $data['reason'],
            'reason_detail'          => $data['reason_detail'] ?? null,
            'refund_type'            => $refundType->value,
            'amount_requested_cents' => $amountRequestedCents,
            'status'                 => SessionRefundRequestStatus::PendingReview->value,
            'provider_deadline_at'   => $deadlineAt,
        ]);

        // ── Activity log ──────────────────────────────────────────────────────
        $svcTitle = $session->service?->title ?? 'session';
        $amount   = '$' . number_format($amountRequestedCents / 100, 2);

        // Actor log for client
        $this->activity->log(
            $client->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_refund_requested',
            'Refund request submitted — ' . $svcTitle,
            $amount . ' refund requested. Provider has ' . $replyDays . ' days to respond.',
            'service_session', $session->id, $provider->id,
            'log', $client->id
        );

        // Notification for provider
        $this->activity->log(
            $provider->id, 'provider', 'payment', ActivitySeverity::Warning,
            'session_refund_request_received',
            $client->display_name . ' requested a refund for: ' . $svcTitle,
            $amount . ' — ' . $refundType->label() . '. Please review and respond within ' . $replyDays . ' days.',
            'service_session', $session->id, $client->id,
            'notification', $client->id
        );

        event(new SessionRefundRequested($refund->fresh(), $session, $client, $provider));

        return $refund->fresh();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // APPROVE — provider approves the refund
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * @throws \RuntimeException if not actionable or Stripe refund fails
     */
    public function approve(SessionRefundRequest $refundRequest, User $provider): SessionRefundRequest
    {
        if ($refundRequest->provider_id !== $provider->id) {
            abort(403, 'Only the session provider can approve this refund.');
        }

        if (!$refundRequest->status->isActionable()) {
            throw new \RuntimeException('This refund request can no longer be approved.');
        }

        $session    = $refundRequest->session;
        $client     = User::findOrFail($refundRequest->requested_by_id);
        $refundType = $refundRequest->refund_type instanceof SessionRefundType
            ? $refundRequest->refund_type
            : SessionRefundType::from((string) $refundRequest->refund_type);

        $totalRefunded = 0;
        $refundId      = null;
        $refundIdBal   = null;

        // ── Issue Stripe refund(s) ────────────────────────────────────────────
        // Each charge (deposit + balance) must be refunded against its own PaymentIntent
        try {
            if (in_array($refundType, [SessionRefundType::DepositOnly, SessionRefundType::Full], true)) {
                if ($session->deposit_charge_id) {
                    $refundId = $this->payouts->refundSessionCharge(
                        $session->deposit_charge_id,
                        $session->deposit_cents ?? 0,
                        ['refund_request_id' => $refundRequest->id, 'type' => 'deposit']
                    );
                    $totalRefunded += $session->deposit_cents ?? 0;
                }
            }

            if (in_array($refundType, [SessionRefundType::BalanceOnly, SessionRefundType::Full], true)) {
                if ($session->balance_charge_id && ($session->balance_cents ?? 0) > 0) {
                    $refundIdBal = $this->payouts->refundSessionCharge(
                        $session->balance_charge_id,
                        $session->balance_cents ?? 0,
                        ['refund_request_id' => $refundRequest->id, 'type' => 'balance']
                    );
                    $totalRefunded += $session->balance_cents ?? 0;
                }
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException('Refund could not be processed: ' . $e->getMessage());
        }

        // ── Update refund request ─────────────────────────────────────────────
        $refundRequest->update([
            'status'                 => SessionRefundRequestStatus::Approved->value,
            'provider_response'      => 'Approved.',
            'responded_at'           => now(),
            'stripe_refund_id'       => $refundId,
            'stripe_refund_id_balance' => $refundIdBal,
            'refunded_cents'         => $totalRefunded,
        ]);

        // ── Update session payment_status ─────────────────────────────────────
        $newPaymentStatus = $this->resolvePaymentStatusAfterRefund($session, $refundType, $totalRefunded);
        $session->update([
            'payment_status'      => $newPaymentStatus->value,
            'total_refunded_cents'=> ($session->total_refunded_cents ?? 0) + $totalRefunded,
        ]);

        // ── Activity log ──────────────────────────────────────────────────────
        $amount   = '$' . number_format($totalRefunded / 100, 2);
        $svcTitle = $session->service?->title ?? 'session';

        $this->activity->log(
            $client->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_refund_approved',
            'Refund approved — ' . $svcTitle,
            $amount . ' has been refunded to your original payment method. Allow 5-10 business days.',
            'service_session', $session->id, $provider->id,
            'notification', $provider->id
        );

        $this->activity->log(
            $provider->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_refund_issued',
            'You approved a refund — ' . $svcTitle,
            $amount . ' refunded to ' . $client->display_name . '. Funds deducted from your Stripe Connect account.',
            'service_session', $session->id, $client->id,
            'log', $provider->id
        );

        event(new SessionRefundApproved($refundRequest->fresh(), $session->fresh(), $client, $provider, $totalRefunded));

        return $refundRequest->fresh();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DENY — provider denies the refund
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * @throws \RuntimeException if not actionable
     */
    public function deny(SessionRefundRequest $refundRequest, User $provider, string $note): SessionRefundRequest
    {
        if ($refundRequest->provider_id !== $provider->id) {
            abort(403, 'Only the session provider can deny this refund.');
        }

        if (!$refundRequest->status->isActionable()) {
            throw new \RuntimeException('This refund request can no longer be denied.');
        }

        $refundRequest->update([
            'status'            => SessionRefundRequestStatus::Denied->value,
            'provider_response' => $note,
            'responded_at'      => now(),
        ]);

        $session  = $refundRequest->session;
        $client   = User::findOrFail($refundRequest->requested_by_id);
        $svcTitle = $session->service?->title ?? 'session';
        $amount   = '$' . number_format($refundRequest->amount_requested_cents / 100, 2);

        $this->activity->log(
            $client->id, 'provider', 'payment', ActivitySeverity::Warning,
            'session_refund_denied',
            'Refund request denied — ' . $svcTitle,
            $amount . ' refund was denied. Reason: ' . $note . '. You may escalate to a formal dispute.',
            'service_session', $session->id, $provider->id,
            'notification', $provider->id
        );

        $this->activity->log(
            $provider->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_refund_denied_by_you',
            'You denied a refund request — ' . $svcTitle,
            $amount . ' refund denied for ' . $client->display_name . '.',
            'service_session', $session->id, $client->id,
            'log', $provider->id
        );

        event(new SessionRefundDenied($refundRequest->fresh(), $session, $client, $provider, $note));

        return $refundRequest->fresh();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ESCALATE — client escalates denied request to formal dispute
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * @throws \RuntimeException if escalation is not allowed
     */
    public function escalate(SessionRefundRequest $refundRequest, User $client): Dispute
    {
        if ($refundRequest->requested_by_id !== $client->id) {
            abort(403, 'Only the client who submitted this request can escalate it.');
        }

        if (!$refundRequest->status->canEscalate()) {
            throw new \RuntimeException('Only denied refund requests can be escalated to a dispute.');
        }

        $session  = $refundRequest->session;
        $provider = User::findOrFail($refundRequest->provider_id);

        // ── Open a formal dispute via the existing DisputeService ─────────────
        $dispute = $this->disputes->open(
            subjectType:    'session',
            subjectId:      $session->id,
            claimantId:     $client->id,
            respondentId:   $provider->id,
            reason:         DisputeReason::ServiceNotDelivered, // closest match; admin can reassign
            description:    'Escalated from refund request ' . $refundRequest->id . '. '
                            . 'Original reason: ' . $refundRequest->reason . '. '
                            . 'Amount: $' . number_format($refundRequest->amount_requested_cents / 100, 2) . '. '
                            . 'Provider response: ' . ($refundRequest->provider_response ?? 'None given.'),
            claimantPortal: 'provider',
        );

        // ── Link dispute back to the refund request ───────────────────────────
        $refundRequest->update([
            'status'              => SessionRefundRequestStatus::EscalatedToDispute->value,
            'escalated_dispute_id'=> $dispute->id,
        ]);

        // ── Activity log ──────────────────────────────────────────────────────
        $svcTitle = $session->service?->title ?? 'session';
        $amount   = '$' . number_format($refundRequest->amount_requested_cents / 100, 2);

        $this->activity->log(
            $client->id, 'provider', 'payment', ActivitySeverity::Warning,
            'session_refund_escalated',
            'Refund escalated to dispute — ' . $svcTitle,
            $amount . ' dispute opened. Dispute #' . substr($dispute->id, 0, 8) . ' is under review.',
            'service_session', $session->id, $provider->id,
            'log', $client->id
        );

        $this->activity->log(
            $provider->id, 'provider', 'payment', ActivitySeverity::Critical,
            'session_dispute_opened',
            $client->display_name . ' escalated a refund to dispute — ' . $svcTitle,
            $amount . ' dispute opened after you denied their refund request. Respond within ' . env('DISPUTE_RESPONDENT_REPLY_DAYS', 5) . ' days.',
            'service_session', $session->id, $client->id,
            'notification', $client->id
        );

        event(new SessionRefundEscalated($refundRequest->fresh(), $session, $dispute, $client, $provider));

        return $dispute;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    private function calculateRefundAmount(ServiceSession $session, SessionRefundType $type): int
    {
        return match ($type) {
            SessionRefundType::DepositOnly  => $session->deposit_cents ?? $session->expected_deposit_cents,
            SessionRefundType::BalanceOnly  => $session->balance_cents ?? $session->expected_balance_cents,
            SessionRefundType::Full         => ($session->deposit_cents ?? 0) + ($session->balance_cents ?? 0),
        };
    }

    private function resolvePaymentStatusAfterRefund(
        ServiceSession   $session,
        SessionRefundType $type,
        int              $totalRefunded
    ): ServiceSessionPaymentStatus {
        $totalPaid = ($session->deposit_cents ?? 0) + ($session->balance_cents ?? 0);
        $previouslyRefunded = $session->total_refunded_cents ?? 0;
        $netRefunded = $previouslyRefunded + $totalRefunded;

        if ($netRefunded >= $totalPaid) {
            return ServiceSessionPaymentStatus::Refunded;
        }
        return ServiceSessionPaymentStatus::PartiallyRefunded;
    }
}
