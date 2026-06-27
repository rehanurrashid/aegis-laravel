<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Admin\RefundProcessed;
use App\Models\AdminAuditLog;
use App\Models\BpPayout;
use App\Models\CsPayout;
use App\Models\PractitionerPayment;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Stripe\StripeClient;

class AdminPaymentService
{
    public function __construct(private PayoutService $payouts) {}

    public function getLedger(array $filters = []): Collection
    {
        $q = PractitionerPayment::query();
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['user']))   $q->where('practitioner_id', $filters['user']);
        if (!empty($filters['from']))   $q->where('paid_at', '>=', $filters['from']);
        if (!empty($filters['to']))     $q->where('paid_at', '<=', $filters['to']);

        return $q->orderByDesc('paid_at')->limit($filters['limit'] ?? 200)->get();
    }

    public function getFailedPayments(): Collection
    {
        return PractitionerPayment::where('status', 'failed')
            ->orderByDesc('created_at')->get();
    }

    public function getPendingPayouts(): array
    {
        return [
            'bp' => BpPayout::where('status', 'pending')->orderBy('created_at')->get(),
            'cs' => CsPayout::where('status', 'pending')->orderBy('created_at')->get(),
        ];
    }

    public function getWebhookEvents(array $filters = []): Collection
    {
        $q = StripeWebhookEvent::query();
        if (!empty($filters['type']))      $q->where('event_type', $filters['type']);
        if (isset($filters['processed']))  $q->where('processed', $filters['processed'] ? 1 : 0);

        return $q->orderByDesc('received_at')->limit($filters['limit'] ?? 100)->get();
    }

    public function retryPayment(User $admin, PractitionerPayment $payment): PractitionerPayment
    {
        if (config('services.stripe.secret')) {
            $stripe = new StripeClient(config('services.stripe.secret'));
            // In production: re-attempt the failed PaymentIntent
            // $stripe->paymentIntents->confirm($payment->stripe_payment_intent_id);
        }
        $payment->update(['status' => 'pending']);
        $this->audit($admin, 'retry_payment', $payment->id, 'practitioner_payment');
        return $payment->fresh();
    }

    public function refundPayment(User $admin, PractitionerPayment $payment, int $amountCents): PractitionerPayment
    {
        if (config('services.stripe.secret')) {
            $stripe = new StripeClient(config('services.stripe.secret'));
            // $stripe->refunds->create(['payment_intent' => $payment->stripe_payment_intent_id, 'amount' => $amountCents]);
        }
        $payment->update(['status' => 'refunded']);
        $this->audit($admin, 'refund_payment', $payment->id, 'practitioner_payment', ['amount_cents' => $amountCents]);

        event(new RefundProcessed($payment->id, $amountCents));
        return $payment->fresh();
    }

    /**
     * @param BpPayout|CsPayout $payout
     */
    public function releasePayout(User $admin, $payout): mixed
    {
        $released = $this->payouts->release($payout);
        $type = $payout instanceof BpPayout ? 'bp_payout' : 'cs_payout';
        $this->audit($admin, 'release_payout', $payout->id, $type);
        return $released;
    }

    private function audit(User $admin, string $action, string $targetId, string $targetType, array $meta = []): void
    {
        AdminAuditLog::create([
            'id'          => 'aal_' . Str::lower(Str::random(12)),
            'admin_id'    => $admin->id,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'meta_json'   => json_encode($meta),
            'created_at'  => now(),
        ]);
    }
}
