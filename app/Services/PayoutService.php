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
 * Handles Stripe Connect destination charges — Aegis never holds funds.
 *
 * Payment flow (Provider → BP):
 *   1. Provider has a saved Stripe Customer (stripe_id) + default PaymentMethod (stripe_payment_method_id)
 *   2. BP has a Stripe Connect Express account (stripe_account_id = acct_xxx)
 *   3. Aegis creates a PaymentIntent with transfer_data.destination = BP's acct_xxx
 *   4. Stripe charges Provider's card and IMMEDIATELY routes 100% to BP's connected account
 *   5. Aegis platform net = $0 — it never touches the money
 *   6. Webhook payment_intent.succeeded confirms transfer and marks BpPayout as paid
 */
class PayoutService
{
    public function __construct(private ActivityService $activity) {}

    // ── Legacy helpers (kept for compatibility) ────────────────────────────────

    public function createBpPayout(string $bpId, int $amountCents, string $description = ''): BpPayout
    {
        return BpPayout::create([
            'id'          => 'bpo_' . Str::lower(Str::random(12)),
            'bp_id'       => $bpId,
            'amount_cents'=> $amountCents,
            'currency'    => 'USD',
            'status'      => 'pending',
            'description' => $description,
        ]);
    }

    public function createCsPayout(string $csId, int $amountCents, string $description = ''): CsPayout
    {
        return CsPayout::create([
            'id'          => 'cspo_' . Str::lower(Str::random(12)),
            'cs_id'       => $csId,
            'amount_cents'=> $amountCents,
            'currency'    => 'USD',
            'status'      => 'pending',
            'description' => $description,
        ]);
    }

