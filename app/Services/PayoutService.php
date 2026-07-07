<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Events\Business\PayoutReleased;
use App\Models\BpContract;
use App\Models\BpMilestone;
use App\Models\BpPayout;
use App\Models\CsPayout;
use App\Models\User;
use App\Models\PractitionerPayment;
use App\Enums\PractitionerPaymentStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Stripe\StripeClient;

/**
 * Handles Stripe Connect transfers — Aegis never holds funds.
 */
class PayoutService
{
    public function __construct(private ActivityService $activity) {}

    public function createBpPayout(string $bpId, int $amountCents, string $description = ''): BpPayout
    {
        return BpPayout::create([
            'id'          => 'bpo_' . Str::lower(Str::random(12)),
            'bp_id'       => $bpId,
            'amount'      => $amountCents / 100,
            'currency'    => 'USD',
            'status'      => 'pending',
            'description' => $description,
            'created_at'  => now(),
        ]);
    }

    public function createCsPayout(string $csId, int $amountCents, string $description = ''): CsPayout
    {
        return CsPayout::create([
            'id'          => 'cspo_' . Str::lower(Str::random(12)),
            'cs_id'       => $csId,
            'amount'      => $amountCents / 100,
            'currency'    => 'USD',
            'status'      => 'pending',
            'description' => $description,
            'created_at'  => now(),
        ]);
    }

    /**
     * Release a payout: calls Stripe Transfer API (stub if not configured),
     * fires event, fans out.
     *
     * @param BpPayout|CsPayout $payout
     */
    public function release($payout): mixed
    {
        $payout->update(['status' => 'in_transit', 'released_at' => now()]);

        // Stripe Connect transfer
        if (config('services.stripe.secret')) {
            try {
                $stripe = new StripeClient(config('services.stripe.secret'));
                $recipientId = $payout instanceof BpPayout ? $payout->bp_id : $payout->cs_id;
                $recipient = User::find($recipientId);
                $stripeAccount = $recipient?->stripe_connect_account_id;

                if ($stripeAccount) {
                    $transfer = $stripe->transfers->create([
                        'amount'      => (int) round($payout->amount * 100),
                        'currency'    => strtolower($payout->currency ?? 'usd'),
                        'destination' => $stripeAccount,
                        'description' => $payout->description,
                    ]);
                    $payout->update([
                        'stripe_transfer_id' => $transfer->id,
                        'status'             => 'paid',
                    ]);
                }
            } catch (\Throwable $e) {
                $payout->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
                throw $e;
            }
        } else {
            // Stub mode — mark as paid without actual transfer
            $payout->update(['status' => 'paid', 'stripe_transfer_id' => 'stub_' . Str::lower(Str::random(12))]);
        }

        event(new PayoutReleased($payout->fresh()));

        $recipientId = $payout instanceof BpPayout ? $payout->bp_id : $payout->cs_id;
        $portal = $payout instanceof BpPayout ? 'business_partner' : 'continuity_steward';

        $this->activity->log(
            $recipientId, $portal, 'payment', ActivitySeverity::Info,
            'payout_released',
            'Payout released',
            '$' . number_format($payout->amount, 2) . ' is on its way to your bank account.',
            $payout instanceof BpPayout ? 'bp_payout' : 'cs_payout',
            $payout->id
        );

        return $payout->fresh();
    }

    public function getPendingBp(?string $bpId = null): Collection
    {
        $q = BpPayout::where('status', 'pending');
        if ($bpId) $q->where('bp_id', $bpId);
        return $q->get();
    }

    public function getPendingCs(?string $csId = null): Collection
    {
        $q = CsPayout::where('status', 'pending');
        if ($csId) $q->where('cs_id', $csId);
        return $q->get();
    }

    /**
     * Release payment for a completed service session.
     * Transfers funds directly to the practitioner's Stripe Connect account.
     * Falls back to stub mode (status=paid, no real transfer) if Stripe not configured
     * or the practitioner has no connected account.
     */
    public function releaseServiceSessionPayout(PractitionerPayment $payment, User $practitioner): PractitionerPayment
    {
        $stripeAccount = $practitioner->stripe_account_id;

        if (config('services.stripe.secret') && $stripeAccount) {
            try {
                $stripe   = new StripeClient(config('services.stripe.secret'));
                $transfer = $stripe->transfers->create([
                    'amount'      => $payment->amount_cents,
                    'currency'    => strtolower($payment->currency ?? 'usd'),
                    'destination' => $stripeAccount,
                    'description' => 'Service session payout — Aegis',
                    'metadata'    => ['payment_id' => $payment->id],
                ]);
                $payment->update([
                    'status'             => PractitionerPaymentStatus::Paid->value,
                    'stripe_transfer_id' => $transfer->id,
                    'paid_at'            => now(),
                ]);
            } catch (\Throwable $e) {
                $payment->update(['status' => PractitionerPaymentStatus::Failed->value]);
                $this->activity->log(
                    $practitioner->id, 'provider', 'payment', ActivitySeverity::Critical,
                    'session_payout_failed',
                    'Session payout failed',
                    $e->getMessage(),
                    'practitioner_payment', $payment->id,
                    null, 'notification', $practitioner->id
                );
                throw $e;
            }
        } else {
            // Stub — no real Stripe key or no connected account
            $payment->update([
                'status'             => $stripeAccount
                    ? PractitionerPaymentStatus::Pending->value   // key missing, queue for later
                    : PractitionerPaymentStatus::Pending->value,  // no Connect account yet
                'stripe_transfer_id' => null,
                'paid_at'            => null,
            ]);
        }

        $this->activity->log(
            $practitioner->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_payout_initiated',
            'Session payout ' . ($payment->status instanceof PractitionerPaymentStatus ? $payment->status->label() : (string) $payment->status),
            '$' . number_format($payment->amount_cents / 100, 2) . ' for completed session.',
            'practitioner_payment', $payment->id,
            null, 'log', $practitioner->id
        );

        return $payment->fresh();
    }

