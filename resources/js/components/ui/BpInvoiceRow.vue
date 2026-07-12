<!--
  BpInvoiceRow.vue — single BP invoice row in the BpFinanceTable Invoices sub-tab.

  Props:
    invoice — full invoice object from FinancesController bpInvoices array
              { id, invoice_number, bp_name, bp_connected, contract_title,
                total_cents, status, issued_month, due_at, notes_short,
                payable, active_dispute_id, kind, paid_at }

  Emits:
    approve(invoice)   — open Approve & Pay modal
    reject(invoice)    — open Reject modal
    view(invoice)      — open ViewInvoiceModal
    dispute(invoice)   — open OpenDisputeModal
-->
<template>
  <tr
    class="bpir-row"
    :class="`bpir-row--${sv(invoice.status)}`"
    @click="$emit('view', invoice)"
  >
    <!-- BP name + connect status -->
    <td class="bpir-td bpir-td--party">
      <div class="bpir-party">
        <div class="bpir-avatar">{{ initials(invoice.bp_name) }}</div>
        <div class="bpir-party-info">
          <div class="bpir-party-name">{{ invoice.bp_name }}</div>
          <div class="bpir-party-sub">
            <span
              class="connect-dot"
              :class="invoice.bp_connected ? 'is-connected' : 'is-not-connected'"
              :data-tooltip="invoice.bp_connected ? 'Stripe Connected' : 'Not connected — cannot pay until BP connects Stripe'"
            ></span>
            {{ invoice.bp_connected ? 'Connected' : 'Not connected' }}
          </div>
        </div>
      </div>
    </td>

    <!-- Contract + invoice number -->
    <td class="bpir-td bpir-td--contract">
      <div class="bpir-contract-title">{{ invoice.contract_title }}</div>
      <div class="bpir-invoice-num">#{{ invoice.invoice_number }}</div>
    </td>

    <!-- Amount -->
    <td class="bpir-td bpir-td--amount">
      <div class="bpir-amount">{{ formatCents(invoice.total_cents) }}</div>
      <div class="bpir-period">{{ invoice.issued_month }}</div>
    </td>

    <!-- Status -->
    <td class="bpir-td bpir-td--status">
      <AegisBadge :label="statusLabel(invoice.status)" :variant="statusVariant(invoice.status)" />
      <!-- Dispute chip -->
      <span v-if="invoice.active_dispute_id" class="bpir-dispute-chip">
        <AegisIcon name="alert-triangle" :size="10" />
        Disputed
      </span>
      <!-- Auto-approve countdown (sent invoices) -->
      <span v-if="sv(invoice.status) === 'sent' && !invoice.active_dispute_id" class="bpir-auto-chip">
        <AegisIcon name="clock" :size="10" />
        Auto-approves in 5 days
      </span>
    </td>

    <!-- Due date -->
    <td class="bpir-td bpir-td--due">
      <span :class="isOverdue ? 'bpir-due--overdue' : ''">
        {{ invoice.due_at ?? '—' }}
      </span>
      <div v-if="invoice.paid_at" class="bpir-paid-on">
        Paid {{ formatDate(invoice.paid_at) }}
      </div>
    </td>

    <!-- Actions -->
    <td class="bpir-td bpir-td--actions" @click.stop>
      <template v-if="invoice.active_dispute_id">
        <!-- Disputed: only show view dispute -->
        <button
          type="button"
          class="btn btn-outline bpir-action-btn"
          data-tooltip="View dispute"
          @click="$emit('dispute', invoice)"
        >
          <AegisIcon name="alert-triangle" :size="13" />
          View Dispute
        </button>
      </template>
      <template v-else-if="invoice.payable">
        <!-- Payable: Approve & Pay + view + dispute + reject -->
        <button
          type="button"
          class="btn btn-success bpir-action-btn"
          :disabled="!invoice.bp_connected"
          :data-tooltip="!invoice.bp_connected ? 'BP must connect Stripe before payment' : 'Approve and pay this invoice'"
          @click="$emit('approve', invoice)"
        >
          <AegisIcon name="check" :size="13" />
          Approve &amp; Pay
        </button>
        <button
          type="button"
          class="btn-icon"
          data-tooltip="View invoice"
          @click="$emit('view', invoice)"
        >
          <AegisIcon name="eye" :size="14" />
        </button>
        <button
          type="button"
          class="btn-icon"
          data-tooltip="Open dispute"
          @click="$emit('dispute', invoice)"
        >
          <AegisIcon name="alert-triangle" :size="14" />
        </button>
        <button
          type="button"
          class="btn-icon btn-icon-danger"
          data-tooltip="Reject invoice"
          @click="$emit('reject', invoice)"
        >
          <AegisIcon name="x" :size="14" />
        </button>
      </template>
      <template v-else>
        <!-- Paid / void: view receipt only -->
        <button
          type="button"
          class="btn-icon"
          data-tooltip="View receipt"
          @click="$emit('view', invoice)"
        >
          <AegisIcon name="eye" :size="14" />
        </button>
      </template>
    </td>
  </tr>
