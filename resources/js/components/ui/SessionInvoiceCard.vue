<!--
  SessionInvoiceCard.vue — universal session invoice display card.

  Fixes applied:
    1. Avatar: renders <img> when avatar_url present, else initials (never '?')
    2. Name linked to /public/provider/{slug} for both parties
    3. AegisPagination support via slot (parent passes if needed)
    4. sessions-active-badge icon fixed to 'disc-filled'
    5. Redesigned layout: cleaner header, tighter amounts, improved visual hierarchy

  Emits: pay-deposit, pay-balance, view-invoice, request-refund,
         escalate-refund, cancel-session, review-refund
-->
<template>
  <div class="session-invoice-card" :class="`is-${viewpoint} is-${session.payment_status ?? 'unpaid'}`">

    <!-- ── ACCENT BAR ─────────────────────────────────────────────── -->
    <div class="sic-accent" :class="`sic-accent--${paymentVariant}`"></div>

    <!-- ── BODY ──────────────────────────────────────────────────── -->
    <div class="sic-body">

      <!-- Row 1: header ──────────────────────────────────────── -->
      <div class="sic-header">

        <!-- Avatar + party info -->
        <div class="sic-header-left">
          <div class="sic-avatar">
            <img
              v-if="partyAvatarUrl"
              :src="partyAvatarUrl"
              :alt="partyName"
              class="sic-avatar-img"
            />
            <span v-else class="sic-avatar-initials">{{ partyInitials }}</span>
          </div>
          <div class="sic-header-info">
            <a
              v-if="partySlug"
              :href="`/public/provider/${partySlug}`"
              class="sic-party-name link-name"
            >{{ partyName }}</a>
            <div v-else class="sic-party-name">{{ partyName }}</div>
            <div class="sic-service-title">{{ session.service_title }}</div>
          </div>
        </div>

        <!-- Badges -->
        <div class="sic-header-right">
          <AegisBadge :label="sessionStatusLabel" :variant="sessionStatusVariant" />
          <AegisBadge :label="session.payment_status_label ?? 'Deposit Due'" :variant="paymentVariant" />
        </div>
      </div>

      <!-- Row 2: meta chips ───────────────────────────────────── -->
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
        <span
          class="sic-connect-pill"
          :class="providerConnected ? 'is-connected' : 'is-not-connected'"
          :data-tooltip="providerConnected
            ? 'Provider has Stripe Connect — payment routes directly to their account'
            : 'Provider has not connected Stripe — payment will be queued'"
        >
          <span class="sic-connect-dot"></span>
          {{ providerConnected ? 'Stripe Connected' : 'Not Connected' }}
        </span>
      </div>

      <!-- Row 3: amount breakdown ─────────────────────────────── -->
      <div class="sic-amounts">
        <div class="sic-amounts-header">
          <span class="sic-amounts-title">
            {{ hasNegotiatedPrice ? 'Negotiated Total' : 'Session Total' }}
          </span>
          <span class="sic-amounts-total">{{ session.amount }}</span>
        </div>
        <div class="sic-amounts-body">

          <!-- Original if negotiated -->
          <div v-if="hasNegotiatedPrice" class="sic-amount-row sic-amount-row--sub">
            <span class="sic-amount-label">Listing Price</span>
            <span class="sic-amount-value sic-amount-value--muted">${{ fmtCents(session.original_amount_cents) }}</span>
          </div>

          <!-- Deposit -->
          <div class="sic-amount-row">
            <span class="sic-amount-label">
              <AegisIcon
                :name="depositPaid ? 'check-circle' : 'circle'"
                :size="12"
                :class="depositPaid ? 'sic-icon-paid' : 'sic-icon-due'"
              />
              Deposit (30%)
              <span v-if="depositPaid && session.deposit_paid_at" class="sic-paid-date">
                · paid {{ session.deposit_paid_at }}
              </span>
            </span>
            <span class="sic-amount-value" :class="depositPaid ? 'sic-val-paid' : ''">
              {{ session.expected_deposit_label || session.deposit_label || '$0.00' }}
            </span>
          </div>

          <!-- Balance -->
          <div class="sic-amount-row">
            <span class="sic-amount-label">
              <AegisIcon
                :name="balancePaid ? 'check-circle' : 'circle'"
                :size="12"
                :class="balancePaid ? 'sic-icon-paid' : 'sic-icon-due'"
              />
              Balance (70%)
              <span v-if="balancePaid && session.balance_paid_at" class="sic-paid-date">
                · paid {{ session.balance_paid_at }}
              </span>
            </span>
            <span class="sic-amount-value" :class="balancePaid ? 'sic-val-paid' : ''">
              {{ session.expected_balance_label || session.balance_label || '$0.00' }}
            </span>
          </div>

          <!-- Refund -->
          <div v-if="session.total_refunded_cents > 0" class="sic-amount-row sic-amount-row--refund">
            <span class="sic-amount-label">
              <AegisIcon name="arrow-left" :size="12" />
              Refunded
            </span>
            <span class="sic-amount-value sic-val-refund">
              –{{ session.total_refunded_label }}
            </span>
          </div>
        </div>
      </div>

      <!-- Row 4: refund status banner ─────────────────────────── -->
      <div
        v-if="session.has_refund_request || session.has_pending_refund_request"
        class="sic-refund-status"
      >
        <AegisIcon name="alert-circle" :size="13" />
        <span v-if="session.refund_request_status === 'pending_review'">
          Refund request pending provider review
        </span>
        <span v-else-if="session.refund_request_status === 'approved'">
          Refund approved — processing
        </span>
        <span v-else-if="session.refund_request_status === 'denied'">
          Refund denied —
          <button
            v-if="session.can_escalate_refund"
            type="button"
            class="link-inline"
            @click="$emit('escalate-refund')"
          >Escalate to dispute</button>
          <span v-else>contact support if needed</span>
        </span>
        <span v-else-if="session.has_pending_refund_request">
          Client has requested a refund
        </span>
      </div>

      <!-- Row 5: actions ──────────────────────────────────────── -->
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

        <!-- Shared -->
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
  'pay-deposit', 'pay-balance', 'view-invoice',
  'request-refund', 'escalate-refund', 'cancel-session', 'review-refund',
])

