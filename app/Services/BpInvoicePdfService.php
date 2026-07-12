<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BpInvoice;

/**
 * BpInvoicePdfService — renders a BP invoice as a self-contained HTML document
 * suitable for browser printing / Save as PDF.
 *
 * Mirrors ContractPdfService pattern — no dompdf required.
 * Route: GET /provider/support-services/bp-invoices/{invoice}/pdf
 */
class BpInvoicePdfService
{
    public function render(BpInvoice $invoice): string
    {
        $invoice->load(['bp:id,display_name,email', 'practitioner:id,display_name,email', 'contract:id,title']);

        $bp       = $invoice->bp;
        $provider = $invoice->practitioner;

        $status = $invoice->status instanceof \BackedEnum ? $invoice->status->value : (string) ($invoice->status ?? '');

        $statusLabel = [
            'draft'    => 'Draft',
            'sent'     => 'Awaiting Approval',
            'overdue'  => 'Overdue',
            'disputed' => 'Disputed',
            'paid'     => 'Paid',
            'void'     => 'Void',
        ][$status] ?? ucfirst($status);

        $statusColor = match ($status) {
            'paid'     => '#2f7d54',
            'overdue',
            'disputed' => '#c0392b',
            'sent'     => '#8a7d52',
            default    => '#555',
        };

        $total      = '$' . number_format(($invoice->total_cents ?? 0) / 100, 2);
        $subtotal   = '$' . number_format(($invoice->subtotal_cents ?? $invoice->total_cents ?? 0) / 100, 2);
        $invoiceNum = $invoice->invoice_number ?? substr($invoice->id, 0, 10);
        $issuedAt   = $invoice->issued_at?->format('F j, Y') ?? '—';
        $dueAt      = $invoice->due_at?->format('F j, Y') ?? '—';
        $paidAt     = $invoice->paid_at?->format('F j, Y') ?? null;
        $contractTitle = $invoice->contract?->title ?? $invoice->notes ?? '—';
        $notes      = $invoice->notes ? $this->e($invoice->notes) : null;

        $generatedAt = now()->format('F j, Y \a\t g:i A T');
        $appUrl      = rtrim(config('app.url'), '/');

        $paidRow = $paidAt
            ? "<tr><td style=\"color:#2f7d54;font-weight:600;\">Paid on</td><td style=\"text-align:right;color:#2f7d54;font-weight:600;\">{$paidAt}</td></tr>"
            : '';

        $notesHtml = $notes
            ? "<div style=\"margin-top:24px;padding:14px 18px;background:#fbf8f1;border:1px solid #e8dfc6;border-radius:6px;\">
                <div style=\"font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:#8a7d52;margin-bottom:6px;\">Notes</div>
                <div style=\"font-size:13px;color:#3a3530;line-height:1.6;\">{$notes}</div>
               </div>"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Invoice #{$this->e($invoiceNum)}</title>
  <style>
    @media print {
      .no-print { display: none !important; }
      body { margin: 0; padding: 0; }
      .page { box-shadow: none !important; max-width: 100% !important; }
    }
    * { box-sizing: border-box; }
    body {
      font-family: Georgia, 'Times New Roman', serif;
      background: #f7f5ef;
      margin: 0; padding: 32px 16px;
      color: #2a2a2a;
    }
    .page {
      background: #fff;
      max-width: 680px;
      margin: 0 auto;
      padding: 48px 52px;
      box-shadow: 0 2px 20px rgba(0,0,0,0.08);
      border: 1px solid #e4dfd7;
      border-radius: 8px;
    }
    .print-bar {
      display: flex; gap: 10px; justify-content: flex-end;
      padding-bottom: 20px; margin-bottom: 28px;
      border-bottom: 1px solid #e8dfc6;
    }
    .print-btn {
      padding: 8px 18px; background: #8a7d52; color: #fff;
      border: 0; border-radius: 5px; font-size: 13px; font-weight: 700;
      cursor: pointer; font-family: inherit;
    }
    .print-btn:hover { background: #705f3e; }
    .aegis-logo { font-family: Georgia,serif; font-size: 22px; font-weight: 700; color: #2d2a26; margin-bottom: 4px; }
    .doc-type { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #8a7d52; margin-bottom: 32px; }
    .inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; }
    .inv-number { font-size: 22px; font-weight: 700; color: #2d2a26; }
    .inv-status { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {$statusColor}; margin-top: 4px; }
    .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
    .party-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #8a7d52; margin-bottom: 4px; }
    .party-name { font-size: 14px; font-weight: 700; color: #2a2a2a; }
    .party-email { font-size: 12px; color: #8a8378; margin-top: 2px; }
    .receipt {
      border: 1px solid #e8dfc6; border-radius: 6px; overflow: hidden; margin-bottom: 24px;
    }
    .receipt-head {
      background: #f7f5ef; padding: 10px 16px;
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.5px; color: #8a7d52;
    }
    .receipt table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .receipt td { padding: 10px 16px; border-top: 1px solid #f0ece3; color: #3a3530; }
    .receipt tr.total td { font-weight: 700; font-size: 16px; background: #fbf8f1; border-top: 2px solid #e8dfc6; }
    .receipt tr.total td:last-child { color: #2a6f2a; }
    .dates { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
    .date-item { background: #fbf8f1; border: 1px solid #e8dfc6; border-radius: 6px; padding: 12px 16px; }
    .date-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #8a7d52; margin-bottom: 4px; }
    .date-value { font-size: 14px; font-weight: 700; color: #2a2a2a; }
    .fine-print {
      margin-top: 28px; padding: 12px 16px;
      background: #f0faf4; border-left: 3px solid #2f7d54;
      font-size: 12px; color: #2f5c3a; line-height: 1.6;
    }
    .doc-footer {
      margin-top: 36px; padding-top: 16px; border-top: 1px solid #e4dfd7;
      font-size: 11px; color: #8a8378; line-height: 1.6; text-align: center;
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="print-bar no-print">
      <button class="print-btn" onclick="window.print()">⬇ Print / Save as PDF</button>
      <button class="print-btn" style="background:#5b6877;" onclick="window.close()">Close</button>
    </div>

    <div class="aegis-logo">Aegis</div>
    <div class="doc-type">Business Partner Invoice · Aegis Practice Continuity Platform</div>

    <div class="inv-header">
      <div>
        <div class="inv-number">Invoice #{$this->e($invoiceNum)}</div>
        <div class="inv-status">{$this->e($statusLabel)}</div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:28px;font-weight:700;color:#2a6f2a;">{$total}</div>
        <div style="font-size:11px;color:#8a8378;margin-top:2px;">Total Due</div>
      </div>
    </div>

    <div class="parties">
      <div>
        <div class="party-label">From (Business Partner)</div>
        <div class="party-name">{$this->e($bp?->display_name ?? '—')}</div>
        <div class="party-email">{$this->e($bp?->email ?? '')}</div>
      </div>
      <div>
        <div class="party-label">Bill To (Provider)</div>
        <div class="party-name">{$this->e($provider?->display_name ?? '—')}</div>
        <div class="party-email">{$this->e($provider?->email ?? '')}</div>
      </div>
    </div>

    <div class="dates">
      <div class="date-item">
        <div class="date-label">Invoice Date</div>
        <div class="date-value">{$issuedAt}</div>
      </div>
      <div class="date-item">
        <div class="date-label">Due Date</div>
        <div class="date-value" style="color:{$this->e($status === 'overdue' ? '#c0392b' : '#2a2a2a')}">{$dueAt}</div>
      </div>
    </div>

    <div class="receipt">
      <div class="receipt-head">Services</div>
      <table>
        <tbody>
          <tr>
            <td>{$this->e($contractTitle)}</td>
            <td style="text-align:right;">{$subtotal}</td>
          </tr>
          {$paidRow}
          <tr class="total">
            <td>Total</td>
            <td style="text-align:right;">{$total}</td>
          </tr>
        </tbody>
      </table>
    </div>

    {$notesHtml}

    <div class="fine-print">
      <strong>Peer payment via Stripe Connect</strong> — funds route directly to the Business Partner.
      Aegis holds no funds. Payment is processed through Aegis Practice Continuity Platform.
    </div>

    <div class="doc-footer">
      <p>Generated by Aegis Practice Continuity Platform &nbsp;·&nbsp;
        <a href="{$appUrl}" style="color:#8a7d52;">{$appUrl}</a></p>
      <p>Document generated: {$generatedAt}</p>
      <p>Invoice ID: {$this->e($invoice->id)}</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private function e(?string $v): string
    {
        return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