</template>

<script setup>
const props = defineProps({
  invoice: { type: Object, required: true },
})

defineEmits(['approve', 'reject', 'view', 'dispute'])

const sv = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

const isOverdue = ['overdue'].includes(sv(props.invoice.status))

function formatCents(c) {
  return '$' + ((c ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })
}
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
function initials(name) {
  if (!name) return 'BP'
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

function statusLabel(s) {
  return ({
    sent:     'Awaiting Approval',
    overdue:  'Overdue',
    disputed: 'Disputed',
    paid:     'Paid',
    void:     'Void',
    draft:    'Draft',
  })[sv(s)] ?? sv(s)
}
function statusVariant(s) {
  return ({
    sent:     'gold',
    overdue:  'red',
    disputed: 'red',
    paid:     'green',
    void:     'neutral',
    draft:    'neutral',
  })[sv(s)] ?? 'neutral'
}
</script>

<style scoped>
.bpir-row {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  transition: background var(--transition);
  cursor: pointer;
}
.bpir-row:last-child { border-bottom: none; }
.bpir-row:hover { background: var(--surface-2); }

/* Status accent: left border via ::before on first td */
.bpir-row--sent     td:first-child    { border-left: 3px solid var(--gold); }
.bpir-row--overdue  td:first-child    { border-left: 3px solid var(--red); }
.bpir-row--disputed td:first-child    { border-left: 3px solid var(--red); }
.bpir-row--paid     td:first-child    { border-left: 3px solid var(--green); opacity: 0.85; }
.bpir-row--void     td:first-child    { border-left: 3px solid var(--border-dark); opacity: 0.7; }

.bpir-td {
  padding: 13px 12px;
  vertical-align: middle;
  font-family: var(--font-sans);
}

/* Party */
.bpir-party {
  display: flex;
  align-items: center;
  gap: 10px;
}
.bpir-avatar {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  background: var(--gold-dark);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
}
.bpir-party-info { min-width: 0; }
.bpir-party-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.bpir-party-sub {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--text-4);
  margin-top: 2px;
}
.connect-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}
.connect-dot.is-connected     { background: var(--green); }
.connect-dot.is-not-connected { background: var(--text-4); }

/* Contract */
.bpir-contract-title { font-size: 12.5px; font-weight: 600; color: var(--text); margin-bottom: 3px; }
.bpir-invoice-num    { font-size: 11px; color: var(--text-4); }

/* Amount */
.bpir-amount { font-size: 13px; font-weight: 700; color: var(--text); }
.bpir-period { font-size: 11px; color: var(--text-4); margin-top: 2px; }

/* Status */
.bpir-td--status { vertical-align: middle; }
.bpir-dispute-chip {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 10px;
  font-weight: 700;
  color: var(--red);
  background: rgba(239,68,68,0.08);
  border: 1px solid var(--red);
  border-radius: var(--radius-full);
  padding: 2px 6px;
  margin-top: 4px;
  margin-left: 4px;
}
.bpir-auto-chip {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 10px;
  color: var(--text-4);
  margin-top: 4px;
  margin-left: 4px;
}

/* Due */
.bpir-due--overdue { color: var(--red); font-weight: 700; }
.bpir-paid-on { font-size: 10px; color: var(--green-dark, #15803d); margin-top: 2px; }

/* Actions */
.bpir-td--actions {
  white-space: nowrap;
  text-align: right;
}
.bpir-action-btn { font-size: 12px; padding: 4px 10px; height: 28px; }
</style>