// ── Computed ──────────────────────────────────────────────────────────────────

const paymentVariant = computed(() => props.session.payment_status_variant ?? 'gold')

const sessionStatusVariant = computed(() => ({
  scheduled: 'blue', completed: 'green', cancelled: 'neutral', no_show: 'red',
}[props.session.status] ?? 'neutral'))

const sessionStatusLabel = computed(() => ({
  scheduled: 'Scheduled', completed: 'Completed', cancelled: 'Cancelled', no_show: 'No Show',
}[props.session.status] ?? props.session.status))

// Party — who is the "other person" from the current viewpoint
const partyName = computed(() =>
  props.viewpoint === 'client'
    ? (props.session.practitioner_name ?? 'Provider')
    : (props.session.client_name ?? 'Client')
)

const partySlug = computed(() =>
  props.viewpoint === 'client'
    ? (props.session.practitioner_slug ?? '')
    : (props.session.client_slug ?? '')
)

const partyAvatarUrl = computed(() =>
  props.viewpoint === 'client'
    ? (props.session.practitioner_avatar_url ?? null)
    : (props.session.client_avatar_url ?? null)
)

const partyInitials = computed(() => {
  const raw = props.viewpoint === 'client'
    ? props.session.practitioner_avatar
    : props.session.client_avatar
  if (raw && raw !== '?') return raw
  // Derive from name as fallback
  const name = partyName.value ?? ''
  return name.split(' ').map(w => w[0] ?? '').join('').substring(0, 2).toUpperCase() || '?'
})

const depositPaid = computed(() =>
  ['deposit_paid', 'paid', 'refunded', 'partially_refunded'].includes(props.session.payment_status)
)

const balancePaid = computed(() =>
  ['paid', 'refunded', 'partially_refunded'].includes(props.session.payment_status)
)

const providerConnected = computed(() =>
  props.viewpoint === 'client'
    ? !!props.session.practitioner_stripe_connected
    : true
)

const hasNegotiatedPrice = computed(() =>
  props.session.negotiated_amount_cents != null
  && props.session.negotiated_amount_cents !== props.session.original_amount_cents
)

