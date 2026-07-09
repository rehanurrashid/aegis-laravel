<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Enums\DisputeReason;
use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use App\Enums\InvoiceStatus;
use App\Events\Dispute\DisputeOpened;
use App\Events\Dispute\DisputeReplied;
use App\Events\Dispute\DisputeResolved;
use App\Models\BpInvoice;
use App\Models\CsInvoice;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Dispute lifecycle:
 *
 *   [ open ] → respondent has DISPUTE_RESPONDENT_REPLY_DAYS to reply
 *      │
 *      ├─ respondent replies                → under_review
 *      ├─ respondent doesn't reply in time  → under_review (default judgment risk)
 *      ▼
 *   [ under_review ] → admin decides
 *      ├─ refund (full or partial)          → resolved (Stripe refund fires)
 *      ├─ pay (full or partial)             → resolved (dismisses claim)
 *      ├─ no action                         → resolved (dismissed)
 *      ├─ stripe escalation                 → resolved (beyond our scope)
 *      ▼
 *   [ resolved ] → after DISPUTE_CLOSE_AFTER_RESOLUTION_DAYS
 *      ▼
 *   [ closed_no_action ]
 *
 * Aegis mediates + provides audit trail. Money movement uses Stripe's
 * refund rails; we NEVER hold funds.
 */
class DisputeService
{
    public function __construct(private ActivityService $activity) {}

    /**
     * Open a dispute against an invoice / payout / session.
     * Freezes the subject invoice (if applicable) at InvoiceStatus::Disputed.
     */
    public function open(
        User $disputer,
        string $subjectType,
        string $subjectId,
        DisputeReason $reason,
        int $amountDisputedCents,
        string $description
    ): Dispute {
        [$respondentId, $subjectAmountCents] = $this->resolveRespondentAndAmount($subjectType, $subjectId);

        if ($respondentId === $disputer->id) {
            throw new RuntimeException('Cannot dispute a transaction you were the sole party of.');
        }
        if ($amountDisputedCents <= 0 || $amountDisputedCents > $subjectAmountCents) {
            throw new RuntimeException('Disputed amount must be between $0.01 and the total subject amount.');
        }

        $dispute = Dispute::create([
            'id'                    => (string) Str::uuid(),
            'disputer_id'           => $disputer->id,
            'respondent_id'         => $respondentId,
            'subject_type'          => $subjectType,
            'subject_id'            => $subjectId,
            'reason'                => $reason->value,
            'amount_disputed_cents' => $amountDisputedCents,
            'description'           => $description,
            'status'                => DisputeStatus::AwaitingResponse->value,
            'opened_at'             => now(),
        ]);

        // Freeze the invoice if applicable
        $this->freezeSubject($subjectType, $subjectId);

        // Notify respondent + admin (activity fan-out; email listener triggers on event)
        $this->activity->log(
            $respondentId, 'shared', 'disputes',
            ActivitySeverity::Warning,
            'dispute_opened_against_you',
            'A dispute has been opened against you',
            'Please review and respond within ' . (int) env('DISPUTE_RESPONDENT_REPLY_DAYS', 5) . ' business days.',
            Dispute::class, $dispute->id, $disputer->id, 'notification', $disputer->id,
        );

        event(new DisputeOpened($dispute->fresh()));

        return $dispute->fresh();
    }

    /**
     * Post a message on the dispute. Author can be disputer or respondent.
     * The first respondent reply promotes status to UnderReview.
     */
    public function reply(Dispute $dispute, User $author, string $body, ?string $attachmentUrl = null): DisputeMessage
    {
        if (!$dispute->status->isActive()) {
            throw new RuntimeException('Cannot reply to a resolved or closed dispute.');
        }

        $role = match (true) {
            $author->id === $dispute->disputer_id   => 'disputer',
            $author->id === $dispute->respondent_id => 'respondent',
            $author->role?->value === 'admin'       => 'admin',
            default => throw new RuntimeException('You are not a party to this dispute.'),
        };

        $msg = DisputeMessage::create([
            'id'             => (string) Str::uuid(),
            'dispute_id'     => $dispute->id,
            'author_id'      => $author->id,
            'author_role'    => $role,
            'body'           => $body,
            'attachment_url' => $attachmentUrl,
            'created_at'     => now(),
        ]);

        // Status transitions on first respondent reply
        if ($role === 'respondent' && $dispute->status === DisputeStatus::AwaitingResponse) {
            $dispute->update([
                'status'                => DisputeStatus::UnderReview->value,
                'respondent_replied_at' => now(),
            ]);
        }

        // Notify counterparties
        $notifyId = match ($role) {
            'disputer'   => $dispute->respondent_id,
            'respondent' => $dispute->disputer_id,
            'admin'      => $dispute->disputer_id, // notify disputer of admin activity
        };
        $this->activity->log(
            $notifyId, 'shared', 'disputes',
            ActivitySeverity::Info,
            'dispute_reply',
            'New message on your dispute',
            Str::limit($body, 140),
            Dispute::class, $dispute->id, $author->id, 'notification', $author->id,
        );
        if ($role !== 'admin') {
            // Additionally notify admin queue (activity log only, one entry)
            $this->activity->log(
                null, 'admin', 'disputes',
                ActivitySeverity::Info,
                'dispute_message_added',
                'Dispute has new activity',
                'Dispute ' . $dispute->id . ' has a new reply from ' . $role . '.',
                Dispute::class, $dispute->id, $author->id, 'log', null,
            );
        }

        event(new DisputeReplied($dispute->fresh(), $msg));

        return $msg;
    }

