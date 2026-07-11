<!--
  SessionInvoiceCard.vue — universal session invoice display card.

  Used in:
    - Services.vue → "My Requests" tab (client viewpoint)
    - Services.vue → "Bookings & Sessions" tab (provider viewpoint)
    - Finances.vue → "Clinical Sessions" tab (both sections)

  Props:
    session   — shaped session object from shapeClientSession() or shapeSession()
    viewpoint — 'client' | 'provider'
    showActions — show action buttons (default true)

  Emits:
    pay-deposit   — client clicks Pay Deposit
    pay-balance   — client clicks Confirm & Pay Balance
    view-invoice  — open invoice modal
    request-refund — client opens refund request
    cancel-session — open cancel modal
    approve-refund  — provider approves incoming refund
    deny-refund    — provider opens deny modal

  All emit calls are handled by the parent — no Inertia posts here.
  AegisBadge, AegisIcon are globally registered — no import needed.
-->
<template>
  <div class="session-invoice-card" :class="`is-${viewpoint}`">

    <!-- ── LEFT ACCENT BAR (payment status colour) ──────────────────── -->
    <div class="sic-accent" :class="`sic-accent--${paymentVariant}`"></div>

    <!-- ── MAIN BODY ─────────────────────────────────────────────────── -->
    <div class="sic-body">

      <!-- Row 1: header ─────────────────────────────────────────── -->
      <div class="sic-header">
        <div class="sic-header-left">
          <div class="sic-avatar">{{ session.practitioner_avatar || session.client_avatar || '?' }}</div>
          <div class="sic-header-info">
            <div class="sic-party-name">
              {{ viewpoint === 'client' ? session.practitioner_name : session.client_name }}
            </div>
            <div class="sic-service-title">{{ session.service_title }}</div>
          </div>
        </div>
        <div class="sic-header-right">
          <AegisBadge :label="sessionStatusLabel" :variant="sessionStatusVariant" />
          <AegisBadge :label="session.payment_status_label" :variant="paymentVariant" />
        </div>
      </div>

      <!-- Row 2: meta ─────────────────────────────────────────────── -->
      <div class="sic-meta">
        <span class="sic-meta-item">
          <AegisIcon name="calendar" :size="12" />
          {{ session.datetime_label || 'Date TBD' }}
        </span>
        <span v-if="session.duration_label && session.duration_label !== '—'" class="sic-meta-item">
          <AegisIcon name="clock" :size="12" />
          {{ session.duration_label }}
        </span>
        <span class="sic-meta-item">
          <AegisIcon name="hash" :size="12" />
          {{ session.invoice_number }}
        </span>
        <!-- Stripe Connect status pill -->
        <span
          class="sic-connect-pill"
          :class="providerConnected ? 'is-connected' : 'is-not-connected'"
          :data-tooltip="providerConnected ? 'Provider has Stripe Connect — payment routes directly to their account' : 'Provider has not connected Stripe — payment will be queued'"
        >
          <span class="sic-connect-dot"></span>
          {{ providerConnected ? 'Stripe Connected' : 'Not Connected' }}
        </span>
      </div>

      <!-- Row 3: amount breakdown ──────────────────────────────────── -->
      <div class="sic-amounts">

        <!-- Agreed total -->
        <div class="sic-amount-row">
          <span class="sic-amount-label">
            {{ hasNegotiatedPrice ? 'Negotiated Price' : 'Service Rate' }}
          </span>
          <span class="sic-amount-value">{{ session.amount }}</span>
        </div>

        <!-- Show original if negotiated -->
        <div v-if="hasNegotiatedPrice" class="sic-amount-row sic-amount-row--sub">
          <span class="sic-amount-label">Original Listing Price</span>
          <span class="sic-amount-value sic-amount-value--muted">${{ fmtCents(session.original_amount_cents) }}</span>
        </div>

        <!-- Deposit line -->
        <div class="sic-amount-row sic-amount-row--charge">
          <span class="sic-amount-label">
            <AegisIcon name="check-circle" :size="12" :class="depositPaid ? 'sic-icon-paid' : 'sic-icon-due'" />
            Deposit (30%)
            <span v-if="depositPaid" class="sic-paid-date">paid {{ session.deposit_paid_at }}</span>
          </span>
          <span class="sic-amount-value" :class="depositPaid ? 'sic-amount-paid' : ''">
            {{ session.expected_deposit_label || session.deposit_label || '$0.00' }}
          </span>
        </div>

        <!-- Balance line -->
        <div class="sic-amount-row sic-amount-row--charge">
          <span class="sic-amount-label">
            <AegisIcon name="check-circle" :size="12" :class="balancePaid ? 'sic-icon-paid' : 'sic-icon-due'" />
            Balance (70%)
            <span v-if="balancePaid" class="sic-paid-date">paid {{ session.balance_paid_at }}</span>
          </span>
          <span class="sic-amount-value" :class="balancePaid ? 'sic-amount-paid' : ''">
            {{ session.expected_balance_label || session.balance_label || '$0.00' }}
          </span>
        </div>

        <!-- Refund line (only shown if any refund issued) -->
        <div v-if="session.total_refunded_cents > 0" class="sic-amount-row sic-amount-row--refund">
          <span class="sic-amount-label">
            <AegisIcon name="arrow-left" :size="12" />
            Refunded
          </span>
          <span class="sic-amount-value sic-amount-refund">
            –{{ session.total_refunded_label }}
          </span>
        </div>

      </div>

      <!-- Row 4: refund request status (if any) ───────────────────── -->
      <div v-if="session.has_refund_request || session.has_pending_refund_request" class="sic-refund-status">
        <AegisIcon name="alert-circle" :size="13" />
        <span v-if="session.refund_request_status === 'pending_review'">Refund request pending provider review</span>
        <span v-else-if="session.refund_request_status === 'approved'">Refund approved — processing</span>
        <span v-else-if="session.refund_request_status === 'denied'">
          Refund denied —
          <button v-if="session.can_escalate_refund" type="button" class="link-inline" @click="$emit('escalate-refund')">Escalate to dispute</button>
          <span v-else>contact support if needed</span>
        </span>
        <span v-else-if="session.has_pending_refund_request">Client has requested a refund</span>
      </div>

      <!-- Row 5: action buttons ────────────────────────────────────── -->
      <div v-if="showActions" class="sic-actions">

        <!-- CLIENT actions -->
        <template v-if="viewpoint === 'client'">
          <button
            v-if="session.can_pay_deposit"
            type="button"
            class="btn btn-primary"
            @click="$emit('pay-deposit')"
          >
            <AegisIcon name="credit-card" :size="13" />
            Pay Deposit {{ session.expected_deposit_label }}
          </button>

          <button
            v-if="session.can_pay_balance"
            type="button"
            class="btn btn-primary"
            @click="$emit('pay-balance')"
          >
            <AegisIcon name="check" :size="13" />
            Confirm &amp; Pay Balance {{ session.expected_balance_label }}
          </button>

          <button
            v-if="session.can_request_refund && !session.has_refund_request"
            type="button"
            class="btn btn-outline"
            @click="$emit('request-refund')"
          >
            <AegisIcon name="arrow-left" :size="13" />
            Request Refund
          </button>
        </template>

        <!-- PROVIDER actions -->
        <template v-if="viewpoint === 'provider'">
          <button
            v-if="session.has_pending_refund_request"
            type="button"
            class="btn btn-outline"
            @click="$emit('review-refund')"
          >
            <AegisIcon name="alert-circle" :size="13" />
            Review Refund Request
          </button>
        </template>

        <!-- Shared actions (both viewpoints) -->
        <button
          type="button"
          class="btn-icon"
          data-tooltip="View Invoice"
          @click="$emit('view-invoice')"
        >
          <AegisIcon name="file-text" :size="14" />
        </button>

        <button
          v-if="session.status === 'scheduled' && viewpoint === 'provider'"
          type="button"
          class="btn-icon"
          data-tooltip="Cancel Session"
          @click="$emit('cancel-session')"
        >
          <AegisIcon name="x" :size="14" />
        </button>

      </div>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  session:     { type: Object,  required: true },
  viewpoint:   { type: String,  default: 'client' }, // 'client' | 'provider'
  showActions: { type: Boolean, default: true },
})

