<!--
  SessionInvoiceCard.vue — centralized session table-row + detail modal.

  Used in:
    - Finances.vue  (provider & client session lists)
    - Services.vue  (Bookings & Sessions tab — provider view)

  Props:
    session     — shaped session object (shapeSession / shapeClientSession)
    viewpoint   — 'client' | 'provider'
    showNotes   — show "Notes" button in modal footer (Services.vue only)
    showInvoice — show "Invoice" button in modal footer (Services.vue only)
    showCancel  — show "Cancel Session" button in modal footer (Services.vue only)

  Emits:
    pay-deposit      — client: open PayDepositModal
    pay-balance      — client: open PayBalanceModal
    request-refund   — client: open RequestRefundModal
    escalate-refund  — client: escalate denied refund
    review-refund    — provider: open ReviewRefundRequestModal
    open-notes       — provider (Services only): open session notes modal
    open-invoice     — provider (Services only): open provider invoice modal
    cancel-session   — provider (Services only): open cancel modal
-->
<template>
  <!-- ── TABLE ROW ─────────────────────────────────────────────────── -->
  <tr
    class="sic-row"
    :class="[`sic-row--${session.status}`, `sic-row--pay-${session.payment_status ?? 'unpaid'}`]"
  >
    <!-- Col 1: Avatar + name + service + date -->
    <td class="sic-td sic-td--party" @click="open = true">
      <div class="sic-party">
        <div class="sic-avatar">
          <img v-if="partyAvatarUrl" :src="partyAvatarUrl" :alt="partyName" class="sic-avatar-img" />
          <span v-else class="sic-avatar-initials">{{ partyInitials }}</span>
        </div>
        <div class="sic-party-info">
          <a
            v-if="partySlug"
            :href="`/public/provider/${partySlug}`"
            class="sic-party-name"
            @click.stop
          >{{ partyName }}</a>
          <span v-else class="sic-party-name">{{ partyName }}</span>
          <span class="sic-service-name">{{ session.service_title }}</span>
          <span class="sic-date-sub">{{ session.datetime_label || '—' }}</span>
        </div>
      </div>
    </td>

    <!-- Col 2: Status badges -->
    <td class="sic-td sic-td--status" @click="open = true">
      <div class="sic-badges">
        <AegisBadge :label="sessionStatusLabel" :variant="sessionStatusVariant" />
        <AegisBadge :label="session.payment_status_label ?? 'Deposit Due'" :variant="paymentVariant" />
        <AegisBadge v-if="session.has_pending_refund_request" label="Refund" variant="red" />
      </div>
    </td>

    <!-- Col 3: Chevron -->
    <td class="sic-td sic-td--actions" @click="open = true">
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

  <!-- ── DETAIL MODAL ──────────────────────────────────────────────── -->
  <AegisModal v-model="open" title="Session Details" size="md">
    <template #default>

      <!-- Party header -->
      <div class="sic-modal-party">
        <div class="sic-avatar sic-avatar--lg">
          <img v-if="partyAvatarUrl" :src="partyAvatarUrl" :alt="partyName" class="sic-avatar-img" />
          <span v-else class="sic-avatar-initials">{{ partyInitials }}</span>
        </div>
        <div class="sic-modal-party-info">
          <a
            v-if="partySlug"
            :href="`/public/provider/${partySlug}`"
            class="sic-modal-party-name"
            target="_blank"
          >{{ partyName }}</a>
          <span v-else class="sic-modal-party-name">{{ partyName }}</span>
          <span v-if="partyCredentials" class="sic-modal-cred">{{ partyCredentials }}</span>
          <span class="sic-modal-service">{{ session.service_title }}</span>
        </div>
        <div class="sic-modal-badges">
          <AegisBadge :label="sessionStatusLabel" :variant="sessionStatusVariant" />
          <AegisBadge :label="session.payment_status_label ?? 'Deposit Due'" :variant="paymentVariant" />
        </div>
      </div>

      <!-- Meta row -->
      <div class="sic-modal-meta">
        <span class="sic-modal-meta-item">
          <AegisIcon name="calendar" :size="12" /> {{ session.datetime_label || 'Date TBD' }}
        </span>
        <span v-if="session.duration_label && session.duration_label !== '—'" class="sic-modal-meta-item">
          <AegisIcon name="clock" :size="12" /> {{ session.duration_label }}
        </span>
        <span class="sic-modal-meta-item">
          <AegisIcon name="hash" :size="12" /> {{ session.invoice_number }}
        </span>
        <span class="sic-connect-pill" :class="providerConnected ? 'is-connected' : 'is-not-connected'">
          <span class="sic-connect-dot"></span>
          {{ providerConnected ? 'Stripe Connected' : 'Not Connected' }}
        </span>
      </div>

      <!-- Amount breakdown -->
      <div class="sic-modal-amounts">
        <div class="sic-modal-amounts-head">
          <span>{{ hasNegotiatedPrice ? 'Negotiated Total' : 'Session Total' }}</span>
          <span class="sic-modal-total">{{ session.amount }}</span>
        </div>

        <div v-if="hasNegotiatedPrice" class="sic-modal-amount-row sic-modal-amount-row--sub">
          <span class="sic-modal-amount-label">Listing Price</span>
          <span class="sic-modal-amount-val sic-modal-amount-val--muted">${{ fmtCents(session.original_amount_cents) }}</span>
        </div>

        <div class="sic-modal-amount-row">
          <span class="sic-modal-amount-label">
            <AegisIcon :name="depositPaid ? 'check-circle' : 'circle'" :size="12" :class="depositPaid ? 'icon-paid' : 'icon-due'" />
            Deposit (30%)
            <span v-if="depositPaid && session.deposit_paid_at" class="sic-modal-paid-date">· paid {{ session.deposit_paid_at }}</span>
          </span>
          <span class="sic-modal-amount-val" :class="depositPaid ? 'sic-modal-amount-val--paid' : ''">
            {{ session.expected_deposit_label || '$0.00' }}
          </span>
        </div>

        <div class="sic-modal-amount-row">
          <span class="sic-modal-amount-label">
            <AegisIcon :name="balancePaid ? 'check-circle' : 'circle'" :size="12" :class="balancePaid ? 'icon-paid' : 'icon-due'" />
            Balance (70%)
            <span v-if="balancePaid && session.balance_paid_at" class="sic-modal-paid-date">· paid {{ session.balance_paid_at }}</span>
          </span>
          <span class="sic-modal-amount-val" :class="balancePaid ? 'sic-modal-amount-val--paid' : ''">
            {{ session.expected_balance_label || '$0.00' }}
          </span>
        </div>

        <div v-if="(session.total_refunded_cents ?? 0) > 0" class="sic-modal-amount-row">
          <span class="sic-modal-amount-label">
            <AegisIcon name="arrow-left" :size="12" /> Refunded
          </span>
          <span class="sic-modal-amount-val sic-modal-amount-val--refund">–{{ session.total_refunded_label }}</span>
        </div>
      </div>

      <!-- Session notes (provider / Services.vue only) -->
      <div v-if="showNotes && (session.summary || session.action_items)" class="sic-modal-notes">
        <div class="sic-modal-notes-label"><AegisIcon name="file-text" :size="12" /> Session Notes</div>
        <div v-if="session.summary" class="sic-modal-notes-body">{{ session.summary }}</div>
        <div v-if="session.action_items" class="sic-modal-notes-actions">
          <strong>Action items:</strong> {{ session.action_items }}
        </div>
      </div>

      <!-- Refund alert -->
      <div v-if="session.has_refund_request || session.has_pending_refund_request" class="sic-modal-refund-alert">
        <AegisIcon name="alert-circle" :size="13" />
        <span v-if="session.refund_request_status === 'pending_review'">Refund request pending provider review.</span>
        <span v-else-if="session.refund_request_status === 'approved'">Refund approved — processing.</span>
        <span v-else-if="session.refund_request_status === 'denied'">
          Refund denied.
          <button v-if="session.can_escalate_refund" type="button" class="link-inline" @click="$emit('escalate-refund'); open = false">Escalate to dispute</button>
        </span>
        <span v-else-if="session.has_pending_refund_request">Client has requested a refund.</span>
      </div>

    </template>

    <!-- Footer actions -->
    <template #footer>
      <button type="button" class="btn btn-outline" @click="open = false">Close</button>

      <!-- CLIENT actions -->
      <template v-if="viewpoint === 'client'">
        <button
          v-if="session.can_pay_deposit"
          type="button"
          class="btn btn-primary"
          @click="$emit('pay-deposit'); open = false"
        >
          <AegisIcon name="credit-card" :size="13" /> Pay Deposit {{ session.expected_deposit_label }}
        </button>
        <button
          v-if="session.can_pay_balance"
          type="button"
          class="btn btn-primary"
          @click="$emit('pay-balance'); open = false"
        >
          <AegisIcon name="check" :size="13" /> Pay Balance {{ session.expected_balance_label }}
        </button>
        <button
          v-if="session.can_request_refund && !session.has_refund_request"
          type="button"
          class="btn btn-outline"
          @click="$emit('request-refund'); open = false"
        >
          <AegisIcon name="arrow-left" :size="13" /> Request Refund
        </button>
      </template>

      <!-- PROVIDER actions -->
      <template v-if="viewpoint === 'provider'">
        <button
          v-if="session.has_pending_refund_request"
          type="button"
          class="btn btn-outline"
          @click="$emit('review-refund'); open = false"
        >
          <AegisIcon name="alert-circle" :size="13" /> Review Refund
        </button>
        <!-- Services.vue-only actions -->
        <button
          v-if="showNotes"
          type="button"
          class="btn btn-outline"
          @click="$emit('open-notes'); open = false"
        >
          <AegisIcon name="file-text" :size="13" /> Notes
        </button>
        <button
          v-if="showInvoice"
          type="button"
          class="btn btn-outline"
          @click="$emit('open-invoice'); open = false"
        >
          <AegisIcon name="dollar-sign" :size="13" /> Invoice
        </button>
        <button
          v-if="showCancel && session.status === 'scheduled'"
          type="button"
          class="btn btn-danger"
          @click="$emit('cancel-session'); open = false"
        >
          <AegisIcon name="x" :size="13" /> Cancel Session
        </button>
      </template>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  session:     { type: Object,  required: true },
  viewpoint:   { type: String,  default: 'client' }, // 'client' | 'provider'
  showNotes:   { type: Boolean, default: false },
  showInvoice: { type: Boolean, default: false },
  showCancel:  { type: Boolean, default: false },
})

