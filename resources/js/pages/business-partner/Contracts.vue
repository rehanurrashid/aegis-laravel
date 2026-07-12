<!--
  pages/business-partner/Contracts.vue — active + pending + completed contracts.

  Wave 1+4 complete:
  - btn-sm removed
  - Tab classes: tabs-segmented / tab-pill
  - Redundant global imports removed
  - Card-based layout (not table)
  - All new statuses: pending_signature | pending_funding | active | completed | cancelled
  - Escrow progress strip on active milestone contracts
  - "Sign contract" badge on pending_signature cards
  - Opens BpContractDetailModal on click
  - Counts in tab pills
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Work"
      title="Contracts"
      :subtitle="`${activeCount} active · ${pendingCount} pending · ${completedCount} completed`"
    />

    <div class="stat-chips-row">
      <AegisStatChip
        icon="agreement"
        :value="activeCount"
        label="Active"
        bg-color="var(--icon-bg-green)"
        icon-color="var(--green-dark)"
      />
      <AegisStatChip
        icon="file-pen"
        :value="pendingCount"
        label="Pending action"
        bg-color="var(--icon-bg-gold)"
        icon-color="var(--gold-dark)"
      />
      <AegisStatChip
        icon="check-circle"
        :value="completedCount"
        label="Completed"
      />
      <AegisStatChip
        icon="dollar"
        :value="pricing.formatCents(totalEarned)"
        label="Total value (completed)"
        bg-color="var(--icon-bg-gold)"
        icon-color="var(--gold-dark)"
      />
    </div>

    <!-- Pending action banner (sign needed) -->
    <div v-if="signatureNeeded.length" class="alert-banner alert-banner-warning">
      <AegisIcon name="file-pen" :size="16" />
      <span>
        <strong>{{ signatureNeeded.length }} contract{{ signatureNeeded.length === 1 ? '' : 's' }}</strong>
        awaiting your signature — click to review and sign.
      </span>
    </div>

    <!-- Tabs -->
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

    <!-- Contract cards -->
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
            <!-- Sign CTA badge -->
            <div v-if="c.status === 'pending_signature' && !c.bp_has_signed" class="contract-sign-needed">
              <AegisIcon name="alert-triangle" :size="12" />
              Signature needed
            </div>

            <AegisBadge :label="statusLabel(c.status)" :variant="statusVariant(c.status)" />

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
            <strong>{{ pricing.formatCents(escrowHeld(c)) }} held</strong>
            · {{ pricing.formatCents(c.escrow_released_cents ?? 0) }} released
          </div>
          <div class="contract-escrow-bar">
            <div
              class="contract-escrow-bar-released"
              :style="{ width: escrowPct(c.escrow_released_cents, c.amount_cents) }"
            />
            <div
              class="contract-escrow-bar-held"
              :style="{ width: escrowPct(escrowHeld(c), c.amount_cents) }"
            />
          </div>
        </div>
      </AegisCard>
    </div>

    <AegisEmptyState
      v-else
      icon="agreement"
      :title="emptyTitle"
      :description="tab === 'active' ? 'Accepted proposals become contracts. Browse open jobs to find work.' : ''"
    >
      <template v-if="tab === 'active'" #action>
        <a :href="route('bp.jobs.index')" class="btn btn-primary">Find jobs</a>
      </template>
    </AegisEmptyState>

    <!-- Contract detail modal -->
    <BpContractDetailModal
      :contract="activeContract"
      @update:model-value="activeContract = null"
    />
  </AppLayout>

  <ReviewContractModal
    v-model="showReview"
    :contract="reviewContract"
    post-route="bp.contracts.review"
    dismiss-route="bp.contracts.review.dismiss"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import AppLayout              from '@/layouts/AppLayout.vue'
import BpContractDetailModal  from '@/components/modals/BpContractDetailModal.vue'
import ReviewContractModal    from '@/components/modals/ReviewContractModal.vue'
import { usePricingStore }    from '@/stores/pricing'
import { usePage }            from '@inertiajs/vue3'

const props   = defineProps({ contracts: { type: Array, default: () => [] } })
const pricing = usePricingStore()

const tab            = ref('active')
const activeContract = ref(null)

