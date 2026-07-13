<?php

declare(strict_types=1);

namespace App\Services;

/**
 * AegisPdfService — universal PDF renderer for the Aegis platform.
 *
 * One CSS block, one header/footer, one layout — renders every document
 * type (BP invoice, CS invoice, contract) as a self-contained HTML string
 * that the browser's Print → Save as PDF produces perfectly.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * USAGE
 * ─────────────────────────────────────────────────────────────────────────
 *
 * (1) BP Invoice:
 *       $pdf->bpInvoice($invoice)
 *
 * (2) CS Invoice:
 *       $pdf->csInvoice($invoice)
 *
 * (3) Contract:
 *       $pdf->contract($contract, $viewerUser)
 *
 * All three return a plain HTML string; stream it as:
 *   response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8'])
 *
 * ─────────────────────────────────────────────────────────────────────────
 * DOCUMENT STRUCTURE
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Every document renders:
 *   [Print / Save as PDF] bar   (hidden on print)
 *   Header: Aegis logo · doc-type label
 *   Title block: doc title + status badge
 *   Party grid: From / Bill To (or Provider / Business Partner)
 *   Date chips: issued · due · paid (whichever apply)
 *   Line items table (BP invoice) OR single-row summary (CS invoice)
 *   Notes/scope box (optional)
 *   Milestones table (contracts with milestones)
 *   Terms section (contracts)
 *   Signature blocks (contracts)
 *   Escrow disclosure (contracts)
 *   Footer: platform name · generated timestamp · document ID
 */
class AegisPdfService
{
    // ─────────────────────────────────────────────────────────────────────
    // Public entry points
    // ─────────────────────────────────────────────────────────────────────

    public function bpInvoice(\App\Models\BpInvoice $invoice): string
    {
        $invoice->load([
            'bp:id,display_name,email',
            'practitioner:id,display_name,email',
            'contract:id,title',
            'lineItems',
        ]);

        $bp       = $invoice->bp;
        $provider = $invoice->practitioner;

        $status = $this->enumVal($invoice->status);

        $statusLabel = [
            'draft'    => 'Draft',
            'sent'     => 'Awaiting Approval',
            'overdue'  => 'Overdue',
            'disputed' => 'Disputed',
            'paid'     => 'Paid',
            'void'     => 'Void',
        ][$status] ?? ucfirst($status);

        $invoiceNum    = $invoice->invoice_number ?? substr($invoice->id, 0, 10);
        $contractTitle = $invoice->contract?->title ?? '—';

        // Date chips
        $dateChips = $this->dateChips([
            ['label' => 'Invoice Date', 'value' => $invoice->issued_at?->format('F j, Y'), 'warn' => false],
            ['label' => 'Due Date',     'value' => $invoice->due_at?->format('F j, Y'),    'warn' => $status === 'overdue'],
            ['label' => 'Paid On',      'value' => $invoice->paid_at?->format('F j, Y'),   'warn' => false, 'green' => true],
        ]);

        // Line items table — use real line items if present, otherwise single row
        $lineItems = $invoice->lineItems;
        if ($lineItems->isNotEmpty()) {
            $rows = $lineItems->sortBy('sort_order')->map(function ($item) {
                $unit  = '$' . number_format($item->unit_amount_cents / 100, 2);
                $total = '$' . number_format($item->line_total_cents / 100, 2);
                $qty   = $item->quantity != 1 ? " × {$item->quantity}" : '';
                return $this->lineItemRow($this->e($item->description), "{$unit}{$qty}", $total);
            })->implode('');
        } else {
            $subtotal = '$' . number_format(($invoice->subtotal_cents ?? $invoice->total_cents ?? 0) / 100, 2);
            $rows = $this->lineItemRow($this->e($contractTitle), '', $subtotal);
        }

        $total    = '$' . number_format(($invoice->total_cents ?? 0) / 100, 2);
        $totalRow = "<tr class=\"items-total\"><td colspan=\"2\">Total</td><td>{$total}</td></tr>";

        $paidRow = $invoice->paid_at
            ? "<tr class=\"items-paid\"><td colspan=\"2\">Paid on {$this->e($invoice->paid_at->format('F j, Y'))}</td><td>{$total}</td></tr>"
            : '';

        $notesHtml = $invoice->notes
            ? $this->notesBox('Notes', $this->e($invoice->notes))
            : '';

        $disclosure = $this->disclosureBox(
            'Peer payment via Stripe Connect — funds route directly to the Business Partner. Aegis holds no funds.'
        );

        return $this->wrap(
            title:      "Invoice #{$this->e($invoiceNum)}",
            docType:    'Business Partner Invoice · Aegis Practice Continuity',
            pageTitle:  "Invoice #{$this->e($invoiceNum)}",
            status:     $status,
            statusLabel: $statusLabel,
            totalCents: (int) ($invoice->total_cents ?? 0),
            parties:    $this->partyGrid(
                fromLabel: 'From (Business Partner)',
                fromName:  $bp?->display_name ?? '—',
                fromEmail: $bp?->email ?? '',
                toLabel:   'Bill To (Practitioner)',
                toName:    $provider?->display_name ?? '—',
                toEmail:   $provider?->email ?? '',
            ),
            dateChips:  $dateChips,
            body:       $this->itemsTable($rows . $paidRow . $totalRow) . $notesHtml . $disclosure,
            signatures: '',
            footer:     "Invoice ID: {$this->e($invoice->id)}",
        );
    }