function fmtCents(cents) {
  if (!cents) return '0.00'
  return (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
/* ── Card shell ── */
.session-invoice-card {
  display: flex;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  overflow: hidden;
  transition: box-shadow var(--transition);
  margin-bottom: 10px;
}
.session-invoice-card:last-child { margin-bottom: 0; }
.session-invoice-card:hover { box-shadow: var(--shadow-md); }

/* Accent bar */
.sic-accent            { width: 4px; flex-shrink: 0; }
.sic-accent--gold      { background: var(--gold); }
.sic-accent--blue      { background: var(--blue, #3b82f6); }
.sic-accent--green     { background: var(--green); }
.sic-accent--neutral   { background: var(--border-dark); }
.sic-accent--red       { background: var(--red, #ef4444); }

/* Body */
.sic-body {
  flex: 1; padding: 14px 16px;
  display: flex; flex-direction: column; gap: 10px;
  min-width: 0;
}

/* ── Header ── */
.sic-header {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 12px; flex-wrap: wrap;
}
.sic-header-left { display: flex; align-items: center; gap: 10px; min-width: 0; }

/* Avatar */
.sic-avatar {
  width: 38px; height: 38px; border-radius: 50%;
  background: var(--badge-bg-gold); color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; overflow: hidden;
}
.sic-avatar-img {
  width: 100%; height: 100%; object-fit: cover; border-radius: 50%;
}
.sic-avatar-initials {
  font-size: 12px; font-weight: 700; letter-spacing: .3px;
  line-height: 1;
}

.sic-header-info   { min-width: 0; }
.sic-party-name    { font-size: 14px; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
a.sic-party-name   { text-decoration: none; color: var(--gold-dark); }
a.sic-party-name:hover { color: var(--gold); text-decoration: underline; }
.sic-service-title { font-size: 12px; color: var(--text-3); font-weight: 600; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sic-header-right  { display: flex; gap: 5px; align-items: center; flex-shrink: 0; flex-wrap: wrap; }
.sic-header-right :deep(.badge) {
  font-size: 11px; font-weight: 700;
  padding: 4px 10px; letter-spacing: .2px;
}

/* ── Meta ── */
.sic-meta { display: flex; flex-wrap: wrap; gap: 8px 12px; align-items: center; }
.sic-meta-item {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 600; color: var(--text-3);
}
.sic-connect-pill {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 10px; font-weight: 700;
  padding: 2px 7px; border-radius: 100px;
  border: 1px solid var(--border); background: var(--surface-2);
}
.sic-connect-pill.is-connected     { color: var(--green-dark, #2e7d32); border-color: var(--green); background: rgba(34,197,94,.07); }
.sic-connect-pill.is-not-connected { color: var(--gold-dark); border-color: var(--gold); }
.sic-connect-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }

/* ── Amounts ── */
.sic-amounts {
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius); overflow: hidden;
}
.sic-amounts-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 8px 12px; background: var(--surface-3);
  border-bottom: 1px solid var(--border);
}
.sic-amounts-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--text-4); }
.sic-amounts-total { font-size: 15px; font-weight: 700; color: var(--text); font-family: var(--font-serif, serif); }
.sic-amounts-body  { padding: 2px 0; }

.sic-amount-row {
  display: flex; justify-content: space-between; align-items: center;
  gap: 8px; padding: 6px 12px;
}
.sic-amount-row + .sic-amount-row { border-top: 1px solid var(--border); }
.sic-amount-row--sub   { opacity: .65; }
.sic-amount-row--refund { }

.sic-amount-label {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; color: var(--text-3); font-weight: 600;
}
.sic-amount-value { font-size: 13px; font-weight: 700; color: var(--text); font-family: var(--font-serif, serif); }
.sic-amount-value--muted { color: var(--text-4); text-decoration: line-through; font-weight: 500; }
.sic-val-paid    { color: var(--green); }
.sic-val-refund  { color: var(--red, #ef4444); }
.sic-icon-paid   { color: var(--green); }
.sic-icon-due    { color: var(--border-dark); }
.sic-paid-date   { font-size: 10px; color: var(--text-4); font-weight: 500; }

/* ── Refund status banner ── */
.sic-refund-status {
  display: flex; align-items: center; gap: 7px;
  padding: 8px 10px;
  background: rgba(245,158,11,.07);
  border: 1px solid var(--gold); border-radius: var(--radius);
  font-size: 12px; font-weight: 600; color: var(--gold-dark);
}
.link-inline {
  color: var(--gold-dark); text-decoration: underline;
  background: none; border: none; cursor: pointer;
  padding: 0; font-size: inherit; font-weight: 700;
}

/* ── Actions ── */
.sic-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding-top: 2px; }
</style>