    /**
     * Legacy release() — used by admin payout panel and CS payouts.
     * These still use platform transfers (CS fees come from Aegis subscription revenue).
     */
    public function release($payout): mixed
    {
        $payout->update(['status' => 'in_transit', 'released_at' => now()]);

        if (config('services.stripe.secret')) {
            try {
                $stripe      = new StripeClient(config('services.stripe.secret'));
                $recipientId = $payout instanceof BpPayout ? $payout->bp_id : $payout->cs_id;
                $recipient   = User::find($recipientId);
                $stripeAccount = $recipient?->stripe_account_id;

                if ($stripeAccount) {
                    $transfer = $stripe->transfers->create([
                        'amount'      => (int) round(($payout->amount_cents ?? ($payout->amount * 100))),
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
            $payout->update(['status' => 'paid', 'stripe_transfer_id' => 'stub_' . Str::lower(Str::random(12))]);
        }

        event(new PayoutReleased($payout->fresh()));

        $recipientId = $payout instanceof BpPayout ? $payout->bp_id : $payout->cs_id;
        $portal      = $payout instanceof BpPayout ? 'business_partner' : 'continuity_steward';

        $this->activity->log(
            $recipientId, $portal, 'payment', ActivitySeverity::Info,
            'payout_released', 'Payout released',
            '$' . number_format(($payout->amount_cents ?? ($payout->amount * 100)) / 100, 2) . ' is on its way to your bank account.',
            $payout instanceof BpPayout ? 'bp_payout' : 'cs_payout', $payout->id,
            null, 'notification', $recipientId
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

    // ── Core destination charge: Provider → BP via Aegis platform ─────────────

    /**
     * Charge the Provider's saved card and immediately route 100% to the BP's
     * Stripe Connect Express account via a destination charge.
     *
     * Aegis acts as the platform but holds $0 — all funds pass through to BP.
     *
     * Guards:
     *  - Provider must have stripe_id (customer) + stripe_payment_method_id (saved card)
     *  - BP must have a valid stripe_account_id (acct_xxx format)
     *
     * Stubs gracefully when Stripe is not configured (sandbox / local dev).
     *
     * @throws \RuntimeException with a user-facing message on guard failure
     */
    public function chargeProviderToBp(
        User   $provider,
        User   $bp,
        int    $amountCents,
        string $currency = 'usd',
        array  $meta = [],
        string $description = ''
    ): array {
        // ── Guards ────────────────────────────────────────────────────────────
        if (!$provider->stripe_id || !$provider->stripe_payment_method_id) {
            throw new \RuntimeException(
                'No payment method on file. Please add a card in Settings → Billing before releasing payment.'
            );
        }

        $bpAccount   = $bp->stripe_account_id;
        $isRealAcct  = $bpAccount && preg_match('/^acct_[a-zA-Z0-9]{16,}$/', $bpAccount);

        if (!$isRealAcct && config('services.stripe.secret')) {
            // Real Stripe env but BP has no real connected account — stub with warning
            return [
                'stripe_payment_intent_id' => null,
                'stripe_transfer_id'       => null,
                'status'                   => 'pending',
                'stub'                     => true,
                'stub_reason'              => 'BP has no verified Stripe Connect account.',
            ];
        }

        // ── Stripe not configured — stub mode ─────────────────────────────────
        if (!config('services.stripe.secret')) {
            return [
                'stripe_payment_intent_id' => 'pi_stub_' . Str::lower(Str::random(16)),
                'stripe_transfer_id'       => null,
                'status'                   => 'pending',
                'stub'                     => true,
                'stub_reason'              => 'Stripe not configured (sandbox mode).',
            ];
        }

        // ── Live Stripe destination charge ────────────────────────────────────
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            $intent = $stripe->paymentIntents->create([
                'amount'               => $amountCents,
                'currency'             => strtolower($currency),
                'customer'             => $provider->stripe_id,
                'payment_method'       => $provider->stripe_payment_method_id,
                'confirm'              => true,
                'automatic_payment_methods' => [
                    'enabled'          => true,
                    'allow_redirects'  => 'never',
                ],
                'transfer_data'        => ['destination' => $bpAccount],
                'on_behalf_of'         => $bpAccount,
                'description'          => $description ?: 'Aegis contract payment',
                'metadata'             => array_merge($meta, [
                    'provider_id' => $provider->id,
                    'bp_id'       => $bp->id,
                ]),
            ]);

            return [
                'stripe_payment_intent_id' => $intent->id,
                'stripe_transfer_id'       => $intent->transfer_data->destination_payment ?? null,
                'status'                   => $intent->status === 'succeeded' ? 'paid' : 'pending',
                'stub'                     => false,
            ];
        } catch (\Stripe\Exception\CardException $e) {
            throw new \RuntimeException('Card declined: ' . $e->getMessage());
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            throw new \RuntimeException('Stripe error: ' . $e->getMessage());
        } catch (\Stripe\Exception\AuthenticationException $e) {
            throw new \RuntimeException('Stripe authentication failed. Check STRIPE_SECRET in .env.');
        } catch (\Throwable $e) {
            throw new \RuntimeException('Payment failed: ' . $e->getMessage());
        }
    }

    // ── Contract payment helpers ───────────────────────────────────────────────

    /**
     * End a one-time payment contract and charge Provider → BP via Stripe.
     */
    public function endContractAndRelease(BpContract $contract, User $provider): BpPayout
    {
        $bp = User::findOrFail($contract->bp_id);

        $result = $this->chargeProviderToBp(
            provider:    $provider,
            bp:          $bp,
            amountCents: $contract->total_value_cents,
            currency:    'usd',
            meta:        ['contract_id' => $contract->id, 'type' => 'one_time'],
            description: 'Contract payment: ' . $contract->title,
        );

        $payout = BpPayout::create([
            'id'                       => 'bpo_' . Str::lower(Str::random(12)),
            'bp_id'                    => $contract->bp_id,
            'provider_id'              => $provider->id,
            'contract_id'              => $contract->id,
            'amount_cents'             => $contract->total_value_cents,
            'currency'                 => 'USD',
            'status'                   => $result['status'],
            'stripe_payment_intent_id' => $result['stripe_payment_intent_id'],
            'stripe_transfer_id'       => $result['stripe_transfer_id'],
            'released_at'              => now(),
            'description'              => 'One-time contract payment: ' . $contract->title,
        ]);

        $contract->update(['status' => 'completed', 'ended_at' => now(), 'completed_at' => now()]);

        $this->logPaymentActivity($provider, $bp, $payout, $contract->title, $contract->total_value_cents, $result);

        return $payout;
    }

    /**
     * Pay an approved milestone: charge Provider → BP via Stripe destination charge.
     */
    public function payMilestone(BpMilestone $milestone, User $provider): BpPayout
    {
        $statusVal = $milestone->status instanceof \BackedEnum
            ? $milestone->status->value
            : (string) $milestone->status;

        if ($statusVal !== 'approved') {
            throw new \RuntimeException('Milestone must be approved before payment.');
        }

        $contract = $milestone->contract;
        $bp       = User::findOrFail($contract->bp_id);

        $result = $this->chargeProviderToBp(
            provider:    $provider,
            bp:          $bp,
            amountCents: $milestone->amount_cents,
            currency:    'usd',
            meta:        ['contract_id' => $contract->id, 'milestone_id' => $milestone->id],
            description: 'Milestone payment: ' . $milestone->title,
        );

        $payout = BpPayout::create([
            'id'                       => 'bpo_' . Str::lower(Str::random(12)),
            'bp_id'                    => $contract->bp_id,
            'provider_id'              => $provider->id,
            'contract_id'              => $contract->id,
            'milestone_id'             => $milestone->id,
            'amount_cents'             => $milestone->amount_cents,
            'currency'                 => 'USD',
            'status'                   => $result['status'],
            'stripe_payment_intent_id' => $result['stripe_payment_intent_id'],
            'stripe_transfer_id'       => $result['stripe_transfer_id'],
            'released_at'              => now(),
            'description'              => 'Milestone payment: ' . $milestone->title,
        ]);

        $milestone->update([
            'status'    => 'paid',
            'paid_at'   => now(),
            'payout_id' => $payout->id,
        ]);

        $this->logPaymentActivity($provider, $bp, $payout, $milestone->title, $milestone->amount_cents, $result, 'milestone');

        return $payout;
    }

    /**
     * Release payment for a completed service session.
     * Uses the same destination charge pattern: client's card → practitioner's connected account.
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
                    'session_payout_failed', 'Session payout failed', $e->getMessage(),
                    'practitioner_payment', $payment->id, null, 'notification', $practitioner->id
                );
                throw $e;
            }
        } else {
            $payment->update([
                'status'             => PractitionerPaymentStatus::Pending->value,
                'stripe_transfer_id' => null,
                'paid_at'            => null,
            ]);
        }

        $this->activity->log(
            $practitioner->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_payout_initiated',
            'Session payout ' . ($payment->status instanceof PractitionerPaymentStatus ? $payment->status->label() : (string) $payment->status),
            '$' . number_format($payment->amount_cents / 100, 2) . ' for completed session.',
            'practitioner_payment', $payment->id, null, 'log', $practitioner->id
        );

        return $payment->fresh();
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function logPaymentActivity(
        User    $provider,
        User    $bp,
        BpPayout $payout,
        string  $title,
        int     $amountCents,
        array   $result,
        string  $type = 'contract'
    ): void {
        $amountLabel = '$' . number_format($amountCents / 100, 2);
        $linkType    = $type === 'milestone' ? 'bp_milestone' : 'bp_payout';
        $linkId      = $type === 'milestone' ? ($payout->milestone_id ?? $payout->id) : $payout->id;

        $statusNote = $result['stub'] ?? false
            ? ' (payment queued — BP Stripe account pending verification)'
            : ' via Stripe';

        // Provider log
        $this->activity->log(
            $provider->id, 'provider', 'job_postings', ActivitySeverity::Info,
            $type === 'milestone' ? 'milestone_paid' : 'contract_payment_released',
            ucfirst($type) . ' payment released: ' . $title,
            $amountLabel . ' charged to your card and sent to ' . $bp->display_name . $statusNote . '.',
            $linkType, $linkId, null, 'log', $provider->id
        );

        // BP notification
        $this->activity->log(
            $bp->id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            $type === 'milestone' ? 'milestone_payment_received' : 'contract_payment_received',
            ucfirst($type) . ' payment received: ' . $title,
            $amountLabel . ' has been transferred to your connected Stripe account.',
            $linkType, $linkId, $provider->id, 'notification', $provider->id
        );
    }
}
