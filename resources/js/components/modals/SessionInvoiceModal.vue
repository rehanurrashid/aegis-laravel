<!--
  SessionInvoiceModal.vue — full session invoice view modal.

  Displays a complete invoice breakdown for a clinical session.
  Works for both client (what I paid) and provider (what I received).
  Includes a print/download button.

  Props:
    modelValue  — v-model (bool, open/close)
    session     — shaped session object
    viewpoint   — 'client' | 'provider'

  AegisBadge, AegisIcon, AegisModal globally registered.
-->
<template>
  <AegisModal
    :model-value="modelValue"
    title="Session Invoice"
    size="md"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template v-if="session">

      <!-- PAID / DUE watermark badge ─────────────────────────────── -->
      <div class="sim-watermark-row">
        <span class="sim-invoice-number">{{ session.invoice_number }}</span>
        <span class="sim-watermark-badge" :class="`sim-watermark--${watermarkVariant}`">
          {{ watermarkLabel }}
        </span>
      </div>

      <!-- Parties ─────────────────────────────────────────────────── -->
      <div class="sim-parties">
        <div class="sim-party">
          <div class="sim-party-label">Client (Payer)</div>
          <div class="sim-party-name">
            {{ viewpoint === 'client' ? 'You' : session.client_name }}
          </div>
        </div>
        <div class="sim-party-arrow"><AegisIcon name="arrow-right" :size="16" /></div>
        <div class="sim-party">
          <div class="sim-party-label">Provider (Recipient)</div>
          <div class="sim-party-name">
            {{ viewpoint === 'provider' ? 'You' : session.practitioner_name }}
          </div>
        </div>
      </div>

      <!-- Service details ─────────────────────────────────────────── -->
      <div class="sim-detail-grid">
        <div class="sim-detail-row">
          <span class="sim-detail-label">Service</span>
          <span class="sim-detail-value">{{ session.service_title }}</span>
        </div>
        <div class="sim-detail-row">
          <span class="sim-detail-label">Scheduled</span>
          <span class="sim-detail-value">{{ session.datetime_label || 'TBD' }}</span>
        </div>
        <div v-if="session.duration_label && session.duration_label !== '—'" class="sim-detail-row">
          <span class="sim-detail-label">Duration</span>
          <span class="sim-detail-value">{{ session.duration_label }}</span>
        </div>
        <div class="sim-detail-row">
          <span class="sim-detail-label">Session Status</span>
          <span class="sim-detail-value">
            <AegisBadge :label="sessionStatusLabel" :variant="sessionStatusVariant" />
          </span>
        </div>
      </div>

      <!-- Amount breakdown ─────────────────────────────────────────── -->
      <div class="sim-breakdown">
        <div class="sim-breakdown-title">Payment Breakdown</div>

        <div v-if="session.negotiated_amount_cents" class="sim-line sim-line--sub">
          <span>Original listing price</span>
          <span class="sim-line-strike">${{ fmtCents(session.original_amount_cents) }}</span>
        </div>

        <div class="sim-line sim-line--total">
          <span>{{ session.negotiated_amount_cents ? 'Agreed price' : 'Service rate' }}</span>
          <span>{{ session.amount }}</span>
        </div>

        <div class="sim-line" :class="depositPaid ? 'sim-line--paid' : 'sim-line--due'">
          <span>
            <AegisIcon :name="depositPaid ? 'check-circle' : 'circle'" :size="13" />
            Deposit (30%)
          </span>
          <span>{{ session.expected_deposit_label }}</span>
        </div>
        <div v-if="depositPaid" class="sim-line sim-line--date">
          <span></span>
          <span>Paid {{ session.deposit_paid_at }}</span>
        </div>

        <div class="sim-line" :class="balancePaid ? 'sim-line--paid' : 'sim-line--due'">
          <span>
            <AegisIcon :name="balancePaid ? 'check-circle' : 'circle'" :size="13" />
            Balance (70%)
          </span>
          <span>{{ session.expected_balance_label }}</span>
        </div>
        <div v-if="balancePaid" class="sim-line sim-line--date">
          <span></span>
          <span>Paid {{ session.balance_paid_at }}</span>
        </div>

        <!-- Refund rows -->
        <template v-if="session.total_refunded_cents > 0">
          <div class="sim-line sim-line--refund">
            <span><AegisIcon name="arrow-left" :size="13" /> Total Refunded</span>
            <span>–{{ session.total_refunded_label }}</span>
          </div>
        </template>

        <!-- Net total -->
        <div class="sim-line sim-line--net">
          <span>Net Total</span>
          <span>{{ netLabel }}</span>
        </div>
      </div>

      <!-- Stripe note ─────────────────────────────────────────────── -->
      <p class="sim-note">
        Payments are processed via Stripe Connect. Funds transfer directly to the
        provider's connected bank account — Aegis does not hold your money.
        Allow 5–10 business days for refunds to appear on your statement.
      </p>

    </template>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="$emit('update:modelValue', false)">Close</button>
      <button type="button" class="btn btn-outline" @click="printInvoice">
        <AegisIcon name="download" :size="13" />
        Download PDF
      </button>
      <a
        v-if="session"
        :href="`/provider/services/sessions/${session.id}/invoice`"
        class="btn btn-primary"
        target="_blank"
        rel="noopener"
      >
        <AegisIcon name="external-link" :size="13" />
        Open Invoice
      </a>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  session:    { type: Object,  default: null },
  viewpoint:  { type: String,  default: 'client' },
})