    // ─────────────────────────────────────────────────────────────────────

    public function csInvoice(\App\Models\CsInvoice $invoice): string
    {
        $invoice->load([
            'cs:id,display_name,email',
            'practitioner:id,display_name,email',
        ]);

        $cs       = $invoice->cs;
        $provider = $invoice->practitioner;

        $status = $this->enumVal($invoice->status);

        $statusLabel = [
            'draft'   => 'Draft',
            'sent'    => 'Awaiting Payment',
            'overdue' => 'Overdue',
            'paid'    => 'Paid',
            'void'    => 'Void',
        ][$status] ?? ucfirst($status);

        $invoiceNum = $invoice->invoice_number ?? substr($invoice->id, 0, 10);
        $total      = '$' . number_format(($invoice->total_cents ?? 0) / 100, 2);

        $dateChips = $this->dateChips([
            ['label' => 'Invoice Date', 'value' => $invoice->issued_at?->format('F j, Y'), 'warn' => false],
            ['label' => 'Due Date',     'value' => $invoice->due_at?->format('F j, Y'),    'warn' => $status === 'overdue'],
            ['label' => 'Paid On',      'value' => $invoice->paid_at?->format('F j, Y'),   'warn' => false, 'green' => true],
        ]);

        $row      = $this->lineItemRow('Continuity Steward Services', '', $total);
        $totalRow = "<tr class=\"items-total\"><td colspan=\"2\">Total</td><td>{$total}</td></tr>";

        $paidRow = $invoice->paid_at
            ? "<tr class=\"items-paid\"><td colspan=\"2\">Paid on {$this->e($invoice->paid_at->format('F j, Y'))}</td><td>{$total}</td></tr>"
            : '';

        $disclosure = $this->disclosureBox(
            'Payment is processed via Stripe Connect and routes directly to the Continuity Steward. Aegis holds no funds.'
        );

        return $this->wrap(
            title:      "Invoice #{$this->e($invoiceNum)}",
            docType:    'Continuity Steward Invoice · Aegis Practice Continuity',
            pageTitle:  "Invoice #{$this->e($invoiceNum)}",
            status:     $status,
            statusLabel: $statusLabel,
            totalCents: (int) ($invoice->total_cents ?? 0),
            parties:    $this->partyGrid(
                fromLabel: 'From (Continuity Steward)',
                fromName:  $cs?->display_name ?? '—',
                fromEmail: $cs?->email ?? '',
                toLabel:   'Bill To (Practitioner)',
                toName:    $provider?->display_name ?? '—',
                toEmail:   $provider?->email ?? '',
            ),
            dateChips:  $dateChips,
            body:       $this->itemsTable($row . $paidRow . $totalRow) . $disclosure,
            signatures: '',
            footer:     "Invoice ID: {$this->e($invoice->id)}",
        );
    }

    // ─────────────────────────────────────────────────────────────────────

