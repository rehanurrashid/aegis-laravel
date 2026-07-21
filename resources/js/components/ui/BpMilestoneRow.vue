<!--
  BpMilestoneRow.vue — single milestone row rendered inside BpContractRow accordion.
  Rev 2: direct-charge lifecycle — no escrow states.

  Props:
    milestone  — { id, title, amount_cents, paid_cents, status, due_at,
                   auto_approve_at, revision_notes, revision_count, submitted_at,
                   paid_at, payment_failed_at }
    contract   — parent contract object
    isActive   — bool: contract status === 'active'

  Emits:
    review(milestone)
    cancel(milestone)
    retry(milestone)
    dispute(milestone)
-->
<template>
  <div class="ms-row" :class="`ms-row--${sv(milestone.status)}`">

    <!-- left: title + meta -->
    <div class="ms-row-info">
      <div class="ms-row-title">{{ milestone.title }}</div>
      <div class="ms-row-meta">
        {{ formatCents(milestone.amount_cents) }}
        <template v-if="milestone.due_at">
          <span class="ms-meta-sep">·</span>
          Due {{ formatDate(milestone.due_at) }}
        </template>
        <template v-if="sv(milestone.status) === 'submitted' && milestone.submitted_at">
          <span class="ms-meta-sep">·</span>
          Submitted {{ formatDate(milestone.submitted_at) }}
        </template>
        <template v-if="sv(milestone.status) === 'paid' && milestone.paid_at">
          <span class="ms-meta-sep">·</span>
          Paid {{ formatDate(milestone.paid_at) }}
        </template>
        <template v-if="sv(milestone.status) === 'revision_requested' && milestone.revision_count > 1">
          <span class="ms-meta-sep">·</span>
          Revision #{{ milestone.revision_count }}
        </template>
      </div>
    </div>

    <!-- right: badge + chips + actions -->
    <div class="ms-row-right">

      <!-- Status badge -->
      <AegisBadge :label="badgeLabel(milestone.status)" :variant="badgeVariant(milestone.status)" />

      <!-- Auto-approve countdown chip (submitted only, Rev 2) -->
      <span
        v-if="sv(milestone.status) === 'submitted' && milestone.auto_approve_at"
        class="ms-chip ms-chip--auto"
        :data-tooltip="`Auto-approved ${formatDate(milestone.auto_approve_at)} if not reviewed`"
      >
        <AegisIcon name="clock" :size="11" />
        {{ autoApproveLabel(milestone.auto_approve_at) }}
      </span>

      <!-- Revision notes -->
      <span
        v-if="sv(milestone.status) === 'revision_requested' && milestone.revision_notes"
        class="ms-chip ms-chip--revision"
        :data-tooltip="milestone.revision_notes"
      >
        <AegisIcon name="refresh-cw" :size="11" />
        See notes
      </span>

      <!-- Pre-paid chip (full_upfront contracts) -->
      <span v-if="sv(milestone.status) === 'prepaid'" class="ms-chip ms-chip--paid">
        <AegisIcon name="check-circle" :size="11" />
        Pre-paid at signing
      </span>

      <!-- Paid chip -->
      <span v-if="['released','paid'].includes(sv(milestone.status))" class="ms-chip ms-chip--paid">
        <AegisIcon name="check-circle" :size="11" />
        Paid {{ milestone.paid_at ? formatDate(milestone.paid_at) : '' }}
      </span>

      <!-- Payment failed chip -->
      <span v-if="sv(milestone.status) === 'payment_failed'" class="ms-chip ms-chip--failed">
        <AegisIcon name="alert-triangle" :size="11" />
        Payment failed
      </span>

      <!-- ── Action buttons ── -->

      <!-- Review: submitted — primary CTA -->
      <button
        v-if="sv(milestone.status) === 'submitted'"
        type="button"
        class="btn btn-primary ms-action-btn"
        @click.stop="$emit('review', milestone)"
      >
        <AegisIcon name="check-square" :size="12" />
        Review
      </button>

      <!-- Retry payment (payment_failed) -->
      <button
        v-if="sv(milestone.status) === 'payment_failed' && isActive"
        type="button"
        class="btn btn-warning ms-action-btn"
        data-tooltip="Retry the direct charge to your card"
        @click.stop="$emit('retry', milestone)"
      >
        <AegisIcon name="refresh-cw" :size="12" />
        Retry payment
      </button>

      <!-- Cancel (pre-payment) -->
      <button
        v-if="['pending','in_progress'].includes(sv(milestone.status)) && isActive"
        type="button"
        class="btn btn-ghost btn-danger-ghost ms-action-btn"
        data-tooltip="Cancel this milestone — no payment was made"
        @click.stop="$emit('cancel', milestone)"
      >
        <AegisIcon name="x" :size="12" />
        Cancel
      </button>

      <!-- Dispute -->
      <button
        v-if="sv(milestone.status) === 'disputed'"
        type="button"
        class="btn btn-outline ms-action-btn"
        @click.stop="$emit('dispute', milestone)"
      >
        <AegisIcon name="alert-triangle" :size="12" />
        View Dispute
      </button>

    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  milestone: { type: Object,  required: true },
  contract:  { type: Object,  required: true },
  isActive:  { type: Boolean, default: false },
})

