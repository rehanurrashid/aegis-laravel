<!--
  BpInvoiceRow.vue — BP invoice table row (sic-row pattern).
  3 columns: Party+meta, Status badges, Chevron.
  All detail + actions live in the inline AegisModal.
-->
<template>
  <!-- ── TABLE ROW ── -->
  <tr
    class="bpir-row"
    :class="`bpir-row--${sv(invoice.status)}`"
  >
    <!-- Col 1: Avatar + name + contract + invoice # -->
    <td class="bpir-td bpir-td--party" @click="open = true">
      <div class="bpir-party">
        <div class="bpir-avatar">{{ initials(invoice.bp_name) }}</div>
        <div class="bpir-party-info">
          <a
            v-if="invoice.bp_slug"
            :href="`/public/bp/${invoice.bp_slug}`"
            class="bpir-party-name bpir-party-name--link"
            @click.stop
          >{{ invoice.bp_name }}</a>
          <span v-else class="bpir-party-name">{{ invoice.bp_name }}</span>
          <span class="bpir-service-name">{{ invoice.contract_title }}</span>
          <span class="bpir-date-sub">
            #{{ invoice.invoice_number }}
            <template v-if="invoice.issued_month"> · {{ invoice.issued_month }}</template>
          </span>
        </div>
      </div>
    </td>

    <!-- Col 2: Status badges -->
    <td class="bpir-td bpir-td--status" @click="open = true">
      <div class="bpir-badges">
        <AegisBadge :label="statusLabel(invoice.status)" :variant="statusVariant(invoice.status)" />
        <AegisBadge v-if="invoice.active_dispute_id" label="Disputed" variant="red" />
      </div>
    </td>

    <!-- Col 3: Chevron -->
    <td class="bpir-td bpir-td--actions" @click="open = true">
      <button
        type="button"
        class="btn-icon"
        data-tooltip="View details & actions"
        @click="open = true"
      >
        <AegisIcon name="chevron-right" :size="15" />
      </button>
    </td>
  </tr>

  <!-- ── DETAIL MODAL ── -->
  <AegisModal v-model="open" title="Invoice Details" size="md">
    <template #default>

      <!-- Party header -->
      <div class="bpir-modal-party">
        <div class="bpir-avatar bpir-avatar--lg">{{ initials(invoice.bp_name) }}</div>
        <div class="bpir-modal-party-info">
          <span class="bpir-modal-party-name">{{ invoice.bp_name }}</span>
          <span class="bpir-modal-service">{{ invoice.contract_title }}</span>
        </div>
        <div class="bpir-modal-badges">
          <AegisBadge :label="statusLabel(invoice.status)" :variant="statusVariant(invoice.status)" />
          <AegisBadge v-if="invoice.active_dispute_id" label="Disputed" variant="red" />
        </div>
      </div>

      <!-- Meta row -->
      <div class="bpir-modal-meta">
        <span class="bpir-modal-meta-item">
          <AegisIcon name="hash" :size="12" /> {{ invoice.invoice_number }}
        </span>
        <span v-if="invoice.issued_month" class="bpir-modal-meta-item">
          <AegisIcon name="calendar" :size="12" /> {{ invoice.issued_month }}
        </span>
        <span class="bpir-connect-pill" :class="invoice.bp_connected ? 'is-connected' : 'is-not-connected'">
          <span class="bpir-connect-dot"></span>
          {{ invoice.bp_connected ? 'Stripe Connected' : 'Not Connected' }}
        </span>
      </div>

      <!-- Amount breakdown -->
      <div class="bpir-modal-amounts">
        <div class="bpir-modal-amounts-head">
          <span>Invoice Total</span>
          <span class="bpir-modal-total">{{ formatCents(invoice.total_cents) }}</span>
        </div>
        <div class="bpir-modal-amount-row">
          <span class="bpir-modal-amount-label">
            <AegisIcon name="calendar" :size="12" /> Due Date
          </span>
          <span class="bpir-modal-amount-val" :class="isOverdue ? 'bpir-modal-amount-val--overdue' : ''">
            {{ invoice.due_at ?? '—' }}
          </span>
        </div>
        <div v-if="invoice.paid_at" class="bpir-modal-amount-row">
          <span class="bpir-modal-amount-label">
            <AegisIcon name="check-circle" :size="12" /> Paid On
          </span>
          <span class="bpir-modal-amount-val bpir-modal-amount-val--paid">
            {{ formatDate(invoice.paid_at) }}
          </span>
        </div>
        <div v-if="invoice.notes_short" class="bpir-modal-amount-row">
          <span class="bpir-modal-amount-label">
            <AegisIcon name="file-text" :size="12" /> Notes
          </span>
          <span class="bpir-modal-amount-val bpir-modal-amount-val--notes">
            {{ invoice.notes_short }}
          </span>
        </div>
      </div>

      <!-- Auto-approve notice -->
      <div
        v-if="sv(invoice.status) === 'sent' && !invoice.active_dispute_id"
        class="bpir-modal-auto-notice"
      >
        <AegisIcon name="clock" :size="13" />
        This invoice auto-approves in 5 days if no action is taken.
      </div>

      <!-- Not connected warning -->
      <div v-if="invoice.payable && !invoice.bp_connected" class="bpir-modal-warn">
        <AegisIcon name="alert-circle" :size="13" />
        This Business Partner has not connected Stripe. Payment cannot be processed until they do.
      </div>

    </template>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="open = false">Close</button>

      <!-- Disputed -->
      <template v-if="invoice.active_dispute_id">
        <button
          type="button"
          class="btn btn-outline"
          @click="$emit('dispute', invoice); open = false"
        >
          <AegisIcon name="alert-triangle" :size="13" /> View Dispute
        </button>
      </template>

      <!-- Payable -->
      <template v-else-if="invoice.payable">
        <button
          type="button"
          class="btn btn-outline"
          @click="$emit('dispute', invoice); open = false"
        >
          <AegisIcon name="alert-triangle" :size="13" /> Dispute
        </button>
        <button
          type="button"
          class="btn btn-danger"
          @click="$emit('reject', invoice); open = false"
        >
          <AegisIcon name="x" :size="13" /> Reject
        </button>
        <button
          type="button"
          class="btn btn-success"
          :disabled="!invoice.bp_connected"
          :data-tooltip="!invoice.bp_connected ? 'BP must connect Stripe first' : ''"
          @click="$emit('approve', invoice); open = false"
        >
          <AegisIcon name="check" :size="13" /> Approve &amp; Pay {{ formatCents(invoice.total_cents) }}
        </button>
      </template>

      <!-- Paid / void -->
      <template v-else>
        <!-- no extra actions -->
      </template>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  invoice: { type: Object, required: true },
})