    /**
     * Transfer funds directly to a BP via Stripe Connect.
     * Stubs when Stripe not configured or BP has no connected account.
     */
    public function releaseToConnectedAccount(
        User $bp,
        int $amountCents,
        string $currency = 'usd',
        array $meta = []
    ): array {
        $stripeAccount = $bp->stripe_account_id;
        if (config('services.stripe.secret') && $stripeAccount) {
            try {
                $stripe = new StripeClient(config('services.stripe.secret'));
                $transfer = $stripe->transfers->create([
                    'amount'      => $amountCents,
                    'currency'    => $currency,
                    'destination' => $stripeAccount,
                    'metadata'    => $meta,
                ]);
                return ['stripe_transfer_id' => $transfer->id, 'status' => 'paid'];
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                throw new \RuntimeException('Stripe error: ' . $e->getMessage());
            } catch (\Stripe\Exception\AuthenticationException $e) {
                throw new \RuntimeException('Stripe authentication failed. Check STRIPE_SECRET in .env.');
            } catch (\Throwable $e) {
                throw new \RuntimeException('Stripe transfer failed: ' . $e->getMessage());
            }
        }
        return [
            'stripe_transfer_id' => 'stub_' . Str::lower(Str::random(12)),
            'status' => 'pending',
        ];
    }

    /**
     * End a one-time payment contract and release full value to BP via Stripe.
     */
    public function endContractAndRelease(BpContract $contract): BpPayout
    {
        $bp = User::findOrFail($contract->bp_id);
        $result = $this->releaseToConnectedAccount(
            $bp,
            $contract->total_value_cents,
            'usd',
            ['contract_id' => $contract->id, 'type' => 'one_time']
        );

        $payout = BpPayout::create([
            'id'                 => 'bpo_' . Str::lower(Str::random(12)),
            'bp_id'              => $contract->bp_id,
            'contract_id'        => $contract->id,
            'amount_cents'       => $contract->total_value_cents,
            'currency'           => 'USD',
            'status'             => $result['status'],
            'stripe_transfer_id' => $result['stripe_transfer_id'],
            'released_at'        => now(),
            'description'        => 'One-time contract payment: ' . $contract->title,
        ]);

        $contract->update(['status' => 'completed', 'ended_at' => now(), 'completed_at' => now()]);

        $this->activity->log(
            $contract->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'contract_payment_released',
            'Payment released: ' . $contract->title,
            '$' . number_format($contract->total_value_cents / 100, 2) . ' sent to ' . $bp->display_name . ' via Stripe.',
            'bp_payout', $payout->id, null,
            'log', $contract->practitioner_id
        );

        $this->activity->log(
            $contract->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            'contract_payment_received',
            'Payment received: ' . $contract->title,
            '$' . number_format($contract->total_value_cents / 100, 2) . ' transferred to your account.',
            'bp_payout', $payout->id, $contract->practitioner_id,
            'notification', $contract->practitioner_id
        );

        return $payout;
    }

    /**
     * Pay an approved milestone and release funds to BP via Stripe.
     */
    public function payMilestone(BpMilestone $milestone): BpPayout
    {
        $statusVal = $milestone->status instanceof \BackedEnum
            ? $milestone->status->value
            : (string) $milestone->status;

        if ($statusVal !== 'approved') {
            throw new \RuntimeException('Milestone must be approved before payment.');
        }

        $contract = $milestone->contract;
        $bp = User::findOrFail($contract->bp_id);

        $result = $this->releaseToConnectedAccount(
            $bp,
            $milestone->amount_cents,
            'usd',
            ['contract_id' => $contract->id, 'milestone_id' => $milestone->id]
        );

        $payout = BpPayout::create([
            'id'                 => 'bpo_' . Str::lower(Str::random(12)),
            'bp_id'              => $contract->bp_id,
            'contract_id'        => $contract->id,
            'milestone_id'       => $milestone->id,
            'amount_cents'       => $milestone->amount_cents,
            'currency'           => 'USD',
            'status'             => $result['status'],
            'stripe_transfer_id' => $result['stripe_transfer_id'],
            'released_at'        => now(),
            'description'        => 'Milestone payment: ' . $milestone->title,
        ]);

        $milestone->update([
            'status'    => 'paid',
            'paid_at'   => now(),
            'payout_id' => $payout->id,
        ]);

        $this->activity->log(
            $contract->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'milestone_paid',
            'Milestone payment released: ' . $milestone->title,
            '$' . number_format($milestone->amount_cents / 100, 2) . ' sent to ' . $bp->display_name . ' via Stripe.',
            'bp_milestone', $milestone->id, null,
            'log', $contract->practitioner_id
        );

        $this->activity->log(
            $contract->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            'milestone_payment_received',
            'Milestone payment received: ' . $milestone->title,
            '$' . number_format($milestone->amount_cents / 100, 2) . ' transferred to your account.',
            'bp_milestone', $milestone->id, $contract->practitioner_id,
            'notification', $contract->practitioner_id
        );

        return $payout;
    }
}
