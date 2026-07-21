<!--
  BpContractRow.vue — sic-row pattern for contract rows.
  Receives raw BpContract Eloquent model (same shape as SupportServices/ContractModal).
  Emits @open(contract) — BpFinanceTable mounts ContractModal centrally.
-->
<template>
  <div class="bpcr-wrap" :class="`bpcr--${statusVal}`">
    <div class="bpcr-row">

      <!-- Col 1: Avatar + name + contract title + billing type + term -->
      <div class="bpcr-td bpcr-td--party" @click="$emit('open', contract)">
        <div class="bpcr-party">
          <div class="bpcr-avatar">{{ initials(bpName) }}</div>
          <div class="bpcr-party-info">
            <a
              v-if="bpSlug"
              :href="`/public/business/${bpSlug}`"
              class="bpcr-party-name bpcr-party-name--link"
              @click.stop
            >{{ bpName }}</a>
            <span v-else class="bpcr-party-name">{{ bpName }}</span>
            <span class="bpcr-service-name">{{ contract.title }}</span>
            <span class="bpcr-date-sub">{{ billingTypeLabel }} · {{ term }}</span>
          </div>
        </div>
      </div>

      <!-- Col 2: Status + review badge + amount + Rev 2 terms chip -->
      <div class="bpcr-td bpcr-td--status" @click="$emit('open', contract)">
        <div class="bpcr-badges">
          <AegisBadge :label="statusLabel" :variant="statusVariant" />
          <span v-if="submittedMilestoneCount > 0" class="bpcr-review-badge">
            {{ submittedMilestoneCount }} needs review
          </span>
        </div>
        <div class="bpcr-amount-sub">{{ formatCents(contract.total_value_cents) }}</div>
        <!-- Rev 2: terms chip -->
        <div v-if="contract.payment_structure" class="bpcr-terms-chip">
          <AegisIcon name="credit-card" :size="10" />
          {{ termsSummary }}
        </div>
        <!-- Rev 2: payment progress (active/completed) -->
        <div v-if="contract.payment_structure && ['active','completed'].includes(statusVal) && (contract.paid_cents ?? 0) > 0" class="bpcr-payment-progress">
          <div class="bpcr-payment-progress-bar">
            <div class="bpcr-payment-progress-fill" :style="{ width: paidPct + '%' }" />
          </div>
          <span class="bpcr-payment-progress-label">{{ formatCents(contract.paid_cents) }} paid</span>
        </div>
      </div>

      <!-- Col 3: Chevron -->
      <div class="bpcr-td bpcr-td--actions" @click="$emit('open', contract)">
        <button type="button" class="btn-icon" data-tooltip="View details & actions">
          <AegisIcon name="chevron-right" :size="15" />
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  contract: { type: Object, required: true },
})

defineEmits(['open'])

const sv = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

// Read native Eloquent model fields (same as ContractModal)
const bpName   = computed(() => props.contract.bp?.display_name ?? props.contract.bp_name ?? '—')
const bpSlug   = computed(() => props.contract.bp?.slug ?? props.contract.bp_slug ?? null)
const statusVal = computed(() => sv(props.contract.status))

const billingTypeLabel = computed(() => {
  const t = sv(props.contract.payment_type) || props.contract.billing_type || 'one_time'
  return ({ milestone: 'Milestone-based', retainer: 'Monthly retainer', one_time: 'One-time' })[t] ?? t
})