defineEmits(['update:modelValue'])

const depositPaid = computed(() =>
  ['deposit_paid', 'paid', 'refunded', 'partially_refunded'].includes(props.session?.payment_status)
)
const balancePaid = computed(() =>
  ['paid', 'refunded', 'partially_refunded'].includes(props.session?.payment_status)
)

const watermarkLabel = computed(() => {
  const s = props.session?.payment_status
  if (s === 'paid')               return 'PAID'
  if (s === 'deposit_paid')       return 'BALANCE DUE'
  if (s === 'refunded')           return 'REFUNDED'
  if (s === 'partially_refunded') return 'PARTIALLY REFUNDED'
  return 'DEPOSIT DUE'
})

const watermarkVariant = computed(() => {
  const s = props.session?.payment_status
  if (s === 'paid')         return 'paid'
  if (s === 'refunded' || s === 'partially_refunded') return 'refunded'
  return 'due'
})

const sessionStatusLabel = computed(() => ({
  scheduled: 'Scheduled', completed: 'Completed',
  cancelled: 'Cancelled', no_show: 'No Show',
}[props.session?.status] ?? props.session?.status ?? '—'))

const sessionStatusVariant = computed(() => ({
  scheduled: 'blue', completed: 'green', cancelled: 'neutral', no_show: 'red',
}[props.session?.status] ?? 'neutral'))

const netLabel = computed(() => {
  if (!props.session) return '$0.00'
  const total    = (props.session.agreed_amount_cents ?? props.session.amount_cents ?? 0)
  const refunded = (props.session.total_refunded_cents ?? 0)
  const net      = total - refunded
  return '$' + (net / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })
})

function fmtCents(cents) {
  if (!cents) return '0.00'
  return (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function printInvoice() {
  if (!props.session) return
  window.open(`/provider/services/sessions/${props.session.id}/invoice`, '_blank')
}
</script>

<style scoped>
.sim-watermark-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}
.sim-invoice-number {
  font-size: 12px; font-weight: 700; color: var(--text-4);
  font-family: var(--font-mono, monospace);
  letter-spacing: 0.5px;
}
.sim-watermark-badge {
  font-size: 11px; font-weight: 800; letter-spacing: 1.5px;
  text-transform: uppercase; padding: 4px 12px;
  border-radius: 100px; border: 2px solid;
}
.sim-watermark--paid    { color: var(--green); border-color: var(--green); background: rgba(34,197,94,.07); }
.sim-watermark--due     { color: var(--gold-dark); border-color: var(--gold); background: rgba(245,158,11,.07); }
.sim-watermark--refunded { color: var(--text-3); border-color: var(--border-dark); background: var(--surface-2); }

.sim-parties {
  display: flex; align-items: center; gap: 10px;
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 12px 14px;
  margin-bottom: 14px;
}
.sim-party { flex: 1; }
.sim-party-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-4); margin-bottom: 2px; }
.sim-party-name  { font-size: 13px; font-weight: 700; color: var(--text); }
.sim-party-arrow { color: var(--text-4); }

.sim-detail-grid { margin-bottom: 14px; }
.sim-detail-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 6px 0; border-bottom: 1px solid var(--border);
  font-size: 13px;
}
.sim-detail-row:last-child { border-bottom: none; }
.sim-detail-label { color: var(--text-3); font-weight: 600; }
.sim-detail-value { font-weight: 700; color: var(--text); }

.sim-breakdown {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 14px;
  margin-bottom: 12px;
}
.sim-breakdown-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-4); margin-bottom: 8px; }
.sim-line {
  display: flex; justify-content: space-between; align-items: center;
  padding: 5px 0; border-bottom: 1px solid var(--border);
  font-size: 13px; font-weight: 600; color: var(--text);
  gap: 8px;
}
.sim-line > span:first-child { display: flex; align-items: center; gap: 5px; }
.sim-line:last-child { border-bottom: none; }
.sim-line--sub    { color: var(--text-4); font-size: 12px; }
.sim-line--strike { text-decoration: line-through; }
.sim-line--total  { font-size: 14px; font-weight: 700; }
.sim-line--paid   { color: var(--green); }
.sim-line--due    { color: var(--text-3); }
.sim-line--date   { font-size: 11px; color: var(--text-4); padding-top: 0; border-bottom: none; }
.sim-line--refund { color: var(--red); }
.sim-line--net    { font-size: 15px; font-weight: 800; color: var(--text); padding-top: 8px; margin-top: 4px; border-top: 2px solid var(--border-dark); border-bottom: none; }

.sim-note {
  font-size: 11px; color: var(--text-4); line-height: 1.6; margin: 0;
}
</style>
