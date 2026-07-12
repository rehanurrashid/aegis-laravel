<!--
  pages/business-partner/Finances.vue — Wave 8 complete rebuild.

  Sections:
  1. Hero + stat chips (lifetime / YTD / in escrow / payout method)
  2. Stripe Connect status banner (if not connected)
  3. Tabs: Overview · Contracts · Payouts · Ledger
  4. Overview: pending milestones + upcoming auto-releases
  5. Contracts: per-contract escrow breakdown + milestone status
  6. Payouts: full payout history table
  7. Escrow ledger: raw fund/release/refund events for transparency

  Props from BusinessPartner\FinancesController::index()
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Money"
      title="Finances"
      subtitle="Earnings, escrow, and payout history."
    />

    <div class="stat-chips-row">
      <AegisStatChip
        icon="dollar"
        :value="pricing.formatCents(summary.lifetime_cents)"
        label="Lifetime earned"
      />
      <AegisStatChip
        icon="trending-up"
        :value="pricing.formatCents(summary.year_cents)"
        label="This year"
        bg-color="var(--icon-bg-green)"
        icon-color="var(--green-dark)"
      />
      <AegisStatChip
        icon="hourglass"
        :value="pricing.formatCents(summary.escrow_held_cents)"
        label="In escrow"
        bg-color="var(--icon-bg-gold)"
        icon-color="var(--gold-dark)"
      />
      <AegisStatChip
        icon="send"
        :value="pricing.formatCents(summary.pending_cents)"
        label="Awaiting approval"
        bg-color="var(--icon-bg-blue)"
        icon-color="var(--blue-dark)"
      />
    </div>

    <!-- Stripe Connect warning -->
    <div v-if="!stripe_connected" class="alert-banner alert-banner-warning">
      <AegisIcon name="alert-triangle" :size="16" />
      <div>
        <strong>Stripe Connect not set up.</strong>
        You won't receive escrow payouts until you complete Stripe Connect onboarding.
        Go to <a :href="route('bp.settings')" class="link-gold">Settings → Payment Setup</a> to connect.
      </div>
    </div>

    <!-- Demo mode notice -->
    <div v-if="stripe_demo" class="alert-banner alert-banner-blue">
      <AegisIcon name="info" :size="16" />
      <span>Demo mode — Stripe payouts are simulated. No real funds will move.</span>
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
        <span v-if="tabCount(t.value)" class="tab-pill-count">{{ tabCount(t.value) }}</span>
      </button>
    </div>

    <!-- ── TAB: OVERVIEW ───────────────────────────────────────────────── -->
    <div v-show="tab === 'overview'">

      <!-- Upcoming auto-releases -->
      <div v-if="upcomingAutoReleases.length" class="finances-section">
        <div class="finances-section-title">
          <AegisIcon name="hourglass" :size="14" />
          Auto-release upcoming
        </div>
        <div class="auto-release-list">
          <div v-for="m in upcomingAutoReleases" :key="m.id" class="auto-release-row">
            <div class="auto-release-info">
              <div class="auto-release-title">{{ m.title }}</div>
              <div class="auto-release-meta">{{ m.contract_title }}</div>
            </div>
            <div class="auto-release-right">
              <div class="auto-release-amount">{{ pricing.formatCents(m.funded_cents) }}</div>
              <div class="auto-release-date">{{ formatAutoRelease(m.auto_release_at) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending milestones under review -->
      <div v-if="submittedMilestones.length" class="finances-section">
        <div class="finances-section-title">
          <AegisIcon name="clock" :size="14" />
          Under review ({{ submittedMilestones.length }})
        </div>
        <div class="milestone-review-list">
          <div v-for="m in submittedMilestones" :key="m.id" class="milestone-review-row">
            <div>
              <div class="milestone-review-title">{{ m.title }}</div>
              <div class="milestone-review-meta">{{ m.contract_title }}</div>
            </div>
            <div class="milestone-review-right">
              <div class="milestone-review-amount">{{ pricing.formatCents(m.funded_cents) }}</div>
              <div v-if="m.auto_release_at" class="milestone-review-auto">
                Auto-releases {{ formatAutoRelease(m.auto_release_at) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <AegisEmptyState
        v-if="!upcomingAutoReleases.length && !submittedMilestones.length"
        icon="dollar"
        title="No pending payments"
        description="Funded milestones awaiting review and upcoming auto-releases will appear here."
      />
    </div>

    <!-- ── TAB: CONTRACTS ─────────────────────────────────────────────── -->
    <div v-show="tab === 'contracts'">
      <AegisEmptyState
        v-if="!activeContracts.length"
        icon="agreement"
        title="No active contracts"
        description="Active contracts with escrow breakdowns appear here."
      />
      <div v-for="c in activeContracts" :key="c.id" class="bp-contract-card">
        <div class="bp-contract-card-header">
          <div>
            <div class="bp-contract-card-title">{{ c.title }}</div>
            <div class="bp-contract-card-meta">{{ c.client_name }}</div>
          </div>
          <div class="bp-contract-card-badges">
            <AegisBadge :label="contractStatusLabel(c.status)" :variant="contractStatusVariant(c.status)" />
          </div>
        </div>

        <!-- Escrow summary row -->
        <div class="bp-contract-escrow">
          <div class="bp-contract-escrow-item">
            <div class="bp-contract-escrow-label">Total value</div>
            <div class="bp-contract-escrow-value">{{ pricing.formatCents(c.total_value_cents) }}</div>
          </div>
          <div class="bp-contract-escrow-item">
            <div class="bp-contract-escrow-label">In escrow</div>
            <div class="bp-contract-escrow-value is-held">{{ pricing.formatCents(c.escrow_held_cents) }}</div>
          </div>
          <div class="bp-contract-escrow-item">
            <div class="bp-contract-escrow-label">Released to you</div>
            <div class="bp-contract-escrow-value is-released">{{ pricing.formatCents(c.escrow_released_cents) }}</div>
          </div>
          <div class="bp-contract-escrow-item">
            <div class="bp-contract-escrow-label">Refunded</div>
            <div class="bp-contract-escrow-value">{{ pricing.formatCents(c.escrow_refunded_cents) }}</div>
          </div>
        </div>

        <!-- Escrow bar -->
        <div v-if="c.total_value_cents > 0" class="bp-escrow-bar">
          <div class="bp-escrow-bar-released" :style="{ width: escrowPct(c.escrow_released_cents, c.total_value_cents) }" />
          <div class="bp-escrow-bar-held"     :style="{ width: escrowPct(c.escrow_held_cents, c.total_value_cents) }" />
        </div>

        <!-- Milestone list -->
        <div v-if="c.milestones?.length" class="bp-milestone-list">
          <div v-for="m in c.milestones" :key="m.id" class="bp-milestone-row">
            <div class="bp-milestone-title">{{ m.title }}</div>
            <div class="bp-milestone-amount">{{ pricing.formatCents(m.amount_cents) }}</div>
            <AegisBadge :label="msLabel(m.status)" :variant="msVariant(m.status)" />
            <div v-if="m.auto_release_at && m.status === 'submitted'" class="bp-milestone-auto">
              <AegisIcon name="hourglass" :size="11" />
              {{ formatAutoRelease(m.auto_release_at) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── TAB: PAYOUTS ───────────────────────────────────────────────── -->
    <div v-show="tab === 'payouts'">
      <AegisEmptyState
        v-if="!payouts.length"
        icon="bank"
        title="No payouts yet"
        description="Escrow releases appear here once the provider approves your milestones."
      />
      <div v-else class="payout-table">
        <div class="payout-table-head">
          <span>Description</span>
          <span>Amount</span>
          <span>Date</span>
          <span>Status</span>
        </div>
        <div v-for="p in payouts" :key="p.id" class="payout-table-row">
          <div class="payout-desc">{{ p.description || 'Milestone payment' }}</div>
          <div class="payout-amount">{{ pricing.formatCents(p.amount_cents) }}</div>
          <div class="payout-date">{{ p.paid_at || p.released_at || p.created_at || '—' }}</div>
          <div class="payout-status">
            <AegisBadge
              :label="{ pending: 'Pending', paid: 'Paid', failed: 'Failed' }[p.status] ?? p.status"
              :variant="{ pending: 'gold', paid: 'green', failed: 'red' }[p.status] ?? 'neutral'"
            />
            <span v-if="p.refunded_cents > 0" class="payout-refunded">
              − {{ pricing.formatCents(p.refunded_cents) }} refunded
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── TAB: LEDGER ────────────────────────────────────────────────── -->
    <div v-show="tab === 'ledger'">
      <div class="finances-section-title" style="margin-bottom:14px;">
        <AegisIcon name="list" :size="14" />
        Escrow activity log (last 20 events)
      </div>
      <AegisEmptyState
        v-if="!escrowLedger.length"
        icon="list"
        title="No escrow activity"
        description="Fund, release, and refund events will appear here as escrow moves."
      />
      <div v-else class="ledger-list">
        <div v-for="e in escrowLedger" :key="e.id" class="ledger-row">
          <div class="ledger-kind" :class="`ledger-kind--${e.kind}`">
            <AegisIcon :name="ledgerIcon(e.kind)" :size="13" />
            {{ ledgerLabel(e.kind) }}
          </div>
          <div class="ledger-desc">{{ e.description }}</div>
          <div class="ledger-amount" :class="{ 'is-positive': ['release'].includes(e.kind) }">
            {{ ['refund','split_refund'].includes(e.kind) ? '−' : '+' }}{{ pricing.formatCents(e.amount_cents) }}
          </div>
          <div class="ledger-date">{{ e.created_at }}</div>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout       from '@/layouts/AppLayout.vue'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  summary:           { type: Object, default: () => ({ lifetime_cents: 0, year_cents: 0, pending_cents: 0, escrow_held_cents: 0, payout_method: null }) },
  payouts:           { type: Array,  default: () => [] },
  activeContracts:   { type: Array,  default: () => [] },
  pendingMilestones: { type: Array,  default: () => [] },
  escrowLedger:      { type: Array,  default: () => [] },
  stripe_connected:  { type: Boolean, default: false },
  stripe_demo:       { type: Boolean, default: false },
  stripe_account_id: { type: String,  default: null },
})

const pricing = usePricingStore()
const tab     = ref('overview')

// ── Tabs ──────────────────────────────────────────────────────────────────────
const tabs = [
  { value: 'overview',   label: 'Overview' },
  { value: 'contracts',  label: 'Contracts' },
  { value: 'payouts',    label: 'Payouts' },
  { value: 'ledger',     label: 'Escrow ledger' },
]

function tabCount(v) {
  if (v === 'contracts') return props.activeContracts.length || null
  if (v === 'payouts')   return props.payouts.filter(p => p.status === 'pending').length || null
  return null
}

// ── Computed ──────────────────────────────────────────────────────────────────
const submittedMilestones = computed(() =>
  props.pendingMilestones.filter(m => m.status === 'submitted'),
)

const upcomingAutoReleases = computed(() =>
  props.pendingMilestones
    .filter(m => m.status === 'submitted' && m.auto_release_at)
    .sort((a, b) => new Date(a.auto_release_at) - new Date(b.auto_release_at)),
)

// ── Helpers ───────────────────────────────────────────────────────────────────
function escrowPct(num, total) {
  if (!total) return '0%'
  return Math.min(100, Math.round(((num ?? 0) / total) * 100)) + '%'
}

function formatAutoRelease(iso) {
  if (!iso) return '—'
  const diff  = new Date(iso) - Date.now()
  if (diff <= 0) return 'imminently'
  const days  = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  return days > 0 ? `in ${days}d ${hours}h` : `in ${hours}h`
}

function contractStatusLabel(s) {
  return { active: 'Active', pending_signature: 'Awaiting Signature', pending_funding: 'Awaiting Funding', completed: 'Completed', cancelled: 'Cancelled' }[s] ?? s
}
function contractStatusVariant(s) {
  return { active: 'green', pending_signature: 'gold', pending_funding: 'blue', completed: 'neutral', cancelled: 'neutral' }[s] ?? 'neutral'
}

function msLabel(s) {
  return {
    pending: 'Pending', pending_funding: 'Awaiting Funding', funded: 'Funded', in_progress: 'In Progress',
    submitted: 'Under Review', revision_requested: 'Revision', approved: 'Approved',
    released: 'Paid', paid: 'Paid', disputed: 'Disputed', refunded: 'Refunded',
  }[s] ?? s
}
function msVariant(s) {
  return {
    pending: 'neutral', pending_funding: 'neutral', funded: 'blue', in_progress: 'blue',
    submitted: 'gold', revision_requested: 'gold', approved: 'green', released: 'green',
    paid: 'green', disputed: 'red', refunded: 'neutral',
  }[s] ?? 'neutral'
}

function ledgerIcon(kind) {
  return { fund: 'arrow-down-circle', release: 'arrow-up-circle', refund: 'arrow-left-circle', split_refund: 'split' }[kind] ?? 'circle'
}
function ledgerLabel(kind) {
  return { fund: 'Funded', release: 'Released', refund: 'Refunded', split_refund: 'Split refund' }[kind] ?? kind
}
</script>

<style scoped>
/* ── Sections ── */
.finances-section { margin-bottom: 24px; }
.finances-section-title { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-tertiary); margin-bottom: 10px; }

/* ── Auto-release list ── */
.auto-release-list { display: flex; flex-direction: column; gap: 1px; border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
.auto-release-row  { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; border-bottom: 1px solid var(--border); background: var(--surface); }
.auto-release-row:last-child { border-bottom: none; }
.auto-release-title  { font-size: 13px; font-weight: 600; color: var(--text); }
.auto-release-meta   { font-size: 11px; color: var(--text-tertiary); }
.auto-release-right  { text-align: right; }
.auto-release-amount { font-size: 14px; font-weight: 700; color: var(--green-dark); }
.auto-release-date   { font-size: 11px; color: var(--text-tertiary); }

/* ── Under review list ── */
.milestone-review-list   { display: flex; flex-direction: column; gap: 1px; border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
.milestone-review-row    { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; border-bottom: 1px solid var(--border); background: rgba(202,158,72,0.04); }
.milestone-review-row:last-child { border-bottom: none; }
.milestone-review-title  { font-size: 13px; font-weight: 600; color: var(--text); }
.milestone-review-meta   { font-size: 11px; color: var(--text-tertiary); }
.milestone-review-right  { text-align: right; }
.milestone-review-amount { font-size: 14px; font-weight: 700; color: var(--gold-dark); }
.milestone-review-auto   { font-size: 11px; color: var(--text-tertiary); }

/* ── Contracts ── */
.bp-contract-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px 24px; margin-bottom: 14px; }
.bp-contract-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.bp-contract-card-title  { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.bp-contract-card-meta   { font-size: 12px; color: var(--text-secondary); }
.bp-contract-card-badges { display: flex; gap: 6px; flex-shrink: 0; }
.bp-contract-escrow      { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px; }
.bp-contract-escrow-label { font-size: 11px; color: var(--text-tertiary); margin-bottom: 2px; }
.bp-contract-escrow-value { font-size: 14px; font-weight: 700; color: var(--text); }
.bp-contract-escrow-value.is-held     { color: var(--gold-dark); }
.bp-contract-escrow-value.is-released { color: var(--green-dark); }
.bp-escrow-bar  { height: 5px; background: var(--border); border-radius: 3px; display: flex; overflow: hidden; margin-bottom: 14px; }
.bp-escrow-bar-released { background: var(--green-dark); transition: width 0.3s; }
.bp-escrow-bar-held     { background: var(--gold-dark); transition: width 0.3s; }

/* ── Milestone list in contract card ── */
.bp-milestone-list  { border-top: 1px solid var(--border); padding-top: 10px; display: flex; flex-direction: column; gap: 2px; }
.bp-milestone-row   { display: flex; align-items: center; gap: 12px; padding: 6px 0; }
.bp-milestone-title  { flex: 1; font-size: 12px; color: var(--text); }
.bp-milestone-amount { font-size: 12px; font-weight: 600; color: var(--text); white-space: nowrap; }
.bp-milestone-auto   { display: flex; align-items: center; gap: 4px; font-size: 11px; color: var(--text-tertiary); white-space: nowrap; }

/* ── Payout table ── */
.payout-table      { border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
.payout-table-head { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; padding: 10px 16px; background: var(--surface-2); border-bottom: 1px solid var(--border); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-tertiary); }
.payout-table-row  { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; padding: 12px 16px; border-bottom: 1px solid var(--border); align-items: center; }
.payout-table-row:last-child { border-bottom: none; }
.payout-desc    { font-size: 13px; color: var(--text); }
.payout-amount  { font-size: 13px; font-weight: 700; color: var(--green-dark); }
.payout-date    { font-size: 12px; color: var(--text-secondary); }
.payout-status  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.payout-refunded { font-size: 11px; color: var(--red-dark, #dc2626); }

/* ── Ledger ── */
.ledger-list { display: flex; flex-direction: column; gap: 1px; border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
.ledger-row  { display: grid; grid-template-columns: 120px 1fr 120px 140px; align-items: center; gap: 12px; padding: 10px 16px; border-bottom: 1px solid var(--border); background: var(--surface); }
.ledger-row:last-child { border-bottom: none; }
.ledger-kind   { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; }
.ledger-kind--fund    { color: var(--gold-dark); }
.ledger-kind--release { color: var(--green-dark); }
.ledger-kind--refund, .ledger-kind--split_refund { color: var(--text-secondary); }
.ledger-desc   { font-size: 12px; color: var(--text-secondary); }
.ledger-amount { font-size: 13px; font-weight: 700; color: var(--text); text-align: right; }
.ledger-amount.is-positive { color: var(--green-dark); }
.ledger-date   { font-size: 11px; color: var(--text-tertiary); text-align: right; }
</style>