    /**
     * Admin resolves the dispute. Executes the resolution's money side-effect:
     * Stripe refund for refund_full / refund_partial; unfreezes the subject
     * for pay_full / pay_partial / no_action.
     */
    public function resolve(
        Dispute $dispute,
        User $admin,
        DisputeResolution $resolution,
        ?int $resolutionCents,
        string $summary,
        ?PayoutService $payouts = null
    ): Dispute {
        if ($admin->role?->value !== 'admin') {
            throw new RuntimeException('Only admins can resolve disputes.');
        }
        if (!$dispute->status->isActive()) {
            throw new RuntimeException('This dispute is already resolved.');
        }

        // Validate cents against resolution kind
        $needsPartial = in_array($resolution, [
            DisputeResolution::RefundPartial,
            DisputeResolution::PayPartial,
        ], true);
        if ($needsPartial && (!$resolutionCents || $resolutionCents <= 0 || $resolutionCents > $dispute->amount_disputed_cents)) {
            throw new RuntimeException('Partial resolutions require a positive cents amount within the disputed amount.');
        }

        // Money side-effect
        $refundIssued = false;
        switch ($resolution) {
            case DisputeResolution::RefundFull:
            case DisputeResolution::RefundPartial:
                $refundCents = $resolution === DisputeResolution::RefundFull
                    ? (int) $dispute->amount_disputed_cents
                    : (int) $resolutionCents;
                $refundIssued = $this->issueStripeRefund($dispute, $refundCents);
                break;

            case DisputeResolution::PayFull:
            case DisputeResolution::PayPartial:
            case DisputeResolution::NoAction:
                // Money already flowed correctly; just unfreeze the subject
                $this->unfreezeSubject($dispute->subject_type, $dispute->subject_id);
                break;

            case DisputeResolution::StripeDisputeEscalated:
                // Aegis is out — party will pursue Stripe chargeback
                break;
        }

        $dispute->update([
            'status'             => DisputeStatus::Resolved->value,
            'resolution'         => $resolution->value,
            'resolution_cents'   => $resolutionCents,
            'resolution_summary' => $summary,
            'resolved_by'        => $admin->id,
            'resolved_at'        => now(),
        ]);

        // Notify both parties
        foreach ([$dispute->disputer_id, $dispute->respondent_id] as $userId) {
            $this->activity->log(
                $userId, 'shared', 'disputes',
                ActivitySeverity::Info,
                'dispute_resolved',
                'Your dispute has been resolved',
                Str::limit($resolution->label() . ' — ' . $summary, 200),
                Dispute::class, $dispute->id, $admin->id, 'notification', $admin->id,
            );
        }

        event(new DisputeResolved($dispute->fresh()));

        Log::info('[DISPUTE] resolved', [
            'dispute_id'     => $dispute->id,
            'resolution'     => $resolution->value,
            'refund_issued'  => $refundIssued,
            'admin_id'       => $admin->id,
        ]);

        return $dispute->fresh();
    }

    // ── Query helpers ───────────────────────────────────────────────────

    public function listForUser(string $userId, ?bool $activeOnly = null): Collection
    {
        $q = Dispute::where(function ($q) use ($userId) {
            $q->where('disputer_id', $userId)->orWhere('respondent_id', $userId);
        })->orderByDesc('opened_at');

        if ($activeOnly === true) {
            $q->active();
        }

        return $q->get();
    }

    public function listForAdmin(?DisputeStatus $statusFilter = null): Collection
    {
        $q = Dispute::orderByDesc('opened_at');
        if ($statusFilter) {
            $q->where('status', $statusFilter->value);
        }
        return $q->get();
    }