defineEmits(['review', 'cancel', 'retry', 'dispute'])

const sv = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

function formatCents(c) {
  return '$' + ((c ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })
}
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
function autoApproveLabel(iso) {
  const diff = new Date(iso) - Date.now()
  if (diff <= 0) return 'auto-approving soon'
  const days  = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  return days > 0 ? `${days}d ${hours}h left` : `${hours}h left`
}

function badgeLabel(s) {
  return ({
    pending:            'Awaiting work',
    pending_funding:    'Awaiting work',
    in_progress:        'In progress',
    funded:             'In progress',
    submitted:          'Awaiting review',
    revision_requested: 'Revision requested',
    approved:           'Approved — payment fired',
    released:           'Paid',
    paid:               'Paid',
    prepaid:            'Pre-paid',
    payment_failed:     'Payment failed',
    disputed:           'Disputed',
    refunded:           'Refunded',
    cancelled:          'Cancelled',
  })[sv(s)] ?? sv(s)
}
function badgeVariant(s) {
  return ({
    pending:            'neutral',
    pending_funding:    'neutral',
    in_progress:        'blue',
    funded:             'blue',
    submitted:          'warning',
    revision_requested: 'warning',
    approved:           'blue',
    released:           'success',
    paid:               'success',
    prepaid:            'success',
    payment_failed:     'danger',
    disputed:           'danger',
    refunded:           'neutral',
    cancelled:          'neutral',
  })[sv(s)] ?? 'neutral'
}
</script>

<style scoped>
.ms-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 16px 10px 32px;
  border-top: 1px solid var(--border);
  background: var(--surface-2);
  transition: background var(--transition);
}
.ms-row:hover { background: var(--surface-3); }

.ms-row--submitted     { border-left: 3px solid var(--gold); }
.ms-row--disputed      { border-left: 3px solid var(--red); }
.ms-row--payment_failed { border-left: 3px solid var(--red); }

.ms-row-info { flex: 1; min-width: 0; }
.ms-row-title {
  font-family: var(--font-sans);
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 3px;
}
.ms-row-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
  font-family: var(--font-sans);
  font-size: 11px;
  color: var(--text-4);
}
.ms-meta-sep { color: var(--border-dark); }

.ms-row-right {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.ms-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: var(--radius-full);
  font-family: var(--font-sans);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.2px;
  white-space: nowrap;
  border: 1px solid;
}
.ms-chip--auto {
  background: rgba(160,129,62,0.08);
  border-color: var(--gold);
  color: var(--gold-dark);
}
.ms-chip--revision {
  background: rgba(245,158,11,0.08);
  border-color: var(--amber, #f59e0b);
  color: var(--amber-dark, #92400e);
  cursor: help;
}
.ms-chip--paid {
  background: rgba(34,197,94,0.08);
  border-color: var(--green);
  color: var(--green-dark, #15803d);
}
.ms-chip--failed {
  background: rgba(239,68,68,0.08);
  border-color: var(--red);
  color: var(--red);
}

.ms-action-btn { font-size: 11px; padding: 4px 10px; height: 28px; }
</style>
