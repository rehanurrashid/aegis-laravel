<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Enums\ContractStatus;
use App\Enums\MilestoneStatus;
use App\Events\Business\ContractFullyFunded;
use App\Events\Business\EscrowFunded;
use App\Events\Business\MilestoneAutoReleased;
use App\Events\Business\MilestoneReleased;
use App\Events\Business\MilestoneRefunded;
use App\Models\BpContract;
use App\Models\BpEscrowLedger;
use App\Models\BpMilestone;
use App\Models\BpPayout;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\StripeClient;

/**
 * EscrowService — Aegis holds funds between Provider and BP.
 *
 * Stripe pattern: SEPARATE CHARGES + TRANSFERS
 *   - fundMilestone()   → PaymentIntent WITHOUT transfer_data (lands in Aegis platform balance)
 *   - releaseMilestone()→ Transfer from Aegis balance to BP Connect account
 *   - refundMilestone() → Refund against original PaymentIntent (returns to provider card)
 *
 * This is the only Stripe pattern supporting indefinite escrow hold time.
 * (Manual capture expires after 7 days — unusable for multi-week milestones.)
 *
 * Every operation writes to bp_escrow_ledger for immutable audit trail.
 * All Stripe calls include idempotency keys to prevent double-charges.
 */
class EscrowService
{
    public function __construct(private ActivityService $activity) {}

    // ═══════════════════════════════════════════════════════════════════════
    // FUND
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Fund a single milestone into Aegis escrow.
     * Charges provider's saved payment method; funds land in Aegis platform balance.
     * Sets milestone status → funded, contract status → active (if pending_funding).
     */
    public function fundMilestone(BpMilestone $milestone, User $provider): BpPayout
    {
        $contract = $milestone->contract()->with(['bp:id,display_name,stripe_account_id'])->firstOrFail();

        $this->guardProviderPaymentMethod($provider);
        $this->guardBpConnectAccount($contract->bp);
        $this->guardMilestoneNotAlreadyFunded($milestone);

        return DB::transaction(function () use ($milestone, $contract, $provider) {
            $amountCents  = (int) $milestone->amount_cents;
            $idempotKey   = 'escrow_fund_ms_' . $milestone->id;
            $transferGroup = $contract->transfer_group ?? $this->ensureTransferGroup($contract);

            $stripeResult = $this->chargeToEscrow(
                $provider,
                $amountCents,
                $idempotKey,
                $transferGroup,
                "Aegis escrow: {$contract->title} / {$milestone->title}",
                ['contract_id' => $contract->id, 'milestone_id' => $milestone->id, 'type' => 'escrow_fund'],
            );

            // Update milestone
            $milestone->update([
                'status'           => MilestoneStatus::Funded->value,
                'funded_at'        => now(),
                'funded_cents'     => $amountCents,
                'escrow_intent_id' => $stripeResult['payment_intent_id'],
                'escrow_charge_id' => $stripeResult['charge_id'],
            ]);

            // Update contract escrow total + activate if was pending_funding
            $contractUpdate = [
                'escrow_funded_cents' => $contract->escrow_funded_cents + $amountCents,
            ];
            $contractStatus = $contract->status instanceof ContractStatus
                ? $contract->status->value
                : (string) $contract->status;
            if ($contractStatus === ContractStatus::PendingFunding->value) {
                $contractUpdate['status'] = ContractStatus::Active->value;
            }
            $contract->update($contractUpdate);

            // Create payout record (tracks the charge; status = pending until released)
            $payout = BpPayout::create([
                'id'                     => 'bpo_' . Str::lower(Str::random(12)),
                'bp_id'                  => $contract->bp_id,
                'provider_id'            => $provider->id,
                'contract_id'            => $contract->id,
                'milestone_id'           => $milestone->id,
                'amount_cents'           => $amountCents,
                'status'                 => 'pending',
                'description'            => "Escrow funded: {$milestone->title}",
                'stripe_payment_intent_id' => $stripeResult['payment_intent_id'],
                'idempotency_key'        => $idempotKey,
            ]);

            // Ledger entry
            $this->writeLedger('fund', $contract, $amountCents, $provider, $contract->bp,
                $stripeResult['payment_intent_id'], 'payment_intent',
                "Funded escrow for milestone: {$milestone->title}", $provider->id, $milestone);

            // Activity
            $this->logFund($contract, $milestone, $provider, $amountCents);

            event(new EscrowFunded($contract->fresh(), $provider, $amountCents, $milestone->fresh()));

            return $payout;
        });
    }

