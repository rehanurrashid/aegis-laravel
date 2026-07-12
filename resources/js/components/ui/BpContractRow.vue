<!--
  BpContractRow.vue — sic-row pattern for contract rows.
  Emits @open(contract) — parent (BpFinanceTable) mounts ContractModal centrally.
-->
<template>
  <div class="bpcr-wrap" :class="`bpcr--${sv(contract.status)}`">
    <div class="bpcr-row">

      <!-- Col 1: Avatar + name + contract title + billing type -->
      <div class="bpcr-td bpcr-td--party" @click="$emit('open', contract)">
        <div class="bpcr-party">
          <div class="bpcr-avatar">{{ initials(contract.bp_name) }}</div>
          <div class="bpcr-party-info">
            <a
              v-if="contract.bp_slug"
              :href="`/public/business/${contract.bp_slug}`"
              class="bpcr-party-name bpcr-party-name--link"
              @click.stop
            >{{ contract.bp_name }}</a>
            <span v-else class="bpcr-party-name">{{ contract.bp_name }}</span>
            <span class="bpcr-service-name">{{ contract.title }}</span>
            <span class="bpcr-date-sub">{{ contract.billing_type_label }} · {{ contract.term }}</span>
          </div>
        </div>
      </div>

      <!-- Col 2: Status + review badge + amount + escrow line -->
      <div class="bpcr-td bpcr-td--status" @click="$emit('open', contract)">
        <div class="bpcr-badges">
          <AegisBadge :label="contractStatusLabel(contract.status)" :variant="contractStatusVariant(contract.status)" />
          <span v-if="submittedMilestoneCount > 0" class="bpcr-review-badge">
            {{ submittedMilestoneCount }} needs review
          </span>
        </div>
        <div class="bpcr-amount-sub">{{ formatCents(contract.total_cents) }}</div>
        <div v-if="isMilestoneDriven && sv(contract.status) === 'active'" class="bpcr-escrow-line">
          <span class="bpcr-escrow-held">
            <AegisIcon name="shield-check" :size="10" />
            {{ formatCents(contract.escrow_held_cents ?? 0) }} held
          </span>
          <template v-if="(contract.unfunded_cents ?? 0) > 0">
            <span class="bpcr-escrow-sep">·</span>
            <span class="bpcr-escrow-unfunded">{{ formatCents(contract.unfunded_cents) }} unfunded</span>
          </template>
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

const isMilestoneDriven = computed(() =>
  props.contract.billing_type === 'milestone' || (props.contract.milestones?.length ?? 0) > 0
)

const submittedMilestoneCount = computed(() =>
  (props.contract.milestones ?? []).filter(m => sv(m.status) === 'submitted').length
)

function formatCents(c) {
  return '$' + ((c ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })
}
function initials(name) {
  if (!name) return 'BP'
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}
function contractStatusLabel(s) {
  return ({
    active:            'Active',
    pending_signature: 'Awaiting Signature',
    pending_funding:   'Awaiting Funding',
    completed:         'Completed',
    cancelled:         'Cancelled',
    disputed:          'Disputed',
    draft:             'Draft',
  })[sv(s)] ?? sv(s)
}
function contractStatusVariant(s) {
  return ({
    active:            'green',
    pending_signature: 'gold',
    pending_funding:   'blue',
    completed:         'neutral',
    cancelled:         'red',
    disputed:          'red',
    draft:             'neutral',
  })[sv(s)] ?? 'neutral'
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
.bpcr-escrow-line { display: flex; align-items: center; gap: 4px; font-size: 10px; flex-wrap: wrap; }
.bpcr-escrow-held     { color: var(--blue-dark, #1d4ed8); font-weight: 600; display: flex; align-items: center; gap: 3px; }
.bpcr-escrow-sep      { color: var(--border-dark); }
.bpcr-escrow-unfunded { color: var(--text-4); }

.bpcr-review-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700;
  color: var(--gold-dark); background: rgba(160,129,62,0.10);
  border: 1px solid var(--gold); border-radius: var(--radius-full);
  padding: 2px 7px;
}
</style>
