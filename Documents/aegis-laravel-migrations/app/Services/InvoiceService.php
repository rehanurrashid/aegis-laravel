<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Events\Business\InvoicePaid;
use App\Events\Business\InvoiceSent;
use App\Models\BpContract;
use App\Models\BpInvoice;
use App\Models\BpInvoiceLineItem;
use App\Models\BpInvoicePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class InvoiceService
{
    public function __construct(private ActivityService $activity) {}

    public function create(BpContract $contract, User $bp, array $data): BpInvoice
    {
        if ($contract->bp_id !== $bp->id) {
            throw new RuntimeException('Only the BP on the contract can create an invoice.');
        }

        return BpInvoice::create([
            'id'              => 'bi_' . Str::lower(Str::random(12)),
            'contract_id'     => $contract->id,
            'practitioner_id' => $contract->practitioner_id,
            'bp_id'           => $contract->bp_id,
            'invoice_number'  => $data['invoice_number'] ?? 'INV-' . strtoupper(Str::random(8)),
            'subtotal_cents'  => 0,
            'tax_cents'       => $data['tax_cents'] ?? 0,
            'total_cents'     => 0,
            'currency'        => 'USD',
            'status'          => 'draft',
            'issued_at'       => now(),
            'due_at'          => $data['due_at'] ?? now()->addDays(30),
            'created_at'      => now(),
        ]);
    }

    public function addLineItem(BpInvoice $invoice, array $data): BpInvoiceLineItem
    {
        $qty   = (int) ($data['quantity'] ?? 1);
        $unit  = (int) ($data['unit_price_cents'] ?? 0);
        $total = $qty * $unit;

        $item = BpInvoiceLineItem::create([
            'id'               => 'bil_' . Str::lower(Str::random(12)),
            'invoice_id'       => $invoice->id,
            'description'      => $data['description'],
            'quantity'         => $qty,
            'unit_price_cents' => $unit,
            'total_cents'      => $total,
        ]);

        $this->recalculateTotals($invoice);
        return $item;
    }

    public function removeLineItem(BpInvoiceLineItem $item): bool
    {
        $invoice = $item->invoice;
        $deleted = (bool) $item->delete();
        if ($deleted && $invoice) {
            $this->recalculateTotals($invoice);
        }
        return $deleted;
    }

    public function send(BpInvoice $invoice): BpInvoice
    {
        if ($invoice->status !== 'draft') {
            throw new RuntimeException('Only draft invoices can be sent.');
        }
        $invoice->update(['status' => 'sent', 'sent_at' => now()]);

        event(new InvoiceSent($invoice->fresh()));

        $this->activity->log(
            $invoice->practitioner_id, 'provider', 'payment', ActivitySeverity::Info,
            'invoice_received',
            "New invoice {$invoice->invoice_number}",
            'Total: $' . number_format($invoice->total_cents / 100, 2),
            'bp_invoice', $invoice->id, $invoice->bp_id
        );

        return $invoice->fresh();
    }

    /**
     * Record a payment against the invoice (in cents).
     */
    public function recordPayment(BpInvoice $invoice, int $amountCents, string $stripeChargeId = null): BpInvoicePayment
    {
        return DB::transaction(function () use ($invoice, $amountCents, $stripeChargeId) {
            $payment = BpInvoicePayment::create([
                'id'               => 'bip_' . Str::lower(Str::random(12)),
                'invoice_id'       => $invoice->id,
                'amount_cents'     => $amountCents,
                'stripe_charge_id' => $stripeChargeId,
                'paid_at'          => now(),
            ]);

            $totalPaid = BpInvoicePayment::where('invoice_id', $invoice->id)->sum('amount_cents');
            if ($totalPaid >= $invoice->total_cents) {
                $invoice->update(['status' => 'paid', 'paid_at' => now()]);
                event(new InvoicePaid($invoice->fresh()));

                $this->activity->log(
                    $invoice->bp_id, 'business_partner', 'payment', ActivitySeverity::Info,
                    'invoice_paid',
                    "Invoice {$invoice->invoice_number} paid",
                    'Funds will appear in your next payout.',
                    'bp_invoice', $invoice->id, $invoice->practitioner_id
                );
            } else {
                $invoice->update(['status' => 'partial']);
            }

            return $payment;
        });
    }

    public function void(BpInvoice $invoice): BpInvoice
    {
        if (!in_array($invoice->status, ['draft', 'sent'], true)) {
            throw new RuntimeException('Only draft or sent invoices can be voided.');
        }
        $invoice->update(['status' => 'void', 'voided_at' => now()]);
        return $invoice->fresh();
    }

    public function refund(BpInvoice $invoice, int $amountCents): void
    {
        BpInvoicePayment::create([
            'id'           => 'bip_' . Str::lower(Str::random(12)),
            'invoice_id'   => $invoice->id,
            'amount_cents' => -$amountCents,
            'paid_at'      => now(),
        ]);
        $invoice->update(['status' => 'refunded']);
    }

    public function getForContract(string $contractId): Collection
    {
        return BpInvoice::where('contract_id', $contractId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getForBp(string $bpId): Collection
    {
        return BpInvoice::where('bp_id', $bpId)
            ->orderByDesc('created_at')
            ->get();
    }

    private function recalculateTotals(BpInvoice $invoice): void
    {
        $subtotal = (int) BpInvoiceLineItem::where('invoice_id', $invoice->id)->sum('total_cents');
        $tax      = (int) $invoice->tax_cents;
        $invoice->update([
            'subtotal_cents' => $subtotal,
            'total_cents'    => $subtotal + $tax,
        ]);
    }
}
