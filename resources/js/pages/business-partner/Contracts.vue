<!--
  pages/business-partner/Contracts.vue — active + completed contracts.

  Wave 1 fixes:
  - btn-sm removed (banned)
  - Non-standard tab-btn/is-active classes → tabs-segmented/tab-pill
  - Redundant global component imports removed
  - Contract detail opens BpContractDetailModal (Wave 4 builds full version;
    Wave 1 wires the open pattern with a basic detail view)
  - Cancelled tab added
  - Escrow balance columns added to table (funded/released)
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Work"
      title="Contracts"
      :subtitle="`${activeCount} active · ${completedCount} completed`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="agreement"   :value="activeCount"    label="Active"    bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="check-circle" :value="completedCount" label="Completed" />
      <AegisStatChip icon="x-circle"    :value="cancelledCount"  label="Cancelled" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
      <AegisStatChip icon="dollar"      :value="pricing.formatCents(totalEarned)" label="Total value (completed)" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
    </div>

    <div class="tabs-segmented">
      <button
        v-for="t in tabs"
        :key="t.value"
        type="button"
        class="tab-pill"
        :class="{ 'is-active': tab === t.value }"
        @click="tab = t.value"
      >
        {{ t.label }}
        <span v-if="countForTab(t.value)" class="tab-pill-count">{{ countForTab(t.value) }}</span>
      </button>
    </div>

    <div v-if="visible.length" class="card-list">
      <AegisCard
        v-for="c in visible"
        :key="c.id"
        class="contract-card"
        hoverable
        @click="openDetail(c)"
      >
        <div class="contract-card-body">
          <div class="contract-card-main">
            <div class="contract-card-title">{{ c.title }}</div>
            <div class="contract-card-meta">
              <span>
                <AegisIcon name="user" :size="12" />
                {{ c.client_name }}
              </span>
              <span>
                <AegisIcon name="dollar" :size="12" />
                {{ pricing.formatCents(c.amount_cents) }}
              </span>
              <span>
                <AegisIcon name="layers" :size="12" />
                {{ c.payment_type === 'milestone' ? 'Milestone-based' : 'One-time' }}
              </span>
              <span v-if="c.signed_at">
                <AegisIcon name="calendar" :size="12" />
                Signed {{ c.signed_at }}
              </span>
            </div>
          </div>

          <div class="contract-card-right">
            <AegisBadge :label="statusLabel(c.status)" :variant="variant(c.status)" />
            <button
              type="button"
              class="btn btn-outline"
              @click.stop="openDetail(c)"
            >
              <AegisIcon name="eye" :size="13" />
              Details
            </button>
          </div>
        </div>

        <!-- Escrow strip (active milestone contracts) -->
        <div
          v-if="c.status === 'active' && c.payment_type === 'milestone' && c.amount_cents > 0"
          class="contract-escrow-strip"
          @click.stop
        >
          <div class="contract-escrow-label">
            <AegisIcon name="shield-check" :size="12" />
            Escrow:
            <strong>{{ pricing.formatCents(c.escrow_funded_cents ?? 0) }} held</strong>
            · {{ pricing.formatCents(c.escrow_released_cents ?? 0) }} released
            · {{ pricing.formatCents(c.escrow_refunded_cents ?? 0) }} refunded
          </div>
          <div class="contract-escrow-bar">
            <div
              class="contract-escrow-bar-released"
              :style="{ width: pct(c.escrow_released_cents, c.amount_cents) }"
            />
            <div
              class="contract-escrow-bar-held"
              :style="{ width: pct((c.escrow_funded_cents ?? 0) - (c.escrow_released_cents ?? 0), c.amount_cents) }"
            />
          </div>
        </div>
      </AegisCard>
    </div>

    <AegisEmptyState
      v-else
      icon="agreement"
      :title="tab === 'active' ? 'No active contracts' : tab === 'completed' ? 'No completed contracts' : 'No cancelled contracts'"
      :description="tab === 'active' ? 'Accepted proposals become contracts. Browse open jobs to find work.' : ''"
    >
      <template v-if="tab === 'active'" #action>
        <a :href="route('bp.jobs.index')" class="btn btn-primary">Find jobs</a>
      </template>
    </AegisEmptyState>

    <!-- Contract detail modal — basic in Wave 1, full in Wave 4 -->
    <BpContractDetailModal :contract="activeContract" />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout           from '@/layouts/AppLayout.vue'
import BpContractDetailModal from '@/components/modals/BpContractDetailModal.vue'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({ contracts: { type: Array, default: () => [] } })
const pricing = usePricingStore()

const tab            = ref('active')
const activeContract = ref(null)

const tabs = [
  { value: 'active',    label: 'Active' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
]

const activeCount    = computed(() => props.contracts.filter((c) => c.status === 'active').length)
const completedCount = computed(() => props.contracts.filter((c) => c.status === 'completed').length)
const cancelledCount = computed(() => props.contracts.filter((c) => c.status === 'cancelled').length)
const totalEarned    = computed(() =>
  props.contracts
    .filter((c) => c.status === 'completed')
    .reduce((s, c) => s + (c.amount_cents || 0), 0),
)

const visible = computed(() => props.contracts.filter((c) => c.status === tab.value))

function countForTab(v) {
  return props.contracts.filter((c) => c.status === v).length || null
}

function statusLabel(s) {
  return {
    draft:             'Draft',
    pending_signature: 'Awaiting Signature',
    pending_funding:   'Awaiting Funding',
    active:            'Active',
    completed:         'Completed',
    cancelled:         'Cancelled',
    disputed:          'Disputed',
  }[s] ?? s
}

function variant(s) {
  return {
    draft:             'neutral',
    pending_signature: 'gold',
    pending_funding:   'gold',
    active:            'green',
    completed:         'blue',
    cancelled:         'neutral',
    disputed:          'red',
  }[s] ?? 'neutral'
}

function pct(num, denom) {
  if (!denom) return '0%'
  return Math.min(100, Math.round(((num || 0) / denom) * 100)) + '%'
}

function openDetail(c) {
  activeContract.value = c
}
</script>