    // ── Internals ───────────────────────────────────────────────────────

    /**
     * Determine the "other party" of the subject and its total amount.
     * @return array{0:string,1:int}  [respondent_id, subject_amount_cents]
     */
    private function resolveRespondentAndAmount(string $subjectType, string $subjectId): array
    {
        return match ($subjectType) {
            'cs_invoice' => (function () use ($subjectId) {
                $inv = CsInvoice::findOrFail($subjectId);
                // Either party can dispute an invoice; respondent is the other side.
                return [$inv->cs_id, (int) $inv->total_cents];
            })(),
            'bp_invoice' => (function () use ($subjectId) {
                $inv = BpInvoice::findOrFail($subjectId);
                return [$inv->bp_id, (int) $inv->total_cents];
            })(),
            'bp_payout' => (function () use ($subjectId) {
                $p = \App\Models\BpPayout::findOrFail($subjectId);
                return [$p->bp_id, (int) $p->amount_cents];
            })(),
            'session' => (function () use ($subjectId) {
                $s = \App\Models\ServiceSession::findOrFail($subjectId);
                return [$s->practitioner_id, (int) $s->amount_cents];
            })(),
            default => throw new RuntimeException("Unknown dispute subject type: {$subjectType}"),
        };
    }

    /**
     * Freeze the subject (invoice) at "disputed" so the payer cannot pay
     * while the dispute is open. Payouts/sessions are not frozen because
     * the money already moved; those are refund-only paths.
     */
    private function freezeSubject(string $subjectType, string $subjectId): void
    {
        if ($subjectType === 'cs_invoice') {
            CsInvoice::where('id', $subjectId)
                ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value])
                ->update(['status' => InvoiceStatus::Disputed->value]);
        } elseif ($subjectType === 'bp_invoice') {
            BpInvoice::where('id', $subjectId)
                ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value])
                ->update(['status' => InvoiceStatus::Disputed->value]);
        }
    }

    /**
     * Unfreeze a disputed invoice back to "sent" so payment can proceed.
     */
    private function unfreezeSubject(string $subjectType, string $subjectId): void
    {
        if ($subjectType === 'cs_invoice') {
            CsInvoice::where('id', $subjectId)
                ->where('status', InvoiceStatus::Disputed->value)
                ->update(['status' => InvoiceStatus::Sent->value]);
        } elseif ($subjectType === 'bp_invoice') {
            BpInvoice::where('id', $subjectId)
                ->where('status', InvoiceStatus::Disputed->value)
                ->update(['status' => InvoiceStatus::Sent->value]);
        }
    }

    /**
     * Try to issue a Stripe refund against the subject's PaymentIntent.
     * Fails soft: if Stripe rejects (already refunded, no PI, etc.) we log
     * and return false so the admin can decide next steps in-portal.
     */
    private function issueStripeRefund(Dispute $dispute, int $refundCents): bool
    {
        if (!config('services.stripe.secret')) {
            Log::warning('[DISPUTE] refund skipped — no Stripe secret', ['dispute' => $dispute->id]);
            return false;
        }

        // Locate the underlying PaymentIntent
        $piId = null;
        if ($dispute->subject_type === 'cs_invoice') {
            $piId = optional(CsInvoice::find($dispute->subject_id))->stripe_payment_intent_id;
        } elseif ($dispute->subject_type === 'bp_invoice') {
            $bpInvoice = BpInvoice::find($dispute->subject_id);
            $payment = $bpInvoice ? $bpInvoice->payments()->latest()->first() : null;
            $piId = $payment?->stripe_payment_intent_id ?? $payment?->stripe_charge_id;
        } elseif ($dispute->subject_type === 'bp_payout') {
            $piId = optional(\App\Models\BpPayout::find($dispute->subject_id))->stripe_payment_intent_id;
        } elseif ($dispute->subject_type === 'session') {
            $piId = optional(\App\Models\ServiceSession::find($dispute->subject_id))->stripe_payment_intent_id;
        }

        if (!$piId || str_starts_with($piId, 'pi_demo_') || str_starts_with($piId, 'pi_stub_')) {
            Log::info('[DISPUTE] refund noop — demo/stub PaymentIntent', ['pi' => $piId]);
            return false;
        }

        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $stripe->refunds->create([
                'payment_intent' => $piId,
                'amount'         => $refundCents,
                'reason'         => 'requested_by_customer',
                'metadata'       => [
                    'aegis_dispute_id' => $dispute->id,
                    'resolution'       => $dispute->resolution?->value,
                ],
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('[DISPUTE] Stripe refund failed', [
                'dispute' => $dispute->id,
                'pi'      => $piId,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }
}
