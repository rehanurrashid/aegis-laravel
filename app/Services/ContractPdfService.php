<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BpContract;
use App\Models\BpMilestone;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * ContractPdfService — renders a contract as a self-contained HTML document
 * suitable for browser printing / Save as PDF.
 *
 * No dompdf dependency required — we use the same approach as the existing
 * downloadPdf() JavaScript function in ContractModal.vue but server-side,
 * which gives a permanent URL both parties can share and bookmark.
 *
 * The rendered HTML is returned as a string; the controller streams it as
 * text/html so the browser's native Print → Save as PDF works perfectly.
 *
 * Wave 6 approach:
 *  - Route: GET /provider/support-services/contracts/{contract}/pdf
 *  - Route: GET /business/contracts/{contract}/pdf
 *  - Authorization: BpContractPolicy::view()
 *  - Includes: signatures with typed names + timestamps + IP address
 */
class ContractPdfService
{
    /**
     * Render the contract as a self-contained HTML string for browser printing.
     */
    public function render(BpContract $contract, User $viewer): string
    {
        $contract->load(['practitioner:id,display_name,email', 'bp:id,display_name,email', 'milestones']);

        $provider = $contract->practitioner;
        $bp       = $contract->bp;

        $statusLabel = [
            'draft'             => 'Draft',
            'pending_signature' => 'Awaiting Signature',
            'pending_funding'   => 'Awaiting Funding',
            'active'            => 'Active',
            'completed'         => 'Completed',
            'cancelled'         => 'Cancelled',
            'disputed'          => 'Disputed',
        ][$this->val($contract->status)] ?? $this->val($contract->status);

        $paymentType = $contract->payment_type === 'milestone' ? 'Milestone-based' : 'One-time';

        $totalFormatted = '$' . number_format(($contract->total_value_cents ?? 0) / 100, 2);

        // Signature block
        $providerSig = $this->renderSignatureBlock(
            'Provider (Practitioner)',
            $provider?->display_name,
            $contract->practitioner_signature_name,
            $contract->practitioner_signed_at,
        );
        $bpSig = $this->renderSignatureBlock(
            'Business Partner',
            $bp?->display_name,
            $contract->bp_signature_name,
            $contract->bp_signed_at,
        );

        // Milestones table
        $milestonesHtml = '';
        if ($contract->milestones->count()) {
            $rows = $contract->milestones->sortBy('sort_order')->map(function (BpMilestone $m) {
                $status = $this->val($m->status);
                $statusLabel = [
                    'pending'            => 'Pending',
                    'pending_funding'    => 'Awaiting Funding',
                    'funded'             => 'Funded',
                    'in_progress'        => 'In Progress',
                    'submitted'          => 'Under Review',
                    'revision_requested' => 'Revision',
                    'approved'           => 'Approved',
                    'released'           => 'Paid',
                    'paid'               => 'Paid',
                    'disputed'           => 'Disputed',
                    'refunded'           => 'Refunded',
                ][$status] ?? $status;

                $amount = '$' . number_format(($m->amount_cents ?? 0) / 100, 2);
                $due    = $m->due_at?->format('M j, Y') ?? '—';
                return "<tr>
                    <td>{$this->e($m->title)}</td>
                    <td>{$due}</td>
                    <td style=\"text-align:right\">{$amount}</td>
                    <td style=\"text-align:center\">{$statusLabel}</td>
                </tr>";
            })->implode('');

            $milestonesHtml = "
            <h3 style=\"font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#8a7d52;margin:28px 0 10px;border-bottom:1px solid #e8dfc6;padding-bottom:4px;\">Milestones</h3>
            <table style=\"width:100%;border-collapse:collapse;font-size:13px;\">
                <thead>
                    <tr style=\"background:#f7f5ef;\">
                        <th style=\"text-align:left;padding:8px 10px;font-size:11px;text-transform:uppercase;color:#5b522f;border-bottom:1px solid #e3e3e3;\">Milestone</th>
                        <th style=\"text-align:left;padding:8px 10px;font-size:11px;text-transform:uppercase;color:#5b522f;border-bottom:1px solid #e3e3e3;\">Due</th>
                        <th style=\"text-align:right;padding:8px 10px;font-size:11px;text-transform:uppercase;color:#5b522f;border-bottom:1px solid #e3e3e3;\">Amount</th>
                        <th style=\"text-align:center;padding:8px 10px;font-size:11px;text-transform:uppercase;color:#5b522f;border-bottom:1px solid #e3e3e3;\">Status</th>
                    </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>";
        }

        // Terms body
        $termsHtml = '';
        if ($contract->terms) {
            $termsFormatted = $this->e($contract->terms);
            $termsHtml = "
            <h3 style=\"font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#8a7d52;margin:28px 0 10px;border-bottom:1px solid #e8dfc6;padding-bottom:4px;\">Contract Terms</h3>
            <pre style=\"font-family:Georgia,'Times New Roman',serif;font-size:12.5px;color:#3a3530;white-space:pre-wrap;line-height:1.7;margin:0;\">{$termsFormatted}</pre>";
        }

        // Escrow disclosure
        $escrowFunded   = number_format(($contract->escrow_funded_cents ?? 0) / 100, 2);
        $escrowReleased = number_format(($contract->escrow_released_cents ?? 0) / 100, 2);
        $escrowRefunded = number_format(($contract->escrow_refunded_cents ?? 0) / 100, 2);

        $generatedAt = now()->format('F j, Y \a\t g:i A T');
        $appUrl      = rtrim(config('app.url'), '/');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contract Agreement — {$this->e($contract->title)}</title>
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
      max-width: 780px;
      margin: 0 auto;
      padding: 52px 56px;
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
    .aegis-logo {
      font-family: Georgia, serif; font-size: 22px; font-weight: 700;
      color: #2d2a26; letter-spacing: -0.3px; margin-bottom: 4px;
    }
    .doc-type {
      font-size: 11px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.8px; color: #8a7d52; margin-bottom: 28px;
    }
    h2 {
      font-size: 24px; font-weight: 700; color: #2d2a26;
      margin: 0 0 6px; line-height: 1.3;
    }
    .contract-id {
      font-family: monospace; font-size: 11px; color: #8a8378;
      margin-bottom: 28px;
    }
    .info-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 0 24px; margin-bottom: 28px;
      background: #fbf8f1; border: 1px solid #e8dfc6;
      border-radius: 6px; padding: 18px 22px;
    }
    .info-label {
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.6px; color: #8a7d52; margin-bottom: 2px;
    }
    .info-value {
      font-size: 13px; color: #2a2a2a; font-weight: 600; margin-bottom: 12px;
    }
    .escrow-box {
      background: #f0faf4; border-left: 3px solid #2f7d54;
      padding: 14px 18px; border-radius: 0 6px 6px 0; margin: 24px 0;
      font-size: 12.5px; color: #2f5c3a; line-height: 1.6;
    }
    .sig-block-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 24px;
      margin-top: 32px; padding-top: 24px; border-top: 2px solid #e8dfc6;
    }
    .sig-block { border: 1px solid #e8dfc6; padding: 18px 20px; border-radius: 6px; }
    .sig-role {
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.6px; color: #8a7d52; margin-bottom: 10px;
    }
    .sig-name {
      font-size: 18px; font-style: italic; color: #2a2a2a;
      font-family: Georgia, serif; border-bottom: 1px solid #ccc;
      padding-bottom: 6px; margin-bottom: 8px;
    }
    .sig-meta { font-size: 11px; color: #8a8378; line-height: 1.6; }
    .sig-pending {
      font-size: 13px; color: #b0a090; font-style: italic; padding: 8px 0;
    }
    .doc-footer {
      margin-top: 40px; padding-top: 16px; border-top: 1px solid #e4dfd7;
      font-size: 11px; color: #8a8378; line-height: 1.6; text-align: center;
    }
  </style>
</head>
<body>
  <div class="page">
    <!-- Print bar -->
    <div class="print-bar no-print">
      <button class="print-btn" onclick="window.print()">⬇ Print / Save as PDF</button>
      <button class="print-btn" style="background:#5b6877;" onclick="window.close()">Close</button>
    </div>

    <!-- Header -->
    <div class="aegis-logo">Aegis</div>
    <div class="doc-type">Service Agreement · Aegis Practice Continuity Platform</div>

    <h2>{$this->e($contract->title)}</h2>
    <div class="contract-id">Contract ID: {$this->e($contract->id)} &nbsp;·&nbsp; Status: {$this->e($statusLabel)}</div>

    <!-- Info grid -->
    <div class="info-grid">
      <div>
        <div class="info-label">Provider (Practitioner)</div>
        <div class="info-value">{$this->e($provider?->display_name ?? '—')}</div>
      </div>
      <div>
        <div class="info-label">Business Partner</div>
        <div class="info-value">{$this->e($bp?->display_name ?? '—')}</div>
      </div>
      <div>
        <div class="info-label">Contract Value</div>
        <div class="info-value" style="color:#2a6f2a;">{$totalFormatted}</div>
      </div>
      <div>
        <div class="info-label">Payment Structure</div>
        <div class="info-value">{$this->e($paymentType)}</div>
      </div>
      <div>
        <div class="info-label">Fully Executed</div>
        <div class="info-value">{$this->e($contract->fully_executed_at?->format('F j, Y') ?? '—')}</div>
      </div>
      <div>
        <div class="info-label">Funding Mode</div>
        <div class="info-value">{$this->e($contract->funding_mode === 'full_upfront' ? 'Full contract upfront' : 'Per milestone')}</div>
      </div>
    </div>

    <!-- Escrow summary (if any movement) -->
    <div class="escrow-box">
      <strong>Aegis Escrow:</strong>
      {$this->e("\${$escrowFunded}")} funded &nbsp;·&nbsp;
      {$this->e("\${$escrowReleased}")} released to Business Partner &nbsp;·&nbsp;
      {$this->e("\${$escrowRefunded}")} refunded to Provider.
      Funds are held by Aegis Platform Inc. and transferred via Stripe.
    </div>

    {$milestonesHtml}

    {$termsHtml}

    <!-- Signature blocks -->
    <div class="sig-block-grid">
      {$providerSig}
      {$bpSig}
    </div>

    <!-- Footer -->
    <div class="doc-footer">
      <p>
        Generated by Aegis Practice Continuity Platform &nbsp;·&nbsp;
        <a href="{$appUrl}" style="color:#8a7d52;">{$appUrl}</a>
      </p>
      <p>Document generated: {$generatedAt}</p>
      <p>
        This document is a record of the service agreement entered into via the Aegis platform.
        Both parties' electronic signatures, as typed above, constitute legally binding consent
        under applicable U.S. electronic signature laws (ESIGN Act, UETA).
      </p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function renderSignatureBlock(
        string $role,
        ?string $displayName,
        ?string $signatureName,
        ?\DateTimeInterface $signedAt,
    ): string {
        if ($signatureName && $signedAt) {
            return "<div class=\"sig-block\">
                <div class=\"sig-role\">{$this->e($role)}</div>
                <div class=\"sig-name\">{$this->e($signatureName)}</div>
                <div class=\"sig-meta\">
                    Signed: {$this->e($signedAt->format('F j, Y \a\t g:i A T'))}<br>
                    Registered name: {$this->e($displayName ?? '—')}
                </div>
            </div>";
        }

        return "<div class=\"sig-block\">
            <div class=\"sig-role\">{$this->e($role)}</div>
            <div class=\"sig-pending\">Signature pending…</div>
            <div class=\"sig-meta\">Registered name: {$this->e($displayName ?? '—')}</div>
        </div>";
    }

    /** Resolve a backed enum or string to its string value. */
    private function val(mixed $v): string
    {
        return $v instanceof \BackedEnum ? $v->value : (string) ($v ?? '');
    }

    /** HTML-escape a value safely. */
    private function e(?string $v): string
    {
        return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
