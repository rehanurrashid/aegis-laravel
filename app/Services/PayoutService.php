<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Enums\PractitionerPaymentKind;
use App\Enums\PractitionerPaymentStatus;
use App\Enums\ServiceSessionPaymentStatus;
use App\Events\Business\ContractCompleted;
use App\Events\Business\PayoutReleased;
use App\Models\BpContract;
use App\Models\BpMilestone;
use App\Models\BpPayout;
use App\Models\CsPayout;
use App\Models\PractitionerPayment;
use App\Models\PractitionerPaymentMethod;
use App\Models\ServiceSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\StripeClient;

/**
 * Handles Stripe Connect destination charges — Aegis never holds funds.
 *
 * All clinical session charges use the destination charge pattern:
 *   Client saved card (stripe_id + stripe_payment_method_id)
 *   → Aegis platform ($0 net — pass-through)
 *   → Provider Stripe Connect Express account (stripe_account_id)
 *
 * Two-charge flow per session:
 *   1. chargeSessionDeposit()  — 30% upfront at accept time (client must click Pay Deposit)
 *   2. chargeSessionBalance()  — 70% at session completion (client confirms session happened)
 *
 * Refund flow uses reverse_transfer: true so funds pull from provider's Connect
 * balance back to client card. Platform balance is pass-through (atomic).
 *
 * Demo detection: cus_demo_* / pm_demo_* / acct_demo_* → stub, no real Stripe calls.
 */
class PayoutService
{
    public function __construct(private ActivityService $activity) {}

    // ═══════════════════════════════════════════════════════════════════════════
    // CLINICAL SESSION — DEPOSIT (30%)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Charge the client 30% of the agreed session amount immediately.
     * Called when client clicks "Pay Deposit" after provider accepts request.
     *
     * Creates a PractitionerPayment record (kind=service_session_deposit).
     * Updates session: deposit_cents, deposit_charge_id, deposit_paid_at, payment_status='deposit_paid'.
     *
     * @throws \RuntimeException with user-facing message on guard failure or Stripe error
     */
    public function chargeSessionDeposit(ServiceSession $session, User $provider, User $client): PractitionerPayment
    {
        $agreedCents  = $session->agreed_amount_cents;
        $depositCents = $session->expected_deposit_cents;

        if ($depositCents <= 0) {
            throw new \RuntimeException('Session has no chargeable amount.');
        }

        $clientPm = PractitionerPaymentMethod::where('practitioner_id', $client->id)
            ->where('is_default', 1)
            ->first();

        // Create the payment record first so we have an ID to reference
        $payment = PractitionerPayment::create([
            'id'                   => 'pp_' . Str::lower(Str::random(12)),
            'session_id'           => $session->id,
            'practitioner_id'      => $provider->id,
            'payment_method_id'    => $clientPm?->id,
            'kind'                 => PractitionerPaymentKind::ServiceSessionDeposit->value,
            'amount_cents'         => $depositCents,
            'currency'             => 'USD',
            'status'               => PractitionerPaymentStatus::Pending->value,
            'payment_method_label' => $clientPm?->label ?? ($client->display_name . ' — saved card'),
            'stripe_charge_id'     => null,
            'paid_at'              => null,
        ]);

        // ── Demo / stub detection ─────────────────────────────────────────────
        if ($this->isDemo($client, $provider)) {
            $demoId = 'pi_demo_dep_' . Str::lower(Str::random(12));
            $payment->update([
                'status'                   => PractitionerPaymentStatus::Paid->value,
                'stripe_payment_intent_id' => $demoId,
                'paid_at'                  => now(),
            ]);
            $session->update([
                'deposit_cents'    => $depositCents,
                'deposit_charge_id'=> $demoId,
                'deposit_paid_at'  => now(),
                'payment_status'   => ServiceSessionPaymentStatus::DepositPaid->value,
            ]);
            $this->logDepositActivity($payment->fresh(), $session, $provider, $client, stub: true);
            return $payment->fresh();
        }

        // ── Guards ────────────────────────────────────────────────────────────
        $this->guardClientPaymentMethod($client);
        $this->guardProviderConnectAccount($provider);

        // ── Stub if Stripe not configured ─────────────────────────────────────
        if (!config('services.stripe.secret')) {
            $stubId = 'pi_stub_dep_' . Str::lower(Str::random(12));
            $payment->update([
                'status'                   => PractitionerPaymentStatus::Pending->value,
                'stripe_payment_intent_id' => $stubId,
            ]);
            $session->update([
                'deposit_cents'    => $depositCents,
                'deposit_charge_id'=> $stubId,
                'deposit_paid_at'  => now(),
                'payment_status'   => ServiceSessionPaymentStatus::DepositPaid->value,
            ]);
            $this->logDepositActivity($payment->fresh(), $session, $provider, $client, stub: true);
            return $payment->fresh();
        }

        // ── Live Stripe destination charge ────────────────────────────────────
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $intent = $stripe->paymentIntents->create([
                'amount'               => $depositCents,
                'currency'             => 'usd',
                'customer'             => $client->stripe_id,
                'payment_method'       => $client->stripe_payment_method_id,
                'confirm'              => true,
                'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                'transfer_data'        => ['destination' => $provider->stripe_account_id],
                'on_behalf_of'         => $provider->stripe_account_id,
                'description'          => 'Session deposit (30%) — Aegis',
                'metadata'             => [
                    'payment_id'      => $payment->id,
                    'session_id'      => $session->id,
                    'charge_type'     => 'session_deposit',
                    'practitioner_id' => $provider->id,
                    'client_id'       => $client->id,
                    'agreed_total'    => $agreedCents,
                ],
            ]);

            $isPaid = $intent->status === 'succeeded';
            $payment->update([
                'status'                   => $isPaid ? PractitionerPaymentStatus::Paid->value : PractitionerPaymentStatus::Pending->value,
                'stripe_payment_intent_id' => $intent->id,
                'paid_at'                  => $isPaid ? now() : null,
            ]);
            $session->update([
                'deposit_cents'    => $depositCents,
                'deposit_charge_id'=> $intent->id,
                'deposit_paid_at'  => $isPaid ? now() : null,
                'payment_status'   => $isPaid
                    ? ServiceSessionPaymentStatus::DepositPaid->value
                    : ServiceSessionPaymentStatus::Unpaid->value,
            ]);

        } catch (\Stripe\Exception\CardException $e) {
            $payment->update(['status' => PractitionerPaymentStatus::Failed->value]);
            throw new \RuntimeException('Card declined: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $payment->update(['status' => PractitionerPaymentStatus::Failed->value]);
            Log::error('[PayoutService] Session deposit failed', [
                'session_id' => $session->id,
                'error'      => $e->getMessage(),
            ]);
            throw new \RuntimeException('Deposit payment failed: ' . $e->getMessage());
        }

        $this->logDepositActivity($payment->fresh(), $session, $provider, $client, stub: false);
        return $payment->fresh();
    }