defineEmits([
  'pay-deposit', 'pay-balance', 'request-refund', 'escalate-refund',
  'review-refund', 'open-notes', 'open-invoice', 'cancel-session',
])

const open = ref(false)

// ── Computed ──────────────────────────────────────────────────────────────────
const paymentVariant = computed(() => props.session.payment_status_variant ?? 'gold')

const sessionStatusVariant = computed(() => ({
  scheduled: 'blue', completed: 'green', cancelled: 'neutral', no_show: 'red',
}[props.session.status] ?? 'neutral'))

const sessionStatusLabel = computed(() => ({
  scheduled: 'Scheduled', completed: 'Completed', cancelled: 'Cancelled', no_show: 'No Show',
}[props.session.status] ?? props.session.status))

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
  const name = partyName.value ?? ''
  return name.split(' ').map(w => w[0] ?? '').join('').substring(0, 2).toUpperCase() || '??'
})

const partyCredentials = computed(() =>
  props.viewpoint === 'client'
    ? (props.session.practitioner_detail ?? props.session.practitioner_credentials ?? '')
    : (props.session.client_credentials ?? '')
)

const depositPaid = computed(() =>
  ['deposit_paid', 'paid', 'refunded', 'partially_refunded'].includes(props.session.payment_status)
)
const balancePaid = computed(() =>
  ['paid', 'refunded', 'partially_refunded'].includes(props.session.payment_status)
)
const providerConnected = computed(() =>
  props.viewpoint === 'client' ? !!props.session.practitioner_stripe_connected : true
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
/* ── TABLE ROW ── */
.sic-row { cursor: pointer; transition: background var(--transition); background: var(--surface, #fff); }
.sic-row:hover { background: var(--surface-2); }
.sic-row--scheduled { border-left: 3px solid var(--blue, #3b82f6); }
.sic-row--completed { border-left: 3px solid var(--green); }
.sic-row--cancelled { border-left: 3px solid var(--border-dark); opacity: .7; }
.sic-row--no_show   { border-left: 3px solid var(--red, #ef4444); }

.sic-td { padding: 10px 12px; vertical-align: middle; font-size: 13px; border-bottom: 1px solid var(--border); }
.sic-td--party   { width: 58%; }
.sic-td--status  { width: 34%; }
.sic-td--actions { width: 8%; text-align: right; }

.sic-party { display: flex; align-items: center; gap: 9px; }
.sic-avatar {
  width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
  background: var(--badge-bg-gold); color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700; overflow: hidden;
}
.sic-avatar--lg { width: 40px; height: 40px; font-size: 13px; }
.sic-avatar-img { width: 100%; height: 100%; object-fit: cover; }
.sic-avatar-initials { line-height: 1; letter-spacing: .3px; }
.sic-party-info  { min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.sic-party-name  { font-size: 13px; font-weight: 700; color: var(--gold-dark); text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
a.sic-party-name:hover { text-decoration: underline; color: var(--gold); }
.sic-service-name { font-size: 11px; color: var(--text-4); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sic-date-sub    { font-size: 11px; color: var(--text-4); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sic-badges      { display: flex; flex-wrap: wrap; gap: 4px; }

/* ── MODAL ── */
.sic-modal-party {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px; margin-bottom: 14px;
  background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius);
}
.sic-modal-party-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.sic-modal-party-name { font-size: 15px; font-weight: 700; color: var(--gold-dark); text-decoration: none; }
a.sic-modal-party-name:hover { text-decoration: underline; }
.sic-modal-cred    { font-size: 11px; font-weight: 600; color: var(--text-4); }
.sic-modal-service { font-size: 12px; color: var(--text-3); font-weight: 600; }
.sic-modal-badges  { display: flex; gap: 5px; flex-wrap: wrap; }

.sic-modal-meta { display: flex; flex-wrap: wrap; gap: 8px 14px; align-items: center; margin-bottom: 14px; }
.sic-modal-meta-item { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: var(--text-3); }
.sic-connect-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 100px; border: 1px solid var(--border); background: var(--surface-2); }
.sic-connect-pill.is-connected { color: var(--green-dark, #2e7d32); border-color: var(--green); background: rgba(34,197,94,.07); }
.sic-connect-pill.is-not-connected { color: var(--gold-dark); border-color: var(--gold); }
.sic-connect-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }

.sic-modal-amounts { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 12px; }
.sic-modal-amounts-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 8px 14px; background: var(--surface-3); border-bottom: 1px solid var(--border);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--text-4);
}
.sic-modal-total { font-size: 16px; font-weight: 700; color: var(--text); font-family: var(--font-serif, serif); text-transform: none; letter-spacing: 0; }
.sic-modal-amount-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 14px; border-top: 1px solid var(--border); font-size: 13px; }
.sic-modal-amount-row--sub { opacity: .65; }
.sic-modal-amount-label { display: flex; align-items: center; gap: 5px; font-weight: 600; color: var(--text-3); font-size: 12px; }
.sic-modal-paid-date { font-size: 10px; color: var(--text-4); font-weight: 500; }
.sic-modal-amount-val { font-weight: 700; font-family: var(--font-serif, serif); color: var(--text); }
.sic-modal-amount-val--muted  { color: var(--text-4); text-decoration: line-through; font-weight: 500; }
.sic-modal-amount-val--paid   { color: var(--green); }
.sic-modal-amount-val--refund { color: var(--red, #ef4444); }
.icon-paid { color: var(--green); }
.icon-due  { color: var(--border-dark); }

.sic-modal-notes { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 10px 14px; margin-bottom: 12px; }
.sic-modal-notes-label { display: flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--text-4); margin-bottom: 6px; }
.sic-modal-notes-body { font-size: 13px; color: var(--text-2); line-height: 1.6; margin-bottom: 6px; }
.sic-modal-notes-actions { font-size: 12px; color: var(--text-3); }

.sic-modal-refund-alert { display: flex; align-items: center; gap: 7px; padding: 9px 12px; border-radius: var(--radius); background: rgba(245,158,11,.07); border: 1px solid var(--gold); font-size: 12px; font-weight: 600; color: var(--gold-dark); }
.link-inline { background: none; border: none; padding: 0; cursor: pointer; color: var(--gold-dark); font-weight: 700; text-decoration: underline; font-size: inherit; }
</style>