defineEmits([
  'pay-deposit',
  'pay-balance',
  'view-invoice',
  'request-refund',
  'escalate-refund',
  'cancel-session',
  'review-refund',
])

// ── Computed helpers ──────────────────────────────────────────────────────────

const paymentVariant = computed(() => props.session.payment_status_variant ?? 'gold')

const sessionStatusVariant = computed(() => {
  return {
    scheduled:  'blue',
    completed:  'green',
    cancelled:  'neutral',
    no_show:    'red',
  }[props.session.status] ?? 'neutral'
})

const sessionStatusLabel = computed(() => {
  return {
    scheduled: 'Scheduled',
    completed: 'Completed',
    cancelled: 'Cancelled',
    no_show:   'No Show',
  }[props.session.status] ?? props.session.status
})

const depositPaid = computed(() => {
  return ['deposit_paid', 'paid', 'refunded', 'partially_refunded'].includes(props.session.payment_status)
})

const balancePaid = computed(() => {
  return ['paid', 'refunded', 'partially_refunded'].includes(props.session.payment_status)
})

const providerConnected = computed(() => {
  return props.viewpoint === 'client'
    ? props.session.practitioner_stripe_connected
    : true // Provider always sees own connect status via parent data
})

const hasNegotiatedPrice = computed(() => {
  return props.session.negotiated_amount_cents != null
    && props.session.negotiated_amount_cents !== props.session.original_amount_cents
})

