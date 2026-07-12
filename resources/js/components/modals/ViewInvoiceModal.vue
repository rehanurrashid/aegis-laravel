<!--
  ViewInvoiceModal.vue — Centralized invoice/receipt modal.
  Handles all 4 payment types with different visual treatments:
    kind = 'bp_invoice' | 'cs_invoice' | 'session' | 'subscription'

  Props:
    modelValue    — v-model boolean for open/close
    invoice       — the invoice/receipt shape (see below)
    canApprove    — show Approve & Pay button (BP only, if payable)
    onApprove     — callback if canApprove clicked
    onDispute     — optional dispute callback
-->
<template>
  <AegisModal :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)"
    :title="modalTitle" size="lg">
    <div v-if="invoice">
      <!-- Header -->
      <div class="vim-header">
        <div>
          <div class="vim-vendor">{{ recipientName }}</div>
          <div class="vim-vendor-sub">{{ recipientKindLabel }}</div>
        </div>
        <div class="vim-date-block">
          <div class="vim-date-label">{{ dateLabel }}</div>
          <div class="vim-date-value">{{ dateValue }}</div>
        </div>
      </div>

      <!-- Receipt lines -->
      <div class="vim-receipt">
        <div class="vim-receipt-row">
          <span>{{ lineDescription }}</span>
          <span>{{ formatCents(invoice.total_cents) }}</span>
        </div>
        <div v-if="invoice.status === 'paid'" class="vim-receipt-row">
          <span>Payment method</span>
          <span>Card ···· 4242</span>
        </div>
        <div class="vim-receipt-row total">
          <span>Total {{ invoice.status === 'paid' ? 'paid' : 'due' }}</span>
          <span>{{ formatCents(invoice.total_cents) }}</span>
        </div>
      </div>

      <!-- Status badge row -->
      <div class="vim-status-row">
        <AegisBadge :label="statusLabel" :variant="statusVariant" />
        <span v-if="invoice.due_at && invoice.status !== 'paid'" class="vim-due">
          Due: {{ invoice.due_at }}
        </span>
        <span v-if="invoice.scheduled_at" class="vim-due">
          Scheduled: {{ invoice.scheduled_at }}
        </span>
      </div>

      <!-- Kind-specific footer copy -->
      <div class="vim-fine-print">
        <template v-if="kind === 'subscription'">
          Aegis platform subscription. Charged to your default card via Stripe.
        </template>
        <template v-else-if="kind === 'session'">
          Direct client-to-provider payment via Stripe Connect. Aegis holds no funds.
        </template>
        <template v-else>
          Peer payment via Stripe Connect — funds route directly to {{ recipientName }}. Aegis holds no funds.
        </template>
      </div>
    </div>

    <template #footer>
      <!-- View on Stripe (subscription receipts) -->
      <a
        v-if="kind === 'subscription' && invoice?.stripe_invoice_id"
        :href="`https://dashboard.stripe.com/invoices/${invoice.stripe_invoice_id}`"
        target="_blank"
        rel="noopener noreferrer"
        class="btn btn-ghost"
      >
        <AegisIcon name="external-link" :size="12" /> View on Stripe
      </a>

      <a
        v-if="kind !== 'subscription' && invoice?.id"
        :href="kind === 'bp_invoice'
          ? `/provider/support-services/bp-invoices/${invoice.id}/pdf`
          : `/provider/finances/invoices/${invoice.id}/pdf`"
        target="_blank"
        rel="noopener"
        class="btn btn-ghost"
      >
        <AegisIcon name="download" :size="12" /> Download PDF
      </a>

      <!-- Approve & Pay (BP only) -->
      <button
        v-if="canApprove && invoice?.payable && kind === 'bp_invoice'"
        type="button"
        class="btn btn-success"
        @click="handleApprove"
      >
        <AegisIcon name="check" :size="13" /> Approve &amp; Pay
      </button>

      <!-- Pay CS invoice -->
      <button
        v-if="canApprove && invoice?.payable && kind === 'cs_invoice'"
        type="button"
        class="btn btn-primary"
        @click="handleApprove"
      >
        <AegisIcon name="dollar" :size="13" /> Pay Now
      </button>

      <button v-if="!canApprove || !invoice?.payable" type="button" class="btn btn-outline"
        @click="$emit('update:modelValue', false)">
        Close
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  invoice:    { type: Object,  default: null },
  canApprove: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'approve'])