// ── Review auto-trigger ────────────────────────────────────────────────────────
const showReview     = ref(false)
const reviewContract = ref(null)
const page           = usePage()

watch(
  () => page.props.flash?.review_contract_id,
  (contractId) => {
    if (!contractId) return
    const c = props.contracts.find(c => c.id === contractId)
    if (!c) return
    reviewContract.value = {
      id:                c.id,
      title:             c.title,
      counterparty_name: c.client_name ?? 'the Practitioner',
    }
    showReview.value = true
  },
  { immediate: true }
)

// ── Tabs ──────────────────────────────────────────────────────────────────────
const tabs = [
  { value: 'active',    label: 'Active' },
  { value: 'pending',   label: 'Pending' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
]

// ── Computed ──────────────────────────────────────────────────────────────────
const activeCount    = computed(() => props.contracts.filter((c) => c.status === 'active').length)
const pendingCount   = computed(() => props.contracts.filter((c) => ['pending_signature', 'pending_funding'].includes(c.status)).length)
const completedCount = computed(() => props.contracts.filter((c) => c.status === 'completed').length)
const signatureNeeded = computed(() => props.contracts.filter((c) => c.status === 'pending_signature' && !c.bp_has_signed))

const totalEarned = computed(() =>
  props.contracts
    .filter((c) => c.status === 'completed')
    .reduce((s, c) => s + (c.amount_cents || 0), 0),
)

function countForTab(v) {
  if (v === 'active')    return activeCount.value || null
  if (v === 'pending')   return pendingCount.value || null
  if (v === 'completed') return completedCount.value || null
  return props.contracts.filter((c) => c.status === 'cancelled').length || null
}

const visible = computed(() => {
  if (tab.value === 'pending') return props.contracts.filter((c) => ['pending_signature', 'pending_funding'].includes(c.status))
  return props.contracts.filter((c) => c.status === tab.value)
})

const emptyTitle = computed(() => ({
  active:    'No active contracts',
  pending:   'No pending contracts',
  completed: 'No completed contracts',
  cancelled: 'No cancelled contracts',
}[tab.value] ?? 'No contracts'))

// ── Escrow helpers ────────────────────────────────────────────────────────────
function escrowHeld(c) {
  return Math.max(0,
    (c.escrow_funded_cents ?? 0) -
    (c.escrow_released_cents ?? 0) -
    (c.escrow_refunded_cents ?? 0),
  )
}

function escrowPct(num, total) {
  if (!total) return '0%'
  return Math.min(100, Math.round(((num ?? 0) / total) * 100)) + '%'
}

// ── Status labels ─────────────────────────────────────────────────────────────
function statusLabel(s) {
  return {
    draft: 'Draft', pending_signature: 'Awaiting Signature', pending_funding: 'Awaiting Funding',
    active: 'Active', completed: 'Completed', cancelled: 'Cancelled', disputed: 'Disputed',
  }[s] ?? s
}

function statusVariant(s) {
  return {
    draft: 'neutral', pending_signature: 'gold', pending_funding: 'blue',
    active: 'green', completed: 'blue', cancelled: 'neutral', disputed: 'red',
  }[s] ?? 'neutral'
}

// ── Actions ───────────────────────────────────────────────────────────────────
function openDetail(c) {
  activeContract.value = c
}
</script>

<style scoped>
.contract-card-body { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.contract-card-main { flex: 1; min-width: 0; }
.contract-card-title { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
.contract-card-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; font-size: 12px; color: var(--text-secondary); }
.contract-card-meta span { display: inline-flex; align-items: center; gap: 4px; }
.contract-card-right { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; flex-shrink: 0; }
.contract-sign-needed { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: var(--gold-dark); }

.contract-escrow-strip { padding: 10px 0 2px; margin-top: 12px; border-top: 1px solid var(--border-subtle); }
.contract-escrow-label { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-secondary); margin-bottom: 6px; }
.contract-escrow-bar { height: 4px; background: var(--surface-2); border-radius: 2px; display: flex; overflow: hidden; }
.contract-escrow-bar-released { background: var(--green-dark); transition: width 0.3s; }
.contract-escrow-bar-held { background: var(--gold-dark); transition: width 0.3s; }
</style>
