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
        // ── Demo / stub detection ─────────────────────────────────────────────
        // Demo seed data uses cus_demo_* / pm_demo_* / acct_demo_* identifiers.
        // These are not real Stripe objects — always stub so demo works without real Stripe onboarding.
        $isDemoProvider = str_starts_with((string) $provider->stripe_id, 'cus_demo_')
            || str_starts_with((string) $provider->stripe_payment_method_id, 'pm_demo_');
        $isDemoBp = str_starts_with((string) $bp->stripe_account_id, 'acct_demo_');

        if ($isDemoProvider || $isDemoBp) {
            return [
                'stripe_payment_intent_id' => 'pi_demo_' . Str::lower(Str::random(16)),
                'stripe_transfer_id'       => null,
                'status'                   => 'paid', // Stub as paid so demo flow completes
                'stub'                     => true,
                'stub_reason'              => 'Demo mode — no real Stripe objects.',
            ];
        }

        // ── Guards (production / real Stripe env) ────────────────────────────
        if (!$provider->stripe_id || !$provider->stripe_payment_method_id) {
            throw new \RuntimeException(
                'No payment method on file. Please add a card in Settings → Billing before releasing payment.'
            );
        }

        $bpAccount  = $bp->stripe_account_id;
        $isRealAcct = $bpAccount && preg_match('/^acct_[a-zA-Z0-9]{16,}$/', $bpAccount);

        if (!$isRealAcct) {
            throw new \RuntimeException(
                'BP has not connected a Stripe account yet. They must complete payment setup before payment can be released.'
            );
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

    // ── CS invoice payment ────────────────────────────────────────────────────────

    /**
     * Provider → CS destination charge. Mirrors chargeProviderToBp() exactly
     * but the recipient is a Continuity Steward rather than a Business Partner.
     *
     * Used when a Provider pays a CS invoice.
     *
     * @throws \RuntimeException on Stripe error or missing account details
     */
    public function chargeProviderToCs(
        User   $provider,
        User   $cs,
        int    $amountCents,
        string $currency = 'usd',
        array  $meta = [],
        string $description = ''
    ): array {
        // ── Demo / stub detection ─────────────────────────────────────────────
        $isDemoProvider = str_starts_with((string) $provider->stripe_id, 'cus_demo_')
            || str_starts_with((string) $provider->stripe_payment_method_id, 'pm_demo_');
        $isDemoCs = str_starts_with((string) $cs->stripe_account_id, 'acct_demo_');

        if ($isDemoProvider || $isDemoCs) {
            return [
                'stripe_payment_intent_id' => 'pi_demo_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(16)),
                'stripe_transfer_id'       => null,
                'status'                   => 'paid',
                'stub'                     => true,
                'stub_reason'              => 'Demo mode — no real Stripe objects.',
            ];
        }

        // ── Guards ────────────────────────────────────────────────────────────
        if (!$provider->stripe_id || !$provider->stripe_payment_method_id) {
            throw new \RuntimeException('No payment method on file. Please add a card in Settings → Billing before paying this invoice.');
        }

        $csAccount  = $cs->stripe_account_id;
        $isRealAcct = $csAccount && preg_match('/^acct_[a-zA-Z0-9]{16,}$/', $csAccount);
        if (!$isRealAcct) {
            throw new \RuntimeException('CS has not connected a Stripe account yet. They must complete payment setup before payment can be released.');
        }

        if (!config('services.stripe.secret')) {
            return [
                'stripe_payment_intent_id' => 'pi_stub_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(16)),
                'stripe_transfer_id'       => null,
                'status'                   => 'pending',
                'stub'                     => true,
                'stub_reason'              => 'Stripe not configured (sandbox mode).',
            ];
        }

        // ── Live Stripe destination charge ────────────────────────────────────
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $intent = $stripe->paymentIntents->create([
                'amount'               => $amountCents,
                'currency'             => strtolower($currency),
                'customer'             => $provider->stripe_id,
                'payment_method'       => $provider->stripe_payment_method_id,
                'confirm'              => true,
                'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                'transfer_data'        => ['destination' => $csAccount],
                'on_behalf_of'         => $csAccount,
                'description'          => $description ?: 'Aegis CS invoice payment',
                'metadata'             => array_merge($meta, [
                    'provider_id' => $provider->id,
                    'cs_id'       => $cs->id,
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

        // Fire PayoutReleased → SendEmailNotificationListener sends bp/40-payout-released to BP
        event(new PayoutReleased($payout->fresh()));

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

        // Fire PayoutReleased → SendEmailNotificationListener sends bp/40-payout-released to BP
        event(new PayoutReleased($payout->fresh()));

        return $payout;
    }

    /**
     * Release payment for a completed service session.
     *
     * Uses the same destination charge pattern as chargeProviderToBp():
     *   Client's saved card (stripe_id + stripe_payment_method_id)
     *   → Aegis platform ($0 net)
     *   → Practitioner's Stripe Connect account (stripe_account_id)
     *
     * Demo detection: cus_demo_* / pm_demo_* / acct_demo_* identifiers bypass
     * real Stripe so the demo works without live credentials.
     *
     * @throws \RuntimeException with user-facing message on guard failure
     */
    public function releaseServiceSessionPayout(
        PractitionerPayment $payment,
        User $practitioner,
        User $client
    ): PractitionerPayment {
        // ── Demo / stub detection ─────────────────────────────────────────
        $isDemoClient = str_starts_with((string) $client->stripe_id, 'cus_demo_')
            || str_starts_with((string) $client->stripe_payment_method_id, 'pm_demo_');
        $isDemoPractitioner = str_starts_with((string) $practitioner->stripe_account_id, 'acct_demo_');

        if ($isDemoClient || $isDemoPractitioner) {
            $payment->update([
                'status'                   => PractitionerPaymentStatus::Paid->value,
                'stripe_payment_intent_id' => 'pi_demo_' . Str::lower(Str::random(16)),
                'paid_at'                  => now(),
            ]);
            $this->logSessionPaymentActivity($payment, $practitioner, $client, stub: true);
            return $payment->fresh();
        }

        // ── Guards (production) ───────────────────────────────────────────
        if (!$client->stripe_id || !$client->stripe_payment_method_id) {
            throw new \RuntimeException(
                'No payment method on file. You must add a card in Settings → Billing before confirming sessions.'
            );
        }

        $practitionerAccount = $practitioner->stripe_account_id;
        if (!$practitionerAccount || !preg_match('/^acct_[a-zA-Z0-9]{16,}$/', $practitionerAccount)) {
            throw new \RuntimeException(
                'Provider has not connected a Stripe account. Payment will be queued once they complete account setup.'
            );
        }

        // ── No Stripe key configured — stub pending ───────────────────────
        if (!config('services.stripe.secret')) {
            $payment->update([
                'status'                   => PractitionerPaymentStatus::Pending->value,
                'stripe_payment_intent_id' => null,
                'paid_at'                  => null,
            ]);
            return $payment->fresh();
        }

        // ── Live Stripe destination charge ────────────────────────────────
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $intent = $stripe->paymentIntents->create([
                'amount'               => $payment->amount_cents,
                'currency'             => strtolower($payment->currency ?? 'usd'),
                'customer'             => $client->stripe_id,
                'payment_method'       => $client->stripe_payment_method_id,
                'confirm'              => true,
                'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                'transfer_data'        => ['destination' => $practitionerAccount],
                'on_behalf_of'         => $practitionerAccount,
                'description'          => 'Session payment — Aegis',
                'metadata'             => [
                    'payment_id'      => $payment->id,
                    'practitioner_id' => $practitioner->id,
                    'client_id'       => $client->id,
                ],
            ]);

            $isPaid = $intent->status === 'succeeded';
            $payment->update([
                'status'                   => $isPaid
                    ? PractitionerPaymentStatus::Paid->value
                    : PractitionerPaymentStatus::Pending->value,
                'stripe_payment_intent_id' => $intent->id,
                'paid_at'                  => $isPaid ? now() : null,
            ]);
        } catch (\Throwable $e) {
            $payment->update(['status' => PractitionerPaymentStatus::Failed->value]);
            $this->activity->log(
                $practitioner->id, 'provider', 'payment', ActivitySeverity::Critical,
                'session_payout_failed', 'Session payment failed',
                $e->getMessage(),
                'practitioner_payment', $payment->id,
                $client->id, 'notification', $practitioner->id
            );
            throw new \RuntimeException('Session payment failed: ' . $e->getMessage());
        }

        $this->logSessionPaymentActivity($payment->fresh(), $practitioner, $client, stub: false);
        return $payment->fresh();
    }

    private function logSessionPaymentActivity(
        PractitionerPayment $payment,
        User $practitioner,
        User $client,
        bool $stub = false
    ): void {
        $amount    = '$' . number_format($payment->amount_cents / 100, 2);
        $statusNote = $stub ? ' (demo mode — no real charge)' : ' via Stripe';

        // Practitioner notification
        $this->activity->log(
            $practitioner->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_payment_received',
            'Session payment received',
            $amount . ' from ' . $client->display_name . $statusNote . '.',
            'practitioner_payment', $payment->id,
            $client->id, 'notification', $client->id
        );

        // Client log
        $this->activity->log(
            $client->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_payment_sent',
            'Session payment sent',
            $amount . ' sent to ' . $practitioner->display_name . $statusNote . '.',
            'practitioner_payment', $payment->id,
            $practitioner->id, 'log', $client->id
        );
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
