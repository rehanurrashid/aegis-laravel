<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Events\Business\PayoutReleased;
use App\Models\BpPayout;
use App\Models\CsPayout;
use App\Models\User;
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
}