function fmtCents(cents) {
  if (!cents) return '0.00'
  return (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.session-invoice-card {
  display: flex;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  overflow: hidden;
  transition: box-shadow var(--transition);
  margin-bottom: 12px;
}
.session-invoice-card:hover { box-shadow: var(--shadow-md); }

/* Accent bar */
.sic-accent { width: 4px; flex-shrink: 0; }
.sic-accent--gold    { background: var(--gold); }
.sic-accent--blue    { background: var(--blue, #3b82f6); }
.sic-accent--green   { background: var(--green); }
.sic-accent--neutral { background: var(--border-dark); }

.sic-body {
  flex: 1;
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
}

/* Header row */
.sic-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}
.sic-header-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
.sic-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--badge-bg-gold); color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.sic-header-info { min-width: 0; }
.sic-party-name  { font-size: 14px; font-weight: 700; color: var(--text); }
.sic-service-title { font-size: 12px; color: var(--text-3); font-weight: 600; margin-top: 1px; }
.sic-header-right { display: flex; gap: 6px; align-items: center; flex-shrink: 0; flex-wrap: wrap; }

/* Meta row */
.sic-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}
.sic-meta-item {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 12px; color: var(--text-3); font-weight: 600;
}
.sic-connect-pill {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 700;
  padding: 2px 8px; border-radius: 100px;
  border: 1px solid var(--border);
  background: var(--surface-2);
}
.sic-connect-pill.is-connected     { color: var(--green-dark, #2e7d32); border-color: var(--green); background: rgba(34,197,94,.07); }
.sic-connect-pill.is-not-connected { color: var(--gold-dark); }
.sic-connect-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: currentColor; flex-shrink: 0;
}

/* Amount breakdown */
.sic-amounts {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.sic-amount-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  padding: 3px 0;
}
.sic-amount-row + .sic-amount-row { border-top: 1px solid var(--border); }
.sic-amount-row--sub   { opacity: .7; }
.sic-amount-row--refund { color: var(--red); }
.sic-amount-label {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; color: var(--text-3); font-weight: 600;
}
.sic-amount-value {
  font-size: 13px; font-weight: 700; color: var(--text);
  font-family: var(--font-serif, serif);
}
.sic-amount-value--muted { color: var(--text-4); text-decoration: line-through; font-weight: 500; }
.sic-amount-paid  { color: var(--green); }
.sic-amount-refund { color: var(--red); font-size: 13px; }
.sic-icon-paid { color: var(--green); }
.sic-icon-due  { color: var(--border-dark); }
.sic-paid-date { font-size: 10px; color: var(--text-4); font-weight: 500; margin-left: 4px; }

/* Refund status banner */
.sic-refund-status {
  display: flex; align-items: center; gap: 7px;
  padding: 8px 12px;
  background: rgba(245,158,11,.07);
  border: 1px solid var(--gold);
  border-radius: var(--radius);
  font-size: 12px; font-weight: 600; color: var(--gold-dark);
}
.link-inline {
  color: var(--gold-dark); text-decoration: underline;
  background: none; border: none; cursor: pointer; padding: 0;
  font-size: inherit; font-weight: 700;
}

/* Actions row */
.sic-actions {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
</style>