const term = computed(() => {
  // If already formatted string (DTO), use as-is
  if (typeof props.contract.term === 'string') return props.contract.term
  // Raw Eloquent model — derive from dates
  const start = props.contract.started_at
    ? new Date(props.contract.started_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
    : '—'
  const end = props.contract.completed_at
    ? new Date(props.contract.completed_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
    : 'Ongoing'
  return `${start} – ${end}`
})

const milestones = computed(() => props.contract.milestones ?? [])

const isMilestoneDriven = computed(() =>
  sv(props.contract.payment_type) === 'milestone' || milestones.value.length > 0
)

const submittedMilestoneCount = computed(() =>
  milestones.value.filter(m => sv(m.status) === 'submitted').length
)

// Rev 2: payment terms summary
const termsSummary = computed(() => {
  const s = props.contract.payment_structure
  const pct = props.contract.upfront_percentage ?? 0
  const map = {
    full_upfront:  '100% upfront',
    split:         `${pct}% upfront + ${100 - pct}% completion`,
    per_milestone: 'Per milestone',
    on_completion: 'Pay on completion',
  }
  return s ? (map[s] ?? s) : null
})

// Rev 2: payment progress percentage
const paidPct = computed(() => {
  const total = props.contract.total_value_cents ?? 0
  const paid  = props.contract.paid_cents ?? 0
  if (!total) return 0
  return Math.min(100, Math.round((paid / total) * 100))
})

const statusLabel = computed(() => ({
  active:            'Active',
  pending_signature: 'Awaiting Signature',
  pending_funding:   'Awaiting Signature',  // Rev 2: no funding step
  completed:         'Completed',
  cancelled:         'Cancelled',
  disputed:          'Disputed',
  draft:             'Draft',
})[statusVal.value] ?? statusVal.value)

const statusVariant = computed(() => ({
  active:            'green',
  pending_signature: 'gold',
  pending_funding:   'blue',
  completed:         'neutral',
  cancelled:         'red',
  disputed:          'red',
  draft:             'neutral',
})[statusVal.value] ?? 'neutral')

function formatCents(c) {
  return '$' + ((c ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })
}
function initials(name) {
  if (!name) return 'BP'
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}
</script>

<style scoped>
.bpcr-wrap {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
}
.bpcr-wrap:last-child { border-bottom: none; }

.bpcr--pending_signature { border-left: 3px solid var(--gold); }
.bpcr--pending_funding   { border-left: 3px solid var(--blue, #3b82f6); }
.bpcr--active            { border-left: 3px solid var(--green); }
.bpcr--disputed          { border-left: 3px solid var(--red); }
.bpcr--completed,
.bpcr--cancelled         { border-left: 3px solid var(--border-dark); opacity: 0.8; }

.bpcr-row {
  display: flex;
  align-items: stretch;
  cursor: pointer;
  transition: background var(--transition);
}
.bpcr-row:hover { background: var(--surface-2); }

.bpcr-td { padding: 10px 12px; font-family: var(--font-sans); }
.bpcr-td--party   { flex: 0 0 58%; min-width: 0; }
.bpcr-td--status  { flex: 1; min-width: 0; }
.bpcr-td--actions { flex: 0 0 8%; display: flex; align-items: center; justify-content: flex-end; }

.bpcr-party { display: flex; align-items: center; gap: 9px; }
.bpcr-avatar {
  width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
  background: var(--badge-bg-gold); color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700;
}
.bpcr-party-info  { min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.bpcr-party-name  { font-size: 13px; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bpcr-party-name--link { color: var(--gold-dark); text-decoration: none; }
.bpcr-party-name--link:hover { text-decoration: underline; color: var(--gold); }
.bpcr-service-name { font-size: 11px; color: var(--text-4); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bpcr-date-sub    { font-size: 11px; color: var(--text-4); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.bpcr-badges    { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 3px; }
.bpcr-amount-sub { font-size: 12px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.bpcr-terms-chip {
  display: flex; align-items: center; gap: 4px;
  font-size: 10px; color: var(--text-4); margin-top: 3px;
}
.bpcr-payment-progress { margin-top: 6px; }
.bpcr-payment-progress-bar {
  height: 3px; background: var(--border); border-radius: 2px; overflow: hidden;
}
.bpcr-payment-progress-fill {
  height: 100%; background: var(--green); border-radius: 2px;
  transition: width 0.3s ease;
}
.bpcr-payment-progress-label {
  font-size: 10px; color: var(--text-4); margin-top: 3px; display: block;
}

.bpcr-review-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700;
  color: var(--gold-dark); background: rgba(160,129,62,0.10);
  border: 1px solid var(--gold); border-radius: var(--radius-full);
  padding: 2px 7px;
}
</style>