defineEmits(['approve', 'reject', 'view', 'dispute'])

const open = ref(false)

const sv = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

const isOverdue = computed(() => sv(props.invoice.status) === 'overdue')

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
/* ── TABLE ROW ── */
.bpir-row {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  transition: background var(--transition);
  cursor: pointer;
}
.bpir-row:last-child { border-bottom: none; }
.bpir-row:hover { background: var(--surface-2); }

.bpir-row--sent     td:first-child { border-left: 3px solid var(--gold); }
.bpir-row--overdue  td:first-child { border-left: 3px solid var(--red); }
.bpir-row--disputed td:first-child { border-left: 3px solid var(--red); }
.bpir-row--paid     td:first-child { border-left: 3px solid var(--green); }
.bpir-row--void     td:first-child { border-left: 3px solid var(--border-dark); opacity: 0.7; }

.bpir-td { padding: 10px 12px; vertical-align: middle; font-family: var(--font-sans); border-bottom: 1px solid var(--border); }
.bpir-td--party   { width: 58%; }
.bpir-td--status  { width: 34%; }
.bpir-td--actions { width: 8%; text-align: right; }

/* Party cell — mirrors sic-party */
.bpir-party { display: flex; align-items: center; gap: 9px; }
.bpir-avatar {
  width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
  background: var(--badge-bg-gold); color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700;
}
.bpir-avatar--lg { width: 40px; height: 40px; font-size: 13px; }
.bpir-party-info { min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.bpir-party-name  { font-size: 13px; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bpir-party-name--link { color: var(--gold-dark); text-decoration: none; }
.bpir-party-name--link:hover { text-decoration: underline; color: var(--gold); }
.bpir-service-name { font-size: 11px; color: var(--text-4); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bpir-date-sub    { font-size: 11px; color: var(--text-4); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Status */
.bpir-badges { display: flex; flex-wrap: wrap; gap: 4px; }

/* ── MODAL ── */
.bpir-modal-party {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px; margin-bottom: 14px;
  background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius);
}
.bpir-modal-party-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.bpir-modal-party-name { font-size: 15px; font-weight: 700; color: var(--text); }
.bpir-modal-service    { font-size: 12px; color: var(--text-3); font-weight: 600; }
.bpir-modal-badges     { display: flex; gap: 5px; flex-wrap: wrap; }

.bpir-modal-meta { display: flex; flex-wrap: wrap; gap: 8px 14px; align-items: center; margin-bottom: 14px; }
.bpir-modal-meta-item { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: var(--text-3); }

.bpir-connect-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 100px; border: 1px solid var(--border); background: var(--surface-2); }
.bpir-connect-pill.is-connected { color: var(--green-dark, #2e7d32); border-color: var(--green); background: rgba(34,197,94,.07); }
.bpir-connect-pill.is-not-connected { color: var(--gold-dark); border-color: var(--gold); }
.bpir-connect-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }

.bpir-modal-amounts { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 12px; }
.bpir-modal-amounts-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 8px 14px; background: var(--surface-3); border-bottom: 1px solid var(--border);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--text-4);
}
.bpir-modal-total { font-size: 16px; font-weight: 700; color: var(--text); font-family: var(--font-serif, serif); text-transform: none; letter-spacing: 0; }
.bpir-modal-amount-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 14px; border-top: 1px solid var(--border); font-size: 13px; }
.bpir-modal-amount-label { display: flex; align-items: center; gap: 5px; font-weight: 600; color: var(--text-3); font-size: 12px; }
.bpir-modal-amount-val { font-weight: 700; color: var(--text); }
.bpir-modal-amount-val--overdue { color: var(--red); }
.bpir-modal-amount-val--paid    { color: var(--green); }
.bpir-modal-amount-val--notes   { font-weight: 400; color: var(--text-3); font-size: 12px; max-width: 180px; text-align: right; }

.bpir-modal-auto-notice {
  display: flex; align-items: center; gap: 7px;
  padding: 9px 12px; border-radius: var(--radius);
  background: rgba(160,129,62,0.07); border: 1px solid var(--gold);
  font-size: 12px; font-weight: 600; color: var(--gold-dark);
  margin-bottom: 10px;
}
.bpir-modal-warn {
  display: flex; align-items: center; gap: 7px;
  padding: 9px 12px; border-radius: var(--radius);
  background: rgba(239,68,68,0.06); border: 1px solid var(--red);
  font-size: 12px; font-weight: 600; color: var(--red);
  margin-bottom: 10px;
}
</style>