    /**
     * Fund entire contract value upfront (full_upfront funding mode).
     * Sets all milestone statuses → funded.
     */
    public function fundContract(BpContract $contract, User $provider): BpPayout
    {
        $contract->load(['bp:id,display_name,stripe_account_id', 'milestones']);

        $this->guardProviderPaymentMethod($provider);
        $this->guardBpConnectAccount($contract->bp);

        $amountCents = (int) $contract->total_value_cents;
        if ($amountCents <= 0) {
            throw new \RuntimeException('Contract has no value to fund.');
        }

        return DB::transaction(function () use ($contract, $provider, $amountCents) {
            $idempotKey    = 'escrow_fund_contract_' . $contract->id;
            $transferGroup = $this->ensureTransferGroup($contract);

            $stripeResult = $this->chargeToEscrow(
                $provider,
                $amountCents,
                $idempotKey,
                $transferGroup,
                "Aegis escrow: {$contract->title} (full contract)",
                ['contract_id' => $contract->id, 'type' => 'escrow_fund_full'],
            );

            // Mark all milestones as funded
            $contract->milestones()->update([
                'status'           => MilestoneStatus::Funded->value,
                'funded_at'        => now(),
                'funded_cents'     => DB::raw('amount_cents'),
                'escrow_intent_id' => $stripeResult['payment_intent_id'],
                'escrow_charge_id' => $stripeResult['charge_id'],
            ]);

            // Update contract
            $contract->update([
                'status'              => ContractStatus::Active->value,
                'escrow_funded_cents' => $amountCents,
            ]);

            $payout = BpPayout::create([
                'id'                     => 'bpo_' . Str::lower(Str::random(12)),
                'bp_id'                  => $contract->bp_id,
                'provider_id'            => $provider->id,
                'contract_id'            => $contract->id,
                'milestone_id'           => null,
                'amount_cents'           => $amountCents,
                'status'                 => 'pending',
                'description'            => "Full escrow funded: {$contract->title}",
                'stripe_payment_intent_id' => $stripeResult['payment_intent_id'],
                'idempotency_key'        => $idempotKey,
            ]);

            $this->writeLedger('fund', $contract, $amountCents, $provider, $contract->bp,
                $stripeResult['payment_intent_id'], 'payment_intent',
                "Full contract escrow funded: {$contract->title}", $provider->id);

            $bp = $contract->bp;
            $this->activity->log(
                $provider->id, 'provider', 'job_postings', ActivitySeverity::Info,
                'escrow_funded', "Contract funded: {$contract->title}",
                'All milestones are now funded. Work can begin.',
                'bp_contract', $contract->id, $contract->bp_id,
                'log', $provider->id
            );
            $this->activity->log(
                $contract->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Info,
                'escrow_funded', "{$provider->display_name} funded the contract",
                'All milestones are funded. You may begin work immediately.',
                'bp_contract', $contract->id, $provider->id,
                'notification', $provider->id
            );

            event(new EscrowFunded($contract->fresh(), $provider, $amountCents, null));
            event(new ContractFullyFunded($contract->fresh(), $provider));

            return $payout;
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RELEASE
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Transfer milestone funds from Aegis platform balance to BP Connect.
     * Called when provider approves a submission OR auto-release timer fires.
     * $approver can be a User (provider) or null (system auto-release).
     */
    public function releaseMilestone(BpMilestone $milestone, ?User $approver = null): BpPayout
    {
        $contract = $milestone->contract()->with(['bp:id,display_name,stripe_account_id', 'practitioner:id,display_name'])->firstOrFail();
        $bp       = $contract->bp;

        $this->guardBpConnectAccount($bp);

        $milestoneStatus = $milestone->status instanceof MilestoneStatus
            ? $milestone->status->value
            : (string) $milestone->status;

        if (!in_array($milestoneStatus, [
            MilestoneStatus::Approved->value,
            MilestoneStatus::Submitted->value,   // auto-release path
        ], true)) {
            throw new \RuntimeException("Milestone must be approved or submitted to release. Current: {$milestoneStatus}");
        }

        return DB::transaction(function () use ($milestone, $contract, $bp, $approver) {
            $amountCents   = (int) $milestone->funded_cents ?: (int) $milestone->amount_cents;
            $idempotKey    = 'escrow_release_ms_' . $milestone->id;
            $transferGroup = $contract->transfer_group ?? $this->ensureTransferGroup($contract);
            $isAutoRelease = $approver === null;
            $actorId       = $approver?->id ?? 'system';

            $transferId = $this->transferToBp($bp, $amountCents, $idempotKey, $transferGroup,
                "Aegis release: {$contract->title} / {$milestone->title}",
                ['contract_id' => $contract->id, 'milestone_id' => $milestone->id]);

            // Update milestone
            $milestone->update([
                'status'         => MilestoneStatus::Released->value,
                'released_at'    => now(),
                'released_cents' => $amountCents,
                'approved_at'    => $milestone->approved_at ?? now(),
                'paid_at'        => now(),
                'transfer_id'    => $transferId,
            ]);

            // Update contract escrow totals
            $contract->update([
                'escrow_released_cents' => $contract->escrow_released_cents + $amountCents,
            ]);

            // Update payout record
            $payout = BpPayout::where('milestone_id', $milestone->id)
                               ->where('status', 'pending')
                               ->first();

            if ($payout) {
                $payout->update([
                    'status'            => 'paid',
                    'paid_at'           => now(),
                    'released_at'       => now(),
                    'stripe_transfer_id'=> $transferId,
                ]);
            } else {
                // Auto-release may not have a prior payout row if funded via full-contract path
                $payout = BpPayout::create([
                    'id'                => 'bpo_' . Str::lower(Str::random(12)),
                    'bp_id'             => $bp->id,
                    'provider_id'       => $contract->practitioner_id,
                    'contract_id'       => $contract->id,
                    'milestone_id'      => $milestone->id,
                    'amount_cents'      => $amountCents,
                    'status'            => 'paid',
                    'paid_at'           => now(),
                    'released_at'       => now(),
                    'description'       => ($isAutoRelease ? '[Auto-released] ' : '') . "Milestone payment: {$milestone->title}",
                    'stripe_transfer_id'=> $transferId,
                    'idempotency_key'   => $idempotKey,
                ]);
            }

            $this->writeLedger('release', $contract, $amountCents, $contract->practitioner, $bp,
                $transferId, 'transfer',
                ($isAutoRelease ? '[Auto] ' : '') . "Released: {$milestone->title}",
                $actorId, $milestone);

            $this->logRelease($contract, $milestone, $approver, $amountCents, $isAutoRelease);

            if ($isAutoRelease) {
                event(new MilestoneAutoReleased($milestone->fresh(), $payout));
            } else {
                event(new MilestoneReleased($milestone->fresh(), $payout, $approver));
            }

            return $payout;
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REFUND
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Refund milestone escrow back to provider's card.
     * Requires the PaymentIntent ID stored on the milestone.
     * $cents can be partial (split resolution) or full (= milestone.funded_cents).
     */
    public function refundMilestone(BpMilestone $milestone, int $cents, User $actor, string $reason): void
    {
        $contract = $milestone->contract()->with(['practitioner:id,display_name', 'bp:id,display_name'])->firstOrFail();

        if (!$milestone->escrow_intent_id) {
            throw new \RuntimeException('No escrow PaymentIntent found. Cannot refund.');
        }

        $maxRefundable = (int) $milestone->funded_cents - (int) $milestone->refunded_cents;
        if ($cents > $maxRefundable) {
            throw new \RuntimeException("Requested refund ({$cents}) exceeds refundable amount ({$maxRefundable}).");
        }

        DB::transaction(function () use ($milestone, $contract, $cents, $actor, $reason) {
            $refundId = $this->issueRefund($milestone->escrow_intent_id, $cents, [
                'contract_id'  => $contract->id,
                'milestone_id' => $milestone->id,
                'actor_id'     => $actor->id,
                'reason'       => substr($reason, 0, 200),
            ]);

            $totalRefunded  = (int) $milestone->refunded_cents + $cents;
            $fullyRefunded  = $totalRefunded >= (int) $milestone->funded_cents;
            $newStatus      = $fullyRefunded ? MilestoneStatus::Refunded->value : $milestone->status->value;

            $milestone->update([
                'status'          => $newStatus,
                'refunded_at'     => now(),
                'refunded_cents'  => $totalRefunded,
                'refund_stripe_id'=> $refundId,
            ]);

            $contract->update([
                'escrow_refunded_cents' => $contract->escrow_refunded_cents + $cents,
            ]);

            // Update payout record
            $payout = BpPayout::where('milestone_id', $milestone->id)->latest()->first();
            if ($payout) {
                $payout->update([
                    'refunded_at'     => now(),
                    'refunded_cents'  => $totalRefunded,
                    'refund_stripe_id'=> $refundId,
                ]);
            }

            $ledgerKind = $fullyRefunded ? 'refund' : 'split_refund';
            $this->writeLedger($ledgerKind, $contract, $cents, $contract->practitioner, $contract->bp,
                $refundId, 'refund', "Refund: {$reason}", $actor->id, $milestone);

            $this->logRefund($contract, $milestone, $actor, $cents, $reason);

            event(new MilestoneRefunded($milestone->fresh(), $actor, $cents, $reason));
        });
    }

    /**
     * Split resolution: partially release to BP + partially refund to provider.
     * Used by admin when resolving disputes with a compromise outcome.
     */
    public function splitResolution(
        BpMilestone $milestone,
        int $releaseCents,
        int $refundCents,
        User $admin,
    ): void {
        $funded = (int) $milestone->funded_cents;
        if ($releaseCents + $refundCents > $funded) {
            throw new \RuntimeException('Split amounts exceed funded escrow.');
        }

        // Release portion first, then refund portion
        if ($releaseCents > 0) {
            // Temporarily mark as approved so releaseMilestone passes the guard
            $milestone->update(['status' => MilestoneStatus::Approved->value]);
            $this->releaseMilestone($milestone, $admin);
        }
        if ($refundCents > 0) {
            $this->refundMilestone($milestone->fresh(), $refundCents, $admin, 'Dispute split resolution');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CANCEL ESCROW
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Cancel all unfunded/funded milestones on a contract being cancelled.
     * Refunds all escrow-held funds back to provider.
     * Called from ContractService::cancel().
     */
    public function cancelContractEscrow(BpContract $contract, User $actor, string $reason): void
    {
        $milestones = $contract->milestones()
            ->whereIn('status', [
                MilestoneStatus::Funded->value,
                MilestoneStatus::InProgress->value,
                MilestoneStatus::Submitted->value,
                MilestoneStatus::RevisionRequested->value,
                MilestoneStatus::Approved->value,
            ])
            ->get();

        foreach ($milestones as $ms) {
            if ($ms->escrow_intent_id && (int) $ms->funded_cents > (int) $ms->refunded_cents) {
                $refundable = (int) $ms->funded_cents - (int) $ms->refunded_cents;
                $this->refundMilestone($ms, $refundable, $actor, "Contract cancelled: {$reason}");
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // BALANCE QUERY
    // ═══════════════════════════════════════════════════════════════════════

    public function getContractBalance(BpContract $contract): array
    {
        return [
            'total_cents'    => (int) $contract->total_value_cents,
            'funded_cents'   => (int) $contract->escrow_funded_cents,
            'released_cents' => (int) $contract->escrow_released_cents,
            'refunded_cents' => (int) $contract->escrow_refunded_cents,
            'held_cents'     => $contract->escrowHeldCents(),
            'unfunded_cents' => max(0, (int) $contract->total_value_cents - (int) $contract->escrow_funded_cents),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE — STRIPE OPERATIONS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Charge provider card; funds land in Aegis platform balance (no transfer_data).
     * Returns ['payment_intent_id', 'charge_id'].
     */
    private function chargeToEscrow(
        User   $provider,
        int    $amountCents,
        string $idempotKey,
        string $transferGroup,
        string $description,
        array  $metadata = [],
    ): array {
        // Demo/stub detection
        if (
            str_starts_with((string) $provider->stripe_id, 'cus_demo_') ||
            str_starts_with((string) $provider->stripe_payment_method_id, 'pm_demo_')
        ) {
            $demoId = 'pi_demo_' . Str::lower(Str::random(16));
            Log::info('[EscrowService] Demo escrow fund', ['key' => $idempotKey, 'amount' => $amountCents]);
            return ['payment_intent_id' => $demoId, 'charge_id' => null, 'stub' => true];
        }

        if (!config('services.stripe.secret')) {
            $stubId = 'pi_stub_' . Str::lower(Str::random(16));
            Log::warning('[EscrowService] Stripe not configured — stub escrow fund');
            return ['payment_intent_id' => $stubId, 'charge_id' => null, 'stub' => true];
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $intent = $stripe->paymentIntents->create([
                'amount'               => $amountCents,
                'currency'             => 'usd',
                'customer'             => $provider->stripe_id,
                'payment_method'       => $provider->stripe_payment_method_id,
                'confirm'              => true,
                'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                // NO transfer_data → funds land in Aegis platform balance (escrow)
                'transfer_group'       => $transferGroup,
                'description'          => $description,
                'metadata'             => array_merge($metadata, ['provider_id' => $provider->id]),
            ], ['idempotency_key' => $idempotKey]);

            $chargeId = $intent->latest_charge ?? null;

            return [
                'payment_intent_id' => $intent->id,
                'charge_id'         => $chargeId,
                'stub'              => false,
            ];
        } catch (\Stripe\Exception\CardException $e) {
            throw new \RuntimeException('Card declined: ' . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[EscrowService] escrow charge failed', ['error' => $e->getMessage(), 'key' => $idempotKey]);
            throw new \RuntimeException('Escrow payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Transfer from Aegis platform balance to BP Connect account.
     * Uses same transfer_group as the original charge for Stripe correlation.
     */
    private function transferToBp(
        User   $bp,
        int    $amountCents,
        string $idempotKey,
        string $transferGroup,
        string $description,
        array  $metadata = [],
    ): ?string {
        $releaseIdempotKey = $idempotKey . '_release';

        if (str_starts_with((string) $bp->stripe_account_id, 'acct_demo_')) {
            $demoId = 'tr_demo_' . Str::lower(Str::random(16));
            Log::info('[EscrowService] Demo transfer to BP', ['key' => $releaseIdempotKey, 'amount' => $amountCents]);
            return $demoId;
        }

        if (!config('services.stripe.secret')) {
            return 'tr_stub_' . Str::lower(Str::random(16));
        }

        try {
            $stripe   = new StripeClient(config('services.stripe.secret'));
            $transfer = $stripe->transfers->create([
                'amount'         => $amountCents,
                'currency'       => 'usd',
                'destination'    => $bp->stripe_account_id,
                'transfer_group' => $transferGroup,
                'description'    => $description,
                'metadata'       => $metadata,
            ], ['idempotency_key' => $releaseIdempotKey]);

            return $transfer->id;
        } catch (\Throwable $e) {
            Log::error('[EscrowService] transfer to BP failed', ['error' => $e->getMessage(), 'key' => $releaseIdempotKey]);
            throw new \RuntimeException('Release transfer failed: ' . $e->getMessage());
        }
    }

    /**
     * Issue a refund against an existing PaymentIntent.
     * Funds return directly to provider's card from Aegis platform balance.
     * No reverse_transfer needed (unlike destination charges) because funds
     * never left the platform balance.
     */
    private function issueRefund(string $paymentIntentId, int $cents, array $metadata = []): ?string
    {
        if (
            str_starts_with($paymentIntentId, 'pi_demo_') ||
            str_starts_with($paymentIntentId, 'pi_stub_')
        ) {
            Log::info('[EscrowService] Demo refund noop', ['pi' => $paymentIntentId, 'amount' => $cents]);
            return 're_demo_' . Str::lower(Str::random(16));
        }

        if (!config('services.stripe.secret')) {
            return 're_stub_' . Str::lower(Str::random(16));
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $refund = $stripe->refunds->create([
                'payment_intent'         => $paymentIntentId,
                'amount'                 => $cents,
                'reason'                 => 'requested_by_customer',
                // No reverse_transfer needed — funds are in Aegis platform balance
                'refund_application_fee' => false,
                'metadata'               => $metadata,
            ]);

            return $refund->id;
        } catch (\Throwable $e) {
            Log::error('[EscrowService] refund failed', ['pi' => $paymentIntentId, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Refund failed: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE — HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    private function ensureTransferGroup(BpContract $contract): string
    {
        if ($contract->transfer_group) {
            return $contract->transfer_group;
        }
        $tg = 'aegis_contract_' . $contract->id;
        $contract->update(['transfer_group' => $tg]);
        return $tg;
    }

    private function guardProviderPaymentMethod(User $provider): void
    {
        if (
            str_starts_with((string) $provider->stripe_id, 'cus_demo_') ||
            str_starts_with((string) $provider->stripe_payment_method_id, 'pm_demo_')
        ) {
            return; // demo allowed
        }
        if (!$provider->stripe_id || !$provider->stripe_payment_method_id) {
            throw new \RuntimeException('No payment method on file. Please add a card in Finances → Payment Method before funding escrow.');
        }
    }

    private function guardBpConnectAccount(User $bp): void
    {
        if (str_starts_with((string) $bp->stripe_account_id, 'acct_demo_')) {
            return; // demo allowed
        }
        if (!$bp->stripe_account_id || !preg_match('/^acct_[a-zA-Z0-9]{16,}$/', $bp->stripe_account_id)) {
            throw new \RuntimeException('Business Partner has not completed Stripe Connect onboarding. Escrow cannot be funded until they do.');
        }
    }

    private function guardMilestoneNotAlreadyFunded(BpMilestone $milestone): void
    {
        $status = $milestone->status instanceof MilestoneStatus
            ? $milestone->status->value
            : (string) $milestone->status;

        $alreadyFunded = in_array($status, [
            MilestoneStatus::Funded->value,
            MilestoneStatus::InProgress->value,
            MilestoneStatus::Submitted->value,
            MilestoneStatus::RevisionRequested->value,
            MilestoneStatus::Approved->value,
            MilestoneStatus::Released->value,
            MilestoneStatus::Paid->value,
        ], true);

        if ($alreadyFunded) {
            throw new \RuntimeException("Milestone is already funded (status: {$status}).");
        }
    }

    private function writeLedger(
        string      $kind,
        BpContract  $contract,
        int         $amountCents,
        User        $provider,
        User        $bp,
        ?string     $stripeObjectId,
        string      $stripeObjectType,
        string      $description,
        mixed       $actorId,
        ?BpMilestone $milestone = null,
    ): void {
        BpEscrowLedger::create([
            'id'                 => 'bel_' . Str::lower(Str::random(12)),
            'contract_id'        => $contract->id,
            'milestone_id'       => $milestone?->id,
            'kind'               => $kind,
            'amount_cents'       => $amountCents,
            'provider_id'        => $provider->id,
            'bp_id'              => $bp->id,
            'stripe_object_id'   => $stripeObjectId,
            'stripe_object_type' => $stripeObjectType,
            'description'        => $description,
            'actor_id'           => is_string($actorId) ? $actorId : (string) $actorId,
            'created_at'         => now(),
        ]);
    }

    private function logFund(BpContract $contract, BpMilestone $milestone, User $provider, int $cents): void
    {
        $formatted = '$' . number_format($cents / 100, 2);
        $bp        = $contract->bp;

        $this->activity->log(
            $provider->id, 'provider', 'job_postings', ActivitySeverity::Info,
            'escrow_funded', "Milestone funded: {$milestone->title}",
            "{$formatted} held in escrow. Work can now begin.",
            'bp_milestone', $milestone->id, $contract->bp_id,
            'log', $provider->id
        );
        $this->activity->log(
            $contract->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            'escrow_funded', "{$formatted} funded for: {$milestone->title}",
            'Payment is secured in escrow. You may begin work on this milestone.',
            'bp_milestone', $milestone->id, $provider->id,
            'notification', $provider->id
        );
    }

    private function logRelease(BpContract $contract, BpMilestone $milestone, ?User $approver, int $cents, bool $isAutoRelease): void
    {
        $formatted     = '$' . number_format($cents / 100, 2);
        $providerLabel = $isAutoRelease
            ? 'Milestone auto-released (no provider response within review window)'
            : "{$approver->display_name} approved and released payment";

        $this->activity->log(
            $contract->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Info,
            'milestone_released', "Payment released: {$milestone->title}",
            "{$formatted} has been transferred to your Stripe Connect account.",
            'bp_milestone', $milestone->id, $contract->practitioner_id,
            'log', $contract->bp_id
        );
        $this->activity->log(
            $contract->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'milestone_released', "{$providerLabel}: {$milestone->title}",
            "{$formatted} released to Business Partner.",
            'bp_milestone', $milestone->id, $contract->bp_id,
            'notification', $contract->bp_id
        );
    }

    private function logRefund(BpContract $contract, BpMilestone $milestone, User $actor, int $cents, string $reason): void
    {
        $formatted = '$' . number_format($cents / 100, 2);

        $this->activity->log(
            $contract->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'escrow_refunded', "Escrow refunded: {$milestone->title}",
            "{$formatted} returned to your payment method. Reason: {$reason}",
            'bp_milestone', $milestone->id, $contract->bp_id,
            'log', $actor->id
        );
        $this->activity->log(
            $contract->bp_id, 'business_partner', 'job_postings', ActivitySeverity::Warning,
            'escrow_refunded', "Escrow refunded on milestone: {$milestone->title}",
            "{$formatted} was refunded to the provider. Reason: {$reason}",
            'bp_milestone', $milestone->id, $contract->practitioner_id,
            'notification', $actor->id
        );
    }
}