    public function contract(\App\Models\BpContract $contract, \App\Models\User $viewer): string
    {
        $contract->load([
            'practitioner:id,display_name,email',
            'bp:id,display_name,email',
            'milestones',
        ]);

        $provider = $contract->practitioner;
        $bp       = $contract->bp;

        $status = $this->enumVal($contract->status);

        $statusLabel = [
            'draft'             => 'Draft',
            'pending_signature' => 'Awaiting Signature',
            'pending_funding'   => 'Awaiting Funding',
            'active'            => 'Active',
            'completed'         => 'Completed',
            'cancelled'         => 'Cancelled',
            'disputed'          => 'Disputed',
        ][$status] ?? ucfirst($status);

        $paymentType = ($contract->payment_type === 'milestone') ? 'Milestone-based' : 'One-time';
        $fundingMode = ($contract->funding_mode === 'per_milestone') ? 'Per milestone' : 'Full contract upfront';
        $total       = '$' . number_format(($contract->total_value_cents ?? 0) / 100, 2);

        $dateChips = $this->dateChips([
            ['label' => 'Fully Executed', 'value' => $contract->fully_executed_at?->format('F j, Y'), 'warn' => false],
            ['label' => 'Started',        'value' => $contract->started_at?->format('F j, Y'),        'warn' => false],
            ['label' => 'Completed',      'value' => $contract->completed_at?->format('F j, Y'),      'warn' => false, 'green' => true],
        ]);

        // Contract meta grid (below parties, above body)
        $metaGrid = "
        <div class=\"meta-grid\">
          <div><div class=\"meta-label\">Contract Value</div><div class=\"meta-value\" style=\"color:var(--c-green)\">{$total}</div></div>
          <div><div class=\"meta-label\">Payment Structure</div><div class=\"meta-value\">{$this->e($paymentType)}</div></div>
          <div><div class=\"meta-label\">Funding Mode</div><div class=\"meta-value\">{$this->e($fundingMode)}</div></div>
          <div><div class=\"meta-label\">Contract ID</div><div class=\"meta-value\" style=\"font-family:monospace;font-size:11px;\">{$this->e($contract->id)}</div></div>
        </div>";

        // Milestones table
        $milestonesHtml = '';
        if ($contract->milestones->isNotEmpty()) {
            $milestonesHtml = $this->sectionHeading('Milestones');
            $rows = $contract->milestones->sortBy('sort_order')->map(function ($m) {
                $ms = $this->enumVal($m->status);
                $msLabel = [
                    'pending'            => 'Pending',
                    'pending_funding'    => 'Awaiting Funding',
                    'funded'             => 'Funded',
                    'in_progress'        => 'In Progress',
                    'submitted'          => 'Under Review',
                    'revision_requested' => 'Revision',
                    'approved'           => 'Approved',
                    'released'           => 'Released',
                    'paid'               => 'Paid',
                    'disputed'           => 'Disputed',
                    'refunded'           => 'Refunded',
                ][$ms] ?? $ms;
                $amount = '$' . number_format(($m->amount_cents ?? 0) / 100, 2);
                $due    = $m->due_at?->format('M j, Y') ?? '—';
                return "<tr>
                    <td>{$this->e($m->title)}</td>
                    <td>{$due}</td>
                    <td>{$amount}</td>
                    <td><span class=\"ms-badge ms-badge--{$ms}\">{$msLabel}</span></td>
                </tr>";
            })->implode('');

            $milestonesHtml .= "
            <table class=\"milestones-table\">
              <thead><tr>
                <th>Milestone</th><th>Due</th><th>Amount</th><th>Status</th>
              </tr></thead>
              <tbody>{$rows}</tbody>
            </table>";
        }

        // Terms
        $termsHtml = '';
        if ($contract->terms) {
            $termsHtml = $this->sectionHeading('Contract Terms')
                . "<pre class=\"terms-pre\">{$this->e($contract->terms)}</pre>";
        }

        // Scope from contract_meta
        $scopeSummary = \Illuminate\Support\Facades\DB::table('contract_meta')
            ->where('contract_id', $contract->id)
            ->where('meta_key', 'scope_summary')
            ->value('meta_value');
        $scopeHtml = $scopeSummary
            ? $this->notesBox('Scope of Work', $this->e($scopeSummary))
            : '';

        // Escrow disclosure
        $funded   = number_format(($contract->escrow_funded_cents ?? 0) / 100, 2);
        $released = number_format(($contract->escrow_released_cents ?? 0) / 100, 2);
        $refunded = number_format(($contract->escrow_refunded_cents ?? 0) / 100, 2);
        $escrow   = $this->disclosureBox(
            "Aegis Escrow: \${$funded} funded · \${$released} released to Business Partner · \${$refunded} refunded to Practitioner. "
            . "Funds held and transferred via Stripe. Aegis never retains funds permanently."
        );

        $body = $metaGrid . $scopeHtml . $milestonesHtml . $termsHtml . $escrow;

        // Signature blocks
        $sigHtml = "
        <div class=\"sig-grid\">
          {$this->sigBlock('Practitioner (Provider)', $provider?->display_name, $contract->practitioner_signature_name, $contract->practitioner_signed_at)}
          {$this->sigBlock('Business Partner', $bp?->display_name, $contract->bp_signature_name, $contract->bp_signed_at)}
        </div>
        <p class=\"sig-legal\">
          Electronic signatures above, as typed by each party, constitute legally binding consent
          under applicable U.S. electronic signature laws (ESIGN Act, UETA).
        </p>";

        return $this->wrap(
            title:      $this->e($contract->title),
            docType:    'Service Agreement · Aegis Practice Continuity',
            pageTitle:  $this->e($contract->title),
            status:     $status,
            statusLabel: $statusLabel,
            totalCents: (int) ($contract->total_value_cents ?? 0),
            parties:    $this->partyGrid(
                fromLabel: 'Business Partner',
                fromName:  $bp?->display_name ?? '—',
                fromEmail: $bp?->email ?? '',
                toLabel:   'Practitioner (Provider)',
                toName:    $provider?->display_name ?? '—',
                toEmail:   $provider?->email ?? '',
            ),
            dateChips:  $dateChips,
            body:       $body,
            signatures: $sigHtml,
            footer:     "Contract ID: {$this->e($contract->id)}",
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // Core layout wrapper
    // ─────────────────────────────────────────────────────────────────────

    private function wrap(
        string $title,
        string $docType,
        string $pageTitle,
        string $status,
        string $statusLabel,
        int    $totalCents,
        string $parties,
        string $dateChips,
        string $body,
        string $signatures,
        string $footer,
    ): string {
        $statusClass = match ($status) {
            'paid', 'completed', 'released', 'approved' => 'status--green',
            'overdue', 'disputed', 'cancelled'           => 'status--red',
            'sent', 'active', 'funded'                   => 'status--gold',
            default                                       => 'status--neutral',
        };

        $totalFormatted = '$' . number_format($totalCents / 100, 2);
        $generatedAt    = now()->format('F j, Y \a\t g:i A T');
        $appUrl         = rtrim(config('app.url'), '/');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{$title}</title>
  <style>
    {$this->css()}
  </style>
</head>
<body>
  <div class="page">

    <!-- ── Print bar ── -->
    <div class="print-bar no-print">
      <button class="btn-print" onclick="window.print()">
        ↓ &nbsp;Print / Save as PDF
      </button>
      <button class="btn-print btn-print--secondary" onclick="window.close()">
        Close
      </button>
    </div>

    <!-- ── Header ── -->
    <header class="doc-header">
      <div>
        <div class="aegis-logo">Aegis</div>
        <div class="doc-type">{$docType}</div>
      </div>
      <div class="doc-header-right">
        <div class="doc-total">{$totalFormatted}</div>
        <div class="doc-total-label">Total</div>
      </div>
    </header>

    <!-- ── Title + status ── -->
    <div class="title-row">
      <h1 class="doc-title">{$pageTitle}</h1>
      <span class="status-badge {$statusClass}">{$statusLabel}</span>
    </div>

    <!-- ── Parties ── -->
    {$parties}

    <!-- ── Date chips ── -->
    {$dateChips}

    <!-- ── Body (line items / milestones / terms / etc.) ── -->
    {$body}

    <!-- ── Signature blocks (contracts only) ── -->
    {$signatures}

    <!-- ── Footer ── -->
    <footer class="doc-footer">
      <div class="doc-footer-inner">
        <span>Generated by Aegis Practice Continuity Platform</span>
        <span>·</span>
        <a href="{$appUrl}" class="footer-link">{$appUrl}</a>
        <span>·</span>
        <span>{$generatedAt}</span>
      </div>
      <div class="doc-footer-id">{$footer}</div>
    </footer>

  </div>
</body>
</html>
HTML;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Component helpers
    // ─────────────────────────────────────────────────────────────────────

    private function partyGrid(
        string $fromLabel, string $fromName, string $fromEmail,
        string $toLabel,   string $toName,   string $toEmail,
    ): string {
        return "
        <div class=\"party-grid\">
          <div class=\"party\">
            <div class=\"party-label\">{$fromLabel}</div>
            <div class=\"party-name\">{$this->e($fromName)}</div>
            <div class=\"party-email\">{$this->e($fromEmail)}</div>
          </div>
          <div class=\"party\">
            <div class=\"party-label\">{$toLabel}</div>
            <div class=\"party-name\">{$this->e($toName)}</div>
            <div class=\"party-email\">{$this->e($toEmail)}</div>
          </div>
        </div>";
    }

    private function dateChips(array $chips): string
    {
        $html = '<div class="date-chips">';
        foreach ($chips as $chip) {
            if (empty($chip['value'])) continue;
            $warn  = !empty($chip['warn'])  ? ' date-chip--warn'  : '';
            $green = !empty($chip['green']) ? ' date-chip--green' : '';
            $html .= "
            <div class=\"date-chip{$warn}{$green}\">
              <div class=\"date-chip-label\">{$chip['label']}</div>
              <div class=\"date-chip-value\">{$chip['value']}</div>
            </div>";
        }
        $html .= '</div>';
        return $html;
    }

    private function itemsTable(string $rows): string
    {
        return "
        <table class=\"items-table\">
          <thead><tr><th>Description</th><th>Unit</th><th>Amount</th></tr></thead>
          <tbody>{$rows}</tbody>
        </table>";
    }

    private function lineItemRow(string $desc, string $unit, string $amount): string
    {
        return "<tr><td>{$desc}</td><td>{$unit}</td><td>{$amount}</td></tr>";
    }

    private function notesBox(string $heading, string $content): string
    {
        return "
        <div class=\"notes-box\">
          <div class=\"notes-box-heading\">{$heading}</div>
          <div class=\"notes-box-body\">{$content}</div>
        </div>";
    }

    private function disclosureBox(string $text): string
    {
        return "
        <div class=\"disclosure\">
          {$this->e($text)}
        </div>";
    }

    private function sectionHeading(string $text): string
    {
        return "<h3 class=\"section-heading\">{$text}</h3>";
    }

    private function sigBlock(
        string $role,
        ?string $displayName,
        ?string $signatureName,
        mixed $signedAt,
    ): string {
        if ($signatureName && $signedAt) {
            $signedAtStr = $signedAt instanceof \DateTimeInterface
                ? $signedAt->format('F j, Y \a\t g:i A T')
                : (string) $signedAt;
            return "
            <div class=\"sig-block\">
              <div class=\"sig-role\">{$this->e($role)}</div>
              <div class=\"sig-name\">{$this->e($signatureName)}</div>
              <div class=\"sig-meta\">
                Signed: {$this->e($signedAtStr)}<br>
                Registered name: {$this->e($displayName ?? '—')}
              </div>
            </div>";
        }

        return "
        <div class=\"sig-block sig-block--pending\">
          <div class=\"sig-role\">{$this->e($role)}</div>
          <div class=\"sig-pending\">Signature pending…</div>
          <div class=\"sig-meta\">Registered name: {$this->e($displayName ?? '—')}</div>
        </div>";
    }

    // ─────────────────────────────────────────────────────────────────────
    // CSS — single source of truth for all document types
    // ─────────────────────────────────────────────────────────────────────

    private function css(): string
    {
        return <<<'CSS'
    /* ── Reset ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Design tokens ── */
    :root {
      --c-gold:       #8a7d52;
      --c-gold-light: #f0e8d0;
      --c-gold-muted: #e8dfc6;
      --c-sand:       #fbf8f1;
      --c-sand-dark:  #f7f5ef;
      --c-green:      #2f7d54;
      --c-green-light:#f0faf4;
      --c-red:        #c0392b;
      --c-text:       #2a2a2a;
      --c-text-light: #8a8378;
      --c-border:     #e4dfd7;
      --c-border-alt: #e8dfc6;
    }

    /* ── Page ── */
    body {
      font-family: Georgia, 'Times New Roman', serif;
      background: var(--c-sand-dark);
      color: var(--c-text);
      padding: 32px 16px;
      font-size: 14px;
      line-height: 1.6;
    }

    .page {
      background: #fff;
      max-width: 720px;
      margin: 0 auto;
      padding: 48px 52px;
      border: 1px solid var(--c-border);
      border-radius: 8px;
      box-shadow: 0 2px 24px rgba(0,0,0,0.07);
    }

    @media print {
      .no-print { display: none !important; }
      body { background: #fff; padding: 0; }
      .page { box-shadow: none; border: none; max-width: 100%; padding: 0; border-radius: 0; }
    }

    /* ── Print bar ── */
    .print-bar {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      padding-bottom: 20px;
      margin-bottom: 28px;
      border-bottom: 1px solid var(--c-border-alt);
    }

    .btn-print {
      padding: 8px 18px;
      background: var(--c-gold);
      color: #fff;
      border: 0;
      border-radius: 5px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      font-family: inherit;
      letter-spacing: 0.2px;
    }
    .btn-print:hover { background: #705f3e; }
    .btn-print--secondary { background: #5b6877; }
    .btn-print--secondary:hover { background: #48535f; }

    /* ── Header ── */
    .doc-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 20px;
    }

    .aegis-logo {
      font-size: 24px;
      font-weight: 700;
      color: #2d2a26;
      letter-spacing: -0.3px;
    }

    .doc-type {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--c-gold);
      margin-top: 3px;
    }

    .doc-header-right { text-align: right; }

    .doc-total {
      font-size: 26px;
      font-weight: 700;
      color: var(--c-green);
      line-height: 1.1;
    }

    .doc-total-label {
      font-size: 11px;
      color: var(--c-text-light);
      margin-top: 2px;
    }

    /* ── Title row ── */
    .title-row {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--c-border-alt);
      flex-wrap: wrap;
    }

    .doc-title {
      font-size: 20px;
      font-weight: 700;
      color: #2d2a26;
      line-height: 1.2;
      flex: 1;
    }

    /* ── Status badge ── */
    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      white-space: nowrap;
    }

    .status--green  { background: #e8f5ee; color: var(--c-green); }
    .status--red    { background: #fdecea; color: var(--c-red); }
    .status--gold   { background: var(--c-gold-light); color: #6b5e35; }
    .status--neutral{ background: #f0ece3; color: #5b5247; }

    /* ── Party grid ── */
    .party-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }

    .party {
      background: var(--c-sand);
      border: 1px solid var(--c-border-alt);
      border-radius: 6px;
      padding: 14px 16px;
    }

    .party-label {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--c-gold);
      margin-bottom: 4px;
    }

    .party-name {
      font-size: 14px;
      font-weight: 700;
      color: var(--c-text);
    }

    .party-email {
      font-size: 12px;
      color: var(--c-text-light);
      margin-top: 2px;
    }

    /* ── Date chips ── */
    .date-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 24px;
    }

    .date-chip {
      background: var(--c-sand);
      border: 1px solid var(--c-border-alt);
      border-radius: 6px;
      padding: 10px 14px;
      min-width: 130px;
    }

    .date-chip--warn  { border-color: var(--c-red);   background: #fdecea; }
    .date-chip--green { border-color: var(--c-green); background: var(--c-green-light); }

    .date-chip-label {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--c-gold);
      margin-bottom: 3px;
    }
    .date-chip--warn  .date-chip-label { color: var(--c-red); }
    .date-chip--green .date-chip-label { color: var(--c-green); }

    .date-chip-value {
      font-size: 13px;
      font-weight: 700;
      color: var(--c-text);
    }

    /* ── Line items table ── */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
      font-size: 13px;
      border: 1px solid var(--c-border-alt);
      border-radius: 6px;
      overflow: hidden;
    }

    .items-table thead tr {
      background: var(--c-sand-dark);
    }

    .items-table th {
      text-align: left;
      padding: 10px 14px;
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--c-gold);
      border-bottom: 1px solid var(--c-border-alt);
    }

    .items-table th:last-child,
    .items-table td:last-child { text-align: right; }

    .items-table td {
      padding: 10px 14px;
      border-top: 1px solid var(--c-border-alt);
      color: #3a3530;
    }

    .items-table tr.items-total td {
      font-weight: 700;
      font-size: 15px;
      background: var(--c-sand);
      border-top: 2px solid var(--c-border-alt);
    }

    .items-table tr.items-total td:last-child { color: var(--c-green); }

    .items-table tr.items-paid td {
      font-size: 12px;
      color: var(--c-green);
      background: var(--c-green-light);
    }

    /* ── Notes box ── */
    .notes-box {
      background: var(--c-sand);
      border: 1px solid var(--c-border-alt);
      border-radius: 6px;
      padding: 14px 18px;
      margin-bottom: 20px;
    }

    .notes-box-heading {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--c-gold);
      margin-bottom: 6px;
    }

    .notes-box-body {
      font-size: 13px;
      color: #3a3530;
      line-height: 1.7;
    }

    /* ── Disclosure box ── */
    .disclosure {
      background: var(--c-green-light);
      border-left: 3px solid var(--c-green);
      border-radius: 0 6px 6px 0;
      padding: 12px 16px;
      font-size: 12px;
      color: #2f5c3a;
      line-height: 1.6;
      margin-bottom: 20px;
    }

    /* ── Contract meta grid ── */
    .meta-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px 24px;
      background: var(--c-sand);
      border: 1px solid var(--c-border-alt);
      border-radius: 6px;
      padding: 16px 20px;
      margin-bottom: 20px;
    }

    .meta-label {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--c-gold);
      margin-bottom: 2px;
    }

