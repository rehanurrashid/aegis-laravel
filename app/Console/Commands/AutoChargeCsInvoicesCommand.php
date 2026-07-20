<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\CsInvoice;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\PayoutService;
use App\Enums\ActivitySeverity;
use Illuminate\Console\Command;

class AutoChargeCsInvoicesCommand extends Command
{
    protected $signature   = 'aegis:auto-charge-cs-invoices';
    protected $description = 'Auto-charge CS invoices that have passed the 7-day manual-pay grace period.';

    public function handle(PayoutService $payouts, ActivityService $activity): int
    {
        $cutoff = now()->subDays(7);

        $invoices = CsInvoice::where('status', InvoiceStatus::Sent->value)
            ->where('issued_at', '<=', $cutoff)
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No invoices eligible for auto-charge.');
            return 0;
        }

        foreach ($invoices as $invoice) {
            $provider = User::find($invoice->practitioner_id);
            $cs       = User::find($invoice->cs_id);

            if (!$provider || !$cs) {
                $this->warn("Skipping {$invoice->invoice_number}: missing provider or CS user.");
                continue;
            }

            if (!$provider->stripe_payment_method_id) {
                $this->warn("Skipping {$invoice->invoice_number}: provider has no default payment method.");
                continue;
            }

            if (!$cs->stripe_account_id) {
                $this->warn("Skipping {$invoice->invoice_number}: CS has no connected Stripe account.");
                continue;
            }

            try {
                $result = $payouts->chargeProviderToCs(
                    $provider,
                    $cs,
                    (int) $invoice->total_cents,
                    'usd',
                    ['cs_invoice_id' => $invoice->id, 'auto_charge' => true],
                    'Auto-charge: CS invoice ' . $invoice->invoice_number
                );

                $invoice->update([
                    'status'                    => InvoiceStatus::Paid->value,
                    'stripe_payment_intent_id'  => $result['stripe_payment_intent_id'] ?? null,
                    'stripe_transfer_id'        => $result['stripe_transfer_id'] ?? null,
                    'paid_at'                   => now(),
                ]);

                $this->info("Auto-charged {$invoice->invoice_number} ({$invoice->total_cents} cents).");

                // Activity log — actor is system (null actorId = use practitioner_id)
                $activity->log(
                    $provider->id, 'provider', 'finances',
                    ActivitySeverity::Info,
                    'cs_invoice_auto_charged',
                    'CS invoice auto-charged',
                    'Invoice ' . $invoice->invoice_number . ' auto-charged for $' . number_format($invoice->total_cents / 100, 2) . ' after 7-day grace period.',
                    CsInvoice::class, $invoice->id, $cs->id, 'log', $provider->id,
                );

                $activity->log(
                    $cs->id, 'continuity_steward', 'finances',
                    ActivitySeverity::Info,
                    'cs_invoice_auto_charged',
                    'Invoice auto-charged',
                    'Invoice ' . $invoice->invoice_number . ' was automatically charged to your practitioner\'s card after the 7-day grace period.',
                    CsInvoice::class, $invoice->id, $provider->id, 'notification', $provider->id,
                );
            } catch (\Exception $e) {
                $this->error("Failed {$invoice->invoice_number}: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