    // ═══════════════════════════════════════════════════════════════════════════
    // CLINICAL SESSION — UNIFIED PORTION CHARGE (Rev 4)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Rev 4: Unified session payment method.
     * Replaces the separate chargeSessionDeposit / chargeSessionBalance pattern.
     *
     * @param  string  $portion  'upfront' | 'completion'
     * @throws \RuntimeException on guard failure or Stripe error
     */
    public function chargeSessionPortion(ServiceSession $session, string $portion): PractitionerPayment
    {
        if (!in_array($portion, ['upfront', 'completion'], true)) {
            throw new \RuntimeException("Invalid portion '{$portion}'. Must be 'upfront' or 'completion'.");
        }

        $provider = $session->practitioner;
        $client   = $session->client;

        if ($portion === 'upfront') {
            $cents        = $session->upfront_cents ?? $session->expected_deposit_cents;
            $kindValue    = 'service_session_upfront';
            $demoPrefix   = 'pi_demo_up_';
            $stubPrefix   = 'pi_stub_up_';
            $description  = 'Session upfront payment — Aegis';
            $chargeType   = 'session_upfront';
            $legacyColumn = 'deposit';
        } else {
            $cents        = $session->completion_cents ?? $session->expected_balance_cents;
            $kindValue    = 'service_session_completion';
            $demoPrefix   = 'pi_demo_comp_';
            $stubPrefix   = 'pi_stub_comp_';
            $description  = 'Session completion payment — Aegis';
            $chargeType   = 'session_completion';
            $legacyColumn = 'balance';
        }

        if ($cents <= 0) {
            throw new \RuntimeException('Session has no chargeable amount for this portion.');
        }

        $clientPm = PractitionerPaymentMethod::where('practitioner_id', $client->id)
            ->where('is_default', 1)
            ->first();

        $payment = PractitionerPayment::create([
            'id'                   => 'pp_' . Str::lower(Str::random(12)),
            'session_id'           => $session->id,
            'practitioner_id'      => $provider->id,
            'payment_method_id'    => $clientPm?->id,
            'kind'                 => $kindValue,
            'amount_cents'         => $cents,
            'currency'             => 'USD',
            'status'               => PractitionerPaymentStatus::Pending->value,
            'payment_method_label' => $clientPm?->label ?? ($client->display_name . ' — saved card'),
            'stripe_charge_id'     => null,
            'paid_at'              => null,
        ]);

        // Determine next payment_status
        $nextPaymentStatus = $portion === 'upfront'
            ? ($session->payment_structure?->value === 'full_upfront'
                ? ServiceSessionPaymentStatus::Paid->value
                : ServiceSessionPaymentStatus::DepositPaid->value)
            : ServiceSessionPaymentStatus::Paid->value;

        $sessionUpdates = [
            "{$legacyColumn}_cents"     => $cents,
            "{$legacyColumn}_charge_id" => null,
            "{$legacyColumn}_paid_at"   => null,
        ];

        // ── Demo / stub detection ─────────────────────────────────────────────
        if ($this->isDemo($client, $provider)) {
            $demoId = $demoPrefix . Str::lower(Str::random(12));
            $payment->update([
                'status'                   => PractitionerPaymentStatus::Paid->value,
                'stripe_payment_intent_id' => $demoId,
                'paid_at'                  => now(),
            ]);
            $sessionUpdates["{$legacyColumn}_charge_id"] = $demoId;
            $sessionUpdates["{$legacyColumn}_paid_at"]   = now();
            $sessionUpdates['payment_status']            = $nextPaymentStatus;
            $session->update($sessionUpdates);
            $this->logPortionActivity($payment->fresh(), $session, $provider, $client, $portion, stub: true);
            return $payment->fresh();
        }

        // ── Guards ────────────────────────────────────────────────────────────
        $this->guardClientPaymentMethod($client);
        $this->guardProviderConnectAccount($provider);

        // ── Stub if Stripe not configured ─────────────────────────────────────
        if (!config('services.stripe.secret')) {
            $stubId = $stubPrefix . Str::lower(Str::random(12));
            $payment->update([
                'status'                   => PractitionerPaymentStatus::Pending->value,
                'stripe_payment_intent_id' => $stubId,
            ]);
            $sessionUpdates["{$legacyColumn}_charge_id"] = $stubId;
            $sessionUpdates["{$legacyColumn}_paid_at"]   = now();
            $sessionUpdates['payment_status']            = $nextPaymentStatus;
            $session->update($sessionUpdates);
            $this->logPortionActivity($payment->fresh(), $session, $provider, $client, $portion, stub: true);
            return $payment->fresh();
        }

        // ── Live Stripe destination charge ────────────────────────────────────
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $intent = $stripe->paymentIntents->create([
                'amount'               => $cents,
                'currency'             => 'usd',
                'customer'             => $client->stripe_id,
                'payment_method'       => $client->stripe_payment_method_id,
                'confirm'              => true,
                'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                'transfer_data'        => ['destination' => $provider->stripe_account_id],
                'on_behalf_of'         => $provider->stripe_account_id,
                'description'          => $description,
                'metadata'             => [
                    'payment_id'         => $payment->id,
                    'session_id'         => $session->id,
                    'charge_type'        => $chargeType,
                    'portion'            => $portion,
                    'payment_structure'  => $session->payment_structure?->value ?? 'split',
                    'upfront_percentage' => $session->upfront_percentage ?? 30,
                    'terms_source'       => $session->terms_source ?? 'provider_default',
                    'practitioner_id'    => $provider->id,
                    'client_id'          => $client->id,
                    'agreed_total'       => $session->agreed_amount_cents,
                ],
            ]);

            $isPaid = $intent->status === 'succeeded';
            $payment->update([
                'status'                   => $isPaid ? PractitionerPaymentStatus::Paid->value : PractitionerPaymentStatus::Pending->value,
                'stripe_payment_intent_id' => $intent->id,
                'paid_at'                  => $isPaid ? now() : null,
            ]);
            $sessionUpdates["{$legacyColumn}_charge_id"] = $intent->id;
            $sessionUpdates["{$legacyColumn}_paid_at"]   = $isPaid ? now() : null;
            $sessionUpdates['payment_status']            = $isPaid
                ? $nextPaymentStatus
                : ($portion === 'upfront' ? ServiceSessionPaymentStatus::Unpaid->value : ServiceSessionPaymentStatus::DepositPaid->value);
            $session->update($sessionUpdates);

        } catch (\Stripe\Exception\CardException $e) {
            $payment->update(['status' => PractitionerPaymentStatus::Failed->value]);
            throw new \RuntimeException('Card declined: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $payment->update(['status' => PractitionerPaymentStatus::Failed->value]);
            Log::error('[PayoutService] Session portion charge failed', [
                'session_id' => $session->id,
                'portion'    => $portion,
                'error'      => $e->getMessage(),
            ]);
            throw new \RuntimeException('Payment failed: ' . $e->getMessage());
        }

        $this->logPortionActivity($payment->fresh(), $session, $provider, $client, $portion, stub: false);
        return $payment->fresh();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CLINICAL SESSION — BALANCE (70%)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Charge the remaining 70% balance when the client confirms the session occurred.
     * Called by ServiceService::completeSession() after session is marked Completed.
     *
     * Creates a PractitionerPayment record (kind=service_session_balance).
     * Updates session: balance_cents, balance_charge_id, balance_paid_at, payment_status='paid'.
     *
     * @throws \RuntimeException with user-facing message on guard failure or Stripe error
     */
    public function chargeSessionBalance(ServiceSession $session, User $provider, User $client): PractitionerPayment
    {
        $depositCents = $session->deposit_cents ?? $session->expected_deposit_cents;
        $balanceCents = $session->agreed_amount_cents - $depositCents;

        if ($balanceCents <= 0) {
            throw new \RuntimeException('No balance remaining for this session.');
        }

        $clientPm = PractitionerPaymentMethod::where('practitioner_id', $client->id)
            ->where('is_default', 1)
            ->first();

        $payment = PractitionerPayment::create([
            'id'                   => 'pp_' . Str::lower(Str::random(12)),
            'session_id'           => $session->id,
            'practitioner_id'      => $provider->id,
            'payment_method_id'    => $clientPm?->id,
            'kind'                 => PractitionerPaymentKind::ServiceSessionBalance->value,
            'amount_cents'         => $balanceCents,
            'currency'             => 'USD',
            'status'               => PractitionerPaymentStatus::Pending->value,
            'payment_method_label' => $clientPm?->label ?? ($client->display_name . ' — saved card'),
            'stripe_charge_id'     => null,
            'paid_at'              => null,
        ]);

        // ── Demo / stub detection ─────────────────────────────────────────────
        if ($this->isDemo($client, $provider)) {
            $demoId = 'pi_demo_bal_' . Str::lower(Str::random(12));
            $payment->update([
                'status'                   => PractitionerPaymentStatus::Paid->value,
                'stripe_payment_intent_id' => $demoId,
                'paid_at'                  => now(),
            ]);
            $session->update([
                'balance_cents'    => $balanceCents,
                'balance_charge_id'=> $demoId,
                'balance_paid_at'  => now(),
                'payment_status'   => ServiceSessionPaymentStatus::Paid->value,
            ]);
            $this->logBalanceActivity($payment->fresh(), $session, $provider, $client, stub: true);
            return $payment->fresh();
        }

        // ── Guards ────────────────────────────────────────────────────────────
        $this->guardClientPaymentMethod($client);
        $this->guardProviderConnectAccount($provider);

        if (!config('services.stripe.secret')) {
            $stubId = 'pi_stub_bal_' . Str::lower(Str::random(12));
            $payment->update([
                'status'                   => PractitionerPaymentStatus::Pending->value,
                'stripe_payment_intent_id' => $stubId,
            ]);
            $session->update([
                'balance_cents'    => $balanceCents,
                'balance_charge_id'=> $stubId,
                'balance_paid_at'  => now(),
                'payment_status'   => ServiceSessionPaymentStatus::Paid->value,
            ]);
            $this->logBalanceActivity($payment->fresh(), $session, $provider, $client, stub: true);
            return $payment->fresh();
        }

        // ── Live Stripe destination charge ────────────────────────────────────
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $intent = $stripe->paymentIntents->create([
                'amount'               => $balanceCents,
                'currency'             => 'usd',
                'customer'             => $client->stripe_id,
                'payment_method'       => $client->stripe_payment_method_id,
                'confirm'              => true,
                'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                'transfer_data'        => ['destination' => $provider->stripe_account_id],
                'on_behalf_of'         => $provider->stripe_account_id,
                'description'          => 'Session balance (70%) — Aegis',
                'metadata'             => [
                    'payment_id'      => $payment->id,
                    'session_id'      => $session->id,
                    'charge_type'     => 'session_balance',
                    'practitioner_id' => $provider->id,
                    'client_id'       => $client->id,
                ],
            ]);

            $isPaid = $intent->status === 'succeeded';
            $payment->update([
                'status'                   => $isPaid ? PractitionerPaymentStatus::Paid->value : PractitionerPaymentStatus::Pending->value,
                'stripe_payment_intent_id' => $intent->id,
                'paid_at'                  => $isPaid ? now() : null,
            ]);
            $session->update([
                'balance_cents'    => $balanceCents,
                'balance_charge_id'=> $intent->id,
                'balance_paid_at'  => $isPaid ? now() : null,
                'payment_status'   => $isPaid
                    ? ServiceSessionPaymentStatus::Paid->value
                    : ServiceSessionPaymentStatus::DepositPaid->value,
            ]);

        } catch (\Stripe\Exception\CardException $e) {
            $payment->update(['status' => PractitionerPaymentStatus::Failed->value]);
            throw new \RuntimeException('Card declined: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $payment->update(['status' => PractitionerPaymentStatus::Failed->value]);
            Log::error('[PayoutService] Session balance charge failed', [
                'session_id' => $session->id,
                'error'      => $e->getMessage(),
            ]);
            throw new \RuntimeException('Balance payment failed: ' . $e->getMessage());
        }

        $this->logBalanceActivity($payment->fresh(), $session, $provider, $client, stub: false);
        return $payment->fresh();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CLINICAL SESSION — REFUND
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Issue a Stripe refund against a session charge PaymentIntent.
     *
     * CRITICAL: Always passes reverse_transfer: true so funds pull from the
     * provider's Connect account balance back to the client's card.
     * Aegis platform balance is a pass-through (atomic reversal).
     *
     * Returns the Stripe Refund ID on success, null on demo/stub.
     *
     * @throws \RuntimeException on Stripe error
     */
    public function refundSessionCharge(
        string $stripePaymentIntentId,
        int    $refundCents,
        array  $metadata = []
    ): ?string {
        // Demo/stub detection — no real Stripe calls
        if (
            str_starts_with($stripePaymentIntentId, 'pi_demo_') ||
            str_starts_with($stripePaymentIntentId, 'pi_stub_')
        ) {
            Log::info('[PayoutService] refund noop — demo/stub PaymentIntent', [
                'pi'     => $stripePaymentIntentId,
                'amount' => $refundCents,
            ]);
            return null;
        }

        if (!config('services.stripe.secret')) {
            Log::warning('[PayoutService] refund skipped — no Stripe secret configured');
            return null;
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $refund = $stripe->refunds->create([
                'payment_intent'  => $stripePaymentIntentId,
                'amount'          => $refundCents,
                // CRITICAL: pull funds from provider's Connect account,
                // not from Aegis platform balance
                'reverse_transfer'=> true,
                // Also refund any application fee collected (none for Aegis currently,
                // but included defensively in case fee collection is added later)
                'refund_application_fee' => false,
                'reason'          => 'requested_by_customer',
                'metadata'        => $metadata,
            ]);
            return $refund->id;
        } catch (\Throwable $e) {
            Log::error('[PayoutService] Stripe refund failed', [
                'pi'    => $stripePaymentIntentId,
                'amount'=> $refundCents,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Refund failed: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // EXISTING METHODS (unchanged — kept for compatibility)
    // ═══════════════════════════════════════════════════════════════════════════

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

    public function chargeProviderToBp(User $provider, User $bp, int $amountCents, string $currency = 'usd', array $meta = [], string $description = ''): array
    {
        $providerPm  = (string) ($provider->stripe_payment_method_id ?? '');
        $providerCus = (string) ($provider->stripe_id ?? '');
        $bpAccount   = (string) ($bp->stripe_account_id ?? '');

        $isDemoProvider = str_starts_with($providerCus, 'cus_demo_')
            || str_starts_with($providerPm, 'pm_demo_');
        $isDemoBp = str_starts_with($bpAccount, 'acct_demo_')
            || empty($bpAccount);

        // Also stub in sandbox/local when Stripe secret is a test key
        $stripeSecret  = (string) config('services.stripe.secret', '');
        $isSandbox     = str_starts_with($stripeSecret, 'sk_test_');

        if ($isDemoProvider || $isDemoBp || $isSandbox) {
            return ['stripe_payment_intent_id' => 'pi_demo_' . Str::lower(Str::random(16)), 'stripe_transfer_id' => null, 'status' => 'paid', 'stub' => true, 'stub_reason' => 'Demo/sandbox mode.'];
        }

        if (!$provider->stripe_id || !$provider->stripe_payment_method_id) {
            throw new \RuntimeException('No payment method on file. Please add a card in Settings → Billing before releasing payment.');
        }

        $bpAccount  = $bp->stripe_account_id;
        $isRealAcct = $bpAccount && preg_match('/^acct_[a-zA-Z0-9]{16,}$/', $bpAccount);
        if (!$isRealAcct) {
            throw new \RuntimeException('BP has not connected a Stripe account yet.');
        }

        if (!config('services.stripe.secret')) {
            return ['stripe_payment_intent_id' => 'pi_stub_' . Str::lower(Str::random(16)), 'stripe_transfer_id' => null, 'status' => 'pending', 'stub' => true, 'stub_reason' => 'Stripe not configured.'];
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $intent = $stripe->paymentIntents->create([
                'amount' => $amountCents, 'currency' => strtolower($currency),
                'customer' => $provider->stripe_id, 'payment_method' => $provider->stripe_payment_method_id,
                'confirm' => true, 'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                'transfer_data' => ['destination' => $bpAccount], 'on_behalf_of' => $bpAccount,
                'description' => $description ?: 'Aegis contract payment',
                'metadata' => array_merge($meta, ['provider_id' => $provider->id, 'bp_id' => $bp->id]),
            ]);
            return ['stripe_payment_intent_id' => $intent->id, 'stripe_transfer_id' => $intent->transfer_data->destination_payment ?? null, 'status' => $intent->status === 'succeeded' ? 'paid' : 'pending', 'stub' => false];
        } catch (\Stripe\Exception\CardException $e) {
            throw new \RuntimeException('Card declined: ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Payment failed: ' . $e->getMessage());
        }
    }

    public function chargeProviderToCs(User $provider, User $cs, int $amountCents, string $currency = 'usd', array $meta = [], string $description = ''): array
    {
        $isDemoProvider = str_starts_with((string) $provider->stripe_id, 'cus_demo_') || str_starts_with((string) $provider->stripe_payment_method_id, 'pm_demo_');
        $isDemoCs = str_starts_with((string) $cs->stripe_account_id, 'acct_demo_');

        if ($isDemoProvider || $isDemoCs) {
            return ['stripe_payment_intent_id' => 'pi_demo_' . Str::lower(Str::random(16)), 'stripe_transfer_id' => null, 'status' => 'paid', 'stub' => true, 'stub_reason' => 'Demo mode.'];
        }

        if (!$provider->stripe_id || !$provider->stripe_payment_method_id) {
            throw new \RuntimeException('No payment method on file. Please add a card in Settings → Billing before paying this invoice.');
        }

        $csAccount  = $cs->stripe_account_id;
        $isRealAcct = $csAccount && preg_match('/^acct_[a-zA-Z0-9]{16,}$/', $csAccount);
        if (!$isRealAcct) {
            throw new \RuntimeException('CS has not connected a Stripe account yet.');
        }

        if (!config('services.stripe.secret')) {
            return ['stripe_payment_intent_id' => 'pi_stub_' . Str::lower(Str::random(16)), 'stripe_transfer_id' => null, 'status' => 'pending', 'stub' => true, 'stub_reason' => 'Stripe not configured.'];
        }

        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $intent = $stripe->paymentIntents->create([
                'amount' => $amountCents, 'currency' => strtolower($currency),
                'customer' => $provider->stripe_id, 'payment_method' => $provider->stripe_payment_method_id,
                'confirm' => true, 'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                'transfer_data' => ['destination' => $csAccount], 'on_behalf_of' => $csAccount,
                'description' => $description ?: 'Aegis CS invoice payment',
                'metadata' => array_merge($meta, ['provider_id' => $provider->id, 'cs_id' => $cs->id]),
            ]);
            return ['stripe_payment_intent_id' => $intent->id, 'stripe_transfer_id' => $intent->transfer_data->destination_payment ?? null, 'status' => $intent->status === 'succeeded' ? 'paid' : 'pending', 'stub' => false];
        } catch (\Stripe\Exception\CardException $e) {
            throw new \RuntimeException('Card declined: ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Payment failed: ' . $e->getMessage());
        }
    }

    public function endContractAndRelease(BpContract $contract, User $provider): BpPayout
    {
        $bp     = User::findOrFail($contract->bp_id);
        $result = $this->chargeProviderToBp($provider, $bp, $contract->total_value_cents, 'usd', ['contract_id' => $contract->id, 'type' => 'one_time'], 'Contract payment: ' . $contract->title);
        $payout = BpPayout::create(['id' => 'bpo_' . Str::lower(Str::random(12)), 'bp_id' => $contract->bp_id, 'provider_id' => $provider->id, 'contract_id' => $contract->id, 'amount_cents' => $contract->total_value_cents, 'currency' => 'USD', 'status' => $result['status'], 'stripe_payment_intent_id' => $result['stripe_payment_intent_id'], 'stripe_transfer_id' => $result['stripe_transfer_id'], 'released_at' => now(), 'description' => 'One-time contract payment: ' . $contract->title]);
        $contract->update(['status' => 'completed', 'ended_at' => now(), 'completed_at' => now()]);
        $this->logPaymentActivity($provider, $bp, $payout, $contract->title, $contract->total_value_cents, $result);
        event(new PayoutReleased($payout->fresh()));
        event(new ContractCompleted($contract->fresh()));
        return $payout;
    }

    public function payMilestone(BpMilestone $milestone, User $provider): BpPayout
    {
        $statusVal = $milestone->status instanceof \BackedEnum ? $milestone->status->value : (string) $milestone->status;
        if ($statusVal !== 'approved') throw new \RuntimeException('Milestone must be approved before payment.');
        $contract = $milestone->contract;
        $bp       = User::findOrFail($contract->bp_id);
        $result   = $this->chargeProviderToBp($provider, $bp, $milestone->amount_cents, 'usd', ['contract_id' => $contract->id, 'milestone_id' => $milestone->id], 'Milestone payment: ' . $milestone->title);
        $payout   = BpPayout::create(['id' => 'bpo_' . Str::lower(Str::random(12)), 'bp_id' => $contract->bp_id, 'provider_id' => $provider->id, 'contract_id' => $contract->id, 'milestone_id' => $milestone->id, 'amount_cents' => $milestone->amount_cents, 'currency' => 'USD', 'status' => $result['status'], 'stripe_payment_intent_id' => $result['stripe_payment_intent_id'], 'stripe_transfer_id' => $result['stripe_transfer_id'], 'released_at' => now(), 'description' => 'Milestone payment: ' . $milestone->title]);
        $milestone->update(['status' => 'paid', 'paid_at' => now(), 'payout_id' => $payout->id]);
        $this->logPaymentActivity($provider, $bp, $payout, $milestone->title, $milestone->amount_cents, $result, 'milestone');
        event(new PayoutReleased($payout->fresh()));
        return $payout;
    }

    /**
     * Legacy single-charge session payout (kept for backwards compat with existing sessions).
     * New sessions use chargeSessionDeposit() + chargeSessionBalance() instead.
     */
    public function releaseServiceSessionPayout(PractitionerPayment $payment, User $practitioner, User $client): PractitionerPayment
    {
        if ($this->isDemo($client, $practitioner)) {
            $payment->update(['status' => PractitionerPaymentStatus::Paid->value, 'stripe_payment_intent_id' => 'pi_demo_' . Str::lower(Str::random(16)), 'paid_at' => now()]);
            $this->logSessionPaymentActivity($payment, $practitioner, $client, stub: true);
            return $payment->fresh();
        }

        $this->guardClientPaymentMethod($client);
        $this->guardProviderConnectAccount($practitioner);

        if (!config('services.stripe.secret')) {
            $payment->update(['status' => PractitionerPaymentStatus::Pending->value, 'stripe_payment_intent_id' => null]);
            return $payment->fresh();
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $intent = $stripe->paymentIntents->create([
                'amount' => $payment->amount_cents, 'currency' => strtolower($payment->currency ?? 'usd'),
                'customer' => $client->stripe_id, 'payment_method' => $client->stripe_payment_method_id,
                'confirm' => true, 'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                'transfer_data' => ['destination' => $practitioner->stripe_account_id],
                'on_behalf_of' => $practitioner->stripe_account_id,
                'description' => 'Session payment — Aegis',
                'metadata' => ['payment_id' => $payment->id, 'practitioner_id' => $practitioner->id, 'client_id' => $client->id],
            ]);
            $isPaid = $intent->status === 'succeeded';
            $payment->update(['status' => $isPaid ? PractitionerPaymentStatus::Paid->value : PractitionerPaymentStatus::Pending->value, 'stripe_payment_intent_id' => $intent->id, 'paid_at' => $isPaid ? now() : null]);
        } catch (\Throwable $e) {
            $payment->update(['status' => PractitionerPaymentStatus::Failed->value]);
            $this->activity->log($practitioner->id, 'provider', 'payment', ActivitySeverity::Critical, 'session_payout_failed', 'Session payment failed', $e->getMessage(), 'practitioner_payment', $payment->id, $client->id, 'notification', $practitioner->id);
            throw new \RuntimeException('Session payment failed: ' . $e->getMessage());
        }

        $this->logSessionPaymentActivity($payment->fresh(), $practitioner, $client, stub: false);
        return $payment->fresh();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    private function isDemo(User $client, User $provider): bool
    {
        return str_starts_with((string) $client->stripe_id, 'cus_demo_')
            || str_starts_with((string) $client->stripe_payment_method_id, 'pm_demo_')
            || str_starts_with((string) $provider->stripe_account_id, 'acct_demo_');
    }

    /** @throws \RuntimeException */
    private function guardClientPaymentMethod(User $client): void
    {
        if (!$client->stripe_id || !$client->stripe_payment_method_id) {
            throw new \RuntimeException(
                'No payment method on file. Add a card in Settings → Billing before paying.'
            );
        }
    }

    /** @throws \RuntimeException */
    private function guardProviderConnectAccount(User $provider): void
    {
        $account = $provider->stripe_account_id;
        if (!$account || !preg_match('/^acct_[a-zA-Z0-9]{16,}$/', $account)) {
            throw new \RuntimeException(
                'Provider has not connected a Stripe account. Payment will be held until they complete setup.'
            );
        }
    }

    private function logPortionActivity(PractitionerPayment $payment, ServiceSession $session, User $provider, User $client, string $portion, bool $stub): void
    {
        $amount   = '$' . number_format($payment->amount_cents / 100, 2);
        $note     = $stub ? ' (demo)' : ' via Stripe';
        $svcTitle = $session->service?->title ?? 'session';

        if ($portion === 'upfront') {
            $this->activity->log(
                $provider->id, 'provider', 'payment', ActivitySeverity::Info,
                'upfront_paid',
                'Upfront payment received — ' . $svcTitle,
                $amount . ' upfront payment from ' . $client->display_name . $note . '.',
                'service_session', $session->id, $client->id, 'notification', $client->id
            );
            $this->activity->log(
                $client->id, 'provider', 'payment', ActivitySeverity::Info,
                'upfront_paid',
                'Upfront payment sent — ' . $svcTitle,
                $amount . ' sent to ' . $provider->display_name . $note . '.',
                'service_session', $session->id, $provider->id, 'log', $client->id
            );
        } else {
            $this->activity->log(
                $provider->id, 'provider', 'payment', ActivitySeverity::Info,
                'completion_paid',
                'Session fully paid — ' . $svcTitle,
                $amount . ' completion payment from ' . $client->display_name . $note . '.',
                'service_session', $session->id, $client->id, 'notification', $client->id
            );
            $this->activity->log(
                $client->id, 'provider', 'payment', ActivitySeverity::Info,
                'completion_paid',
                'Session payment complete — ' . $svcTitle,
                $amount . ' sent to ' . $provider->display_name . $note . '. Session payment complete.',
                'service_session', $session->id, $provider->id, 'log', $client->id
            );
        }
    }

    private function logDepositActivity(PractitionerPayment $payment, ServiceSession $session, User $provider, User $client, bool $stub): void
    {
        $amount    = '$' . number_format($payment->amount_cents / 100, 2);
        $note      = $stub ? ' (demo)' : ' via Stripe';
        $svcTitle  = $session->service?->title ?? 'session';

        $this->activity->log(
            $provider->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_deposit_received',
            'Session deposit received — ' . $svcTitle,
            $amount . ' deposit from ' . $client->display_name . $note . '. Balance due on completion.',
            'service_session', $session->id, $client->id, 'notification', $client->id
        );
        $this->activity->log(
            $client->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_deposit_paid',
            'Deposit paid — ' . $svcTitle,
            $amount . ' deposit sent to ' . $provider->display_name . $note . '. Balance due after session.',
            'service_session', $session->id, $provider->id, 'log', $client->id
        );
    }

    private function logBalanceActivity(PractitionerPayment $payment, ServiceSession $session, User $provider, User $client, bool $stub): void
    {
        $amount   = '$' . number_format($payment->amount_cents / 100, 2);
        $note     = $stub ? ' (demo)' : ' via Stripe';
        $svcTitle = $session->service?->title ?? 'session';

        $this->activity->log(
            $provider->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_balance_received',
            'Session fully paid — ' . $svcTitle,
            $amount . ' balance from ' . $client->display_name . $note . '. Session complete.',
            'service_session', $session->id, $client->id, 'notification', $client->id
        );
        $this->activity->log(
            $client->id, 'provider', 'payment', ActivitySeverity::Info,
            'session_balance_paid',
            'Session payment complete — ' . $svcTitle,
            $amount . ' sent to ' . $provider->display_name . $note . '. Full payment received.',
            'service_session', $session->id, $provider->id, 'log', $client->id
        );
    }

    private function logSessionPaymentActivity(PractitionerPayment $payment, User $practitioner, User $client, bool $stub): void
    {
        $amount     = '$' . number_format($payment->amount_cents / 100, 2);
        $statusNote = $stub ? ' (demo mode — no real charge)' : ' via Stripe';
        $this->activity->log($practitioner->id, 'provider', 'payment', ActivitySeverity::Info, 'session_payment_received', 'Session payment received', $amount . ' from ' . $client->display_name . $statusNote . '.', 'practitioner_payment', $payment->id, $client->id, 'notification', $client->id);
        $this->activity->log($client->id, 'provider', 'payment', ActivitySeverity::Info, 'session_payment_sent', 'Session payment sent', $amount . ' sent to ' . $practitioner->display_name . $statusNote . '.', 'practitioner_payment', $payment->id, $practitioner->id, 'log', $client->id);
    }

    private function logPaymentActivity(User $provider, User $bp, BpPayout $payout, string $title, int $amountCents, array $result, string $type = 'contract'): void
    {
        $amountLabel = '$' . number_format($amountCents / 100, 2);
        $linkType    = $type === 'milestone' ? 'bp_milestone' : 'bp_payout';
        $linkId      = $type === 'milestone' ? ($payout->milestone_id ?? $payout->id) : $payout->id;
        $statusNote  = $result['stub'] ?? false ? ' (payment queued — BP Stripe account pending verification)' : ' via Stripe';
        $this->activity->log($provider->id, 'provider', 'job_postings', ActivitySeverity::Info, $type === 'milestone' ? 'milestone_paid' : 'contract_payment_released', ucfirst($type) . ' payment released: ' . $title, $amountLabel . ' charged to your card and sent to ' . $bp->display_name . $statusNote . '.', $linkType, $linkId, null, 'log', $provider->id);
        $this->activity->log($bp->id, 'business_partner', 'job_postings', ActivitySeverity::Info, $type === 'milestone' ? 'milestone_payment_received' : 'contract_payment_received', ucfirst($type) . ' payment received: ' . $title, $amountLabel . ' has been transferred to your connected Stripe account.', $linkType, $linkId, $provider->id, 'notification', $provider->id);
    }
}