    .meta-value {
      font-size: 13px;
      font-weight: 600;
      color: var(--c-text);
    }

    /* ── Section heading ── */
    .section-heading {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--c-gold);
      border-bottom: 1px solid var(--c-border-alt);
      padding-bottom: 6px;
      margin: 24px 0 12px;
    }

    /* ── Milestones table ── */
    .milestones-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
      margin-bottom: 8px;
      border: 1px solid var(--c-border-alt);
      border-radius: 6px;
      overflow: hidden;
    }

    .milestones-table th {
      text-align: left;
      padding: 8px 12px;
      background: var(--c-sand-dark);
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      color: var(--c-gold);
      border-bottom: 1px solid var(--c-border-alt);
    }

    .milestones-table td {
      padding: 9px 12px;
      border-top: 1px solid var(--c-border-alt);
      color: #3a3530;
    }

    .ms-badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 10px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.3px;
      background: #f0ece3;
      color: #5b5247;
    }
    .ms-badge--released,
    .ms-badge--paid,
    .ms-badge--approved  { background: #e8f5ee; color: var(--c-green); }
    .ms-badge--disputed,
    .ms-badge--refunded  { background: #fdecea; color: var(--c-red); }
    .ms-badge--funded,
    .ms-badge--in_progress { background: var(--c-gold-light); color: #6b5e35; }
    .ms-badge--submitted { background: #e8f0fd; color: #3b5bdb; }

    /* ── Terms ── */
    .terms-pre {
      font-family: Georgia, 'Times New Roman', serif;
      font-size: 12.5px;
      color: #3a3530;
      white-space: pre-wrap;
      line-height: 1.7;
      margin-bottom: 20px;
    }

    /* ── Signature blocks ── */
    .sig-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-top: 32px;
      padding-top: 24px;
      border-top: 2px solid var(--c-border-alt);
    }

    .sig-block {
      border: 1px solid var(--c-border-alt);
      border-radius: 6px;
      padding: 16px 18px;
    }

    .sig-block--pending { background: var(--c-sand); }

    .sig-role {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--c-gold);
      margin-bottom: 10px;
    }

    .sig-name {
      font-size: 20px;
      font-style: italic;
      color: var(--c-text);
      font-family: Georgia, serif;
      border-bottom: 1px solid #ccc;
      padding-bottom: 6px;
      margin-bottom: 8px;
    }

    .sig-meta {
      font-size: 11px;
      color: var(--c-text-light);
      line-height: 1.6;
    }

    .sig-pending {
      font-size: 13px;
      color: #b0a090;
      font-style: italic;
      padding: 8px 0;
    }

    .sig-legal {
      font-size: 11px;
      color: var(--c-text-light);
      line-height: 1.6;
      margin-top: 16px;
      text-align: center;
    }

    /* ── Footer ── */
    .doc-footer {
      margin-top: 36px;
      padding-top: 16px;
      border-top: 1px solid var(--c-border);
      font-size: 11px;
      color: var(--c-text-light);
      text-align: center;
      line-height: 1.8;
    }

    .doc-footer-inner {
      display: flex;
      gap: 8px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .footer-link { color: var(--c-gold); text-decoration: none; }
    .footer-link:hover { text-decoration: underline; }

    .doc-footer-id {
      margin-top: 4px;
      font-family: monospace;
      font-size: 10px;
      color: #b0a890;
    }
CSS;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Utility helpers
    // ─────────────────────────────────────────────────────────────────────

    private function enumVal(mixed $v): string
    {
        return $v instanceof \BackedEnum ? $v->value : (string) ($v ?? '');
    }

    private function e(?string $v): string
    {
        return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