const kind = computed(() => props.invoice?.kind || 'bp_invoice')

const modalTitle = computed(() => {
  if (!props.invoice) return 'Invoice'
  return {
    'bp_invoice':   'Business Partner Invoice — #' + (props.invoice.invoice_number || ''),
    'cs_invoice':   'CS Invoice — #' + (props.invoice.invoice_number || ''),
    'session':      'Clinical Session Payment',
    'subscription': 'Aegis Subscription Invoice',
  }[kind.value] || 'Invoice'
})

const recipientName = computed(() => {
  if (!props.invoice) return ''
  return props.invoice.bp_name
      || props.invoice.cs_name
      || props.invoice.practitioner_name
      || 'Aegis Platform'
})

const recipientKindLabel = computed(() => ({
  'bp_invoice':   'Business Partner',
  'cs_invoice':   'Continuity Steward',
  'session':      'Clinical Practitioner',
  'subscription': 'Aegis Platform',
})[kind.value] || '')

const dateLabel = computed(() => kind.value === 'session' ? 'Session Date' : 'Invoice Date')

const dateValue = computed(() => {
  if (!props.invoice) return ''
  return props.invoice.scheduled_at
      || props.invoice.issued_at
      || props.invoice.date
      || '—'
})

const lineDescription = computed(() => {
  if (!props.invoice) return ''
  return props.invoice.contract_title
      || props.invoice.service_title
      || props.invoice.description
      || 'Services'
})

const statusLabel = computed(() => {
  const s = props.invoice?.status || 'sent'
  // BP invoices use 'sent' to mean awaiting provider approval
  if (kind.value === 'bp_invoice') {
    return ({
      sent:     'Awaiting Approval',
      overdue:  'Overdue',
      disputed: 'Disputed',
      paid:     'Paid',
      void:     'Void',
      draft:    'Draft',
    })[s] ?? s.charAt(0).toUpperCase() + s.slice(1)
  }
  return s.charAt(0).toUpperCase() + s.slice(1)
})

const statusVariant = computed(() => {
  const s = props.invoice?.status
  if (kind.value === 'bp_invoice') {
    return ({ sent: 'gold', overdue: 'red', disputed: 'red', paid: 'green', void: 'neutral', draft: 'neutral' })[s] || 'neutral'
  }
  return ({ paid: 'green', sent: 'blue', overdue: 'red', draft: 'neutral', void: 'neutral', disputed: 'gold', pending: 'gold', scheduled: 'blue', completed: 'green', cancelled: 'neutral' })[s] || 'neutral'
})

function formatCents(c) {
  return '$' + (Number(c || 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function handleApprove() {
  emit('approve', props.invoice)
}
</script>

<style scoped>
.vim-header      { display: flex; justify-content: space-between; margin-bottom: 16px; }
.vim-vendor      { font-family: var(--font-serif); font-size: 17px; font-weight: 700; color: var(--text); }
.vim-vendor-sub  { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.vim-date-block  { text-align: right; }
.vim-date-label  { font-size: 11px; color: var(--text-3); text-transform: uppercase; letter-spacing: 0.4px; }
.vim-date-value  { font-size: 13px; font-weight: 700; color: var(--text); margin-top: 2px; }

.vim-receipt     { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; }
.vim-receipt-row { display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; color: var(--text-2); }
.vim-receipt-row.total { border-top: 1px solid var(--border-dark); font-weight: 700; font-size: 15px; padding-top: 12px; margin-top: 6px; color: var(--text); }

.vim-status-row  { display: flex; align-items: center; gap: 12px; margin-top: 14px; }
.vim-due         { font-size: 12px; color: var(--text-3); }

.vim-fine-print  { font-size: 11px; color: var(--text-3); margin-top: 12px; padding: 10px 12px; background: var(--surface-2); border-radius: var(--radius-sm); border: 1px solid var(--border); line-height: 1.5; }
</style>
