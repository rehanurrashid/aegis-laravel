<!--
  BpMilestoneRow.vue — single milestone row rendered inside BpContractRow accordion.

  Props:
    milestone  — { id, title, amount_cents, funded_cents, status, due_at,
                   auto_release_at, revision_notes, revision_count, submitted_at }
    contract   — parent contract object (needed by sub-modals)
    isActive   — bool: contract status === 'active'
    hasPaymentMethod — bool: provider has saved PM for funding

  Emits:
    fund(milestone)
    refund(milestone)
    review(milestone)
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

      <!-- Auto-release countdown chip (submitted only) -->
      <span
        v-if="sv(milestone.status) === 'submitted' && milestone.auto_release_at"
        class="ms-chip ms-chip--auto"
        :data-tooltip="`Auto-releases ${formatDate(milestone.auto_release_at)} if not reviewed`"
      >
        <AegisIcon name="clock" :size="11" />
        {{ autoReleaseLabel(milestone.auto_release_at) }}
      </span>

      <!-- Funded chip -->
      <span v-if="['funded','in_progress'].includes(sv(milestone.status))" class="ms-chip ms-chip--funded">
        <AegisIcon name="shield-check" :size="11" />
        Funded
      </span>

      <!-- Revision notes tooltip -->
      <span
        v-if="sv(milestone.status) === 'revision_requested' && milestone.revision_notes"
        class="ms-chip ms-chip--revision"
        :data-tooltip="milestone.revision_notes"
      >
        <AegisIcon name="refresh-cw" :size="11" />
        See notes
      </span>

      <!-- Released/paid chip -->
      <span v-if="['released','paid'].includes(sv(milestone.status))" class="ms-chip ms-chip--paid">
        <AegisIcon name="check-circle" :size="11" />
        Paid
      </span>

      <!-- Refunded chip -->
      <span v-if="sv(milestone.status) === 'refunded'" class="ms-chip ms-chip--refunded">
        <AegisIcon name="arrow-left" :size="11" />
        Refunded
      </span>

      <!-- ── Action buttons ── -->

      <!-- Fund: pending or pending_funding, contract active -->
      <button
        v-if="['pending','pending_funding'].includes(sv(milestone.status)) && isActive"
        type="button"
        class="btn btn-primary ms-action-btn"
        :disabled="!hasPaymentMethod"
        :data-tooltip="!hasPaymentMethod ? 'Add a payment method first' : 'Fund this milestone into escrow'"
        @click.stop="$emit('fund', milestone)"
      >
        <AegisIcon name="dollar" :size="12" />
        Fund
      </button>

      <!-- Refund escrow: funded/in_progress, before BP submits -->
      <button
        v-if="['funded','in_progress'].includes(sv(milestone.status)) && isActive"
        type="button"
        class="btn btn-ghost btn-danger-ghost ms-action-btn"
        data-tooltip="Refund escrow — only available before BP submits work"
        @click.stop="$emit('refund', milestone)"
      >
        <AegisIcon name="corner-down-left" :size="12" />
        Refund
      </button>

      <!-- Review: submitted — the primary CTA -->
      <button
        v-if="sv(milestone.status) === 'submitted'"
        type="button"
        class="btn btn-primary ms-action-btn"
        @click.stop="$emit('review', milestone)"
      >
        <AegisIcon name="check-square" :size="12" />
        Review Work
      </button>

      <!-- Dispute: disputed milestone -->
      <button
        v-if="sv(milestone.status) === 'disputed'"
        type="button"
        class="btn btn-outline ms-action-btn"
        data-tooltip="View or manage this dispute"
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
  milestone:        { type: Object,  required: true },
  contract:         { type: Object,  required: true },
  isActive:         { type: Boolean, default: false },
  hasPaymentMethod: { type: Boolean, default: false },
})

defineEmits(['fund', 'refund', 'review', 'dispute'])

// Unwrap backed enum
const sv = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

function formatCents(c) {
  return '$' + ((c ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })
}
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
function autoReleaseLabel(iso) {
  const diff = new Date(iso) - Date.now()
  if (diff <= 0) return 'releasing soon'
  const days  = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  return days > 0 ? `${days}d ${hours}h left` : `${hours}h left`
}

function badgeLabel(s) {
  return ({
    pending:            'Pending',
    pending_funding:    'Awaiting Funding',
    funded:             'Funded',
    in_progress:        'In Progress',
    submitted:          'Under Review',
    revision_requested: 'Revision Requested',
    approved:           'Approved',
    released:           'Paid',
    paid:               'Paid',
    disputed:           'Disputed',
    refunded:           'Refunded',
  })[sv(s)] ?? sv(s)
}
function badgeVariant(s) {
  return ({
    pending:            'neutral',
    pending_funding:    'neutral',
    funded:             'blue',
    in_progress:        'blue',
    submitted:          'gold',
    revision_requested: 'gold',
    approved:           'green',
    released:           'green',
    paid:               'green',
    disputed:           'red',
    refunded:           'neutral',
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

/* Status left-bar accent */
.ms-row--submitted    { border-left: 3px solid var(--gold); }
.ms-row--disputed     { border-left: 3px solid var(--red); }
.ms-row--pending_funding { border-left: 3px solid var(--text-4); }

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

/* Chips */
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
.ms-chip--funded {
  background: rgba(59,130,246,0.08);
  border-color: var(--blue, #3b82f6);
  color: var(--blue-dark, #1d4ed8);
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
.ms-chip--refunded {
  background: var(--surface-3);
  border-color: var(--border-dark);
  color: var(--text-3);
}

.ms-action-btn { font-size: 11px; padding: 4px 10px; height: 28px; }
</style>
