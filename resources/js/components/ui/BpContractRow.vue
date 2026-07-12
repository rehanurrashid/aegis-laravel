<!--
  BpContractRow.vue — single contract row in the BpFinanceTable Contracts sub-tab.
  Expands to show milestone accordion for milestone-based contracts.

  Props:
    contract   — full contract object from FinancesController (activeContracts array)
    hasPaymentMethod — bool: provider has saved PM

  Emits:
    sign(contract)
    fund-contract(contract)
    cancel(contract)
    autopay(contract)
    view(contract)
    pdf(contract)
    fund-milestone(contract, milestone)
    refund-milestone(contract, milestone)
    review-milestone(contract, milestone)
    dispute-milestone(contract, milestone)
-->
<template>
  <div class="bpcr-wrap" :class="`bpcr--${sv(contract.status)}`">

    <!-- ── Contract header row ── -->
    <div class="bpcr-row" @click="toggleExpand">
      <!-- Party -->
      <div class="bpcr-cell bpcr-cell--party">
        <div class="bpcr-avatar">{{ initials(contract.bp_name) }}</div>
        <div class="bpcr-party-info">
          <div class="bpcr-party-name">{{ contract.bp_name }}</div>
          <div class="bpcr-party-sub">
            <span
              class="connect-dot"
              :class="contract.bp_connected ? 'is-connected' : 'is-not-connected'"
              :data-tooltip="contract.bp_connected ? 'Stripe Connected' : 'Not connected to Stripe'"
            ></span>
            {{ contract.bp_connected ? 'Connected' : 'Not connected' }}
          </div>
        </div>
      </div>

      <!-- Service / contract title -->
      <div class="bpcr-cell bpcr-cell--service">
        <div class="bpcr-service-title">{{ contract.title }}</div>
        <div class="bpcr-service-sub">{{ contract.billing_type_label }} · {{ contract.term }}</div>
      </div>

      <!-- Amount + escrow bar -->
      <div class="bpcr-cell bpcr-cell--amount">
        <div class="bpcr-amount">{{ formatCents(contract.total_cents) }}</div>
        <!-- Escrow mini bar: only for active milestone contracts -->
        <template v-if="sv(contract.status) === 'active' && isMilestoneDriven && contract.total_cents > 0">
          <div class="bpcr-escrow-bar-wrap">
            <div class="bpcr-escrow-bar">
              <div class="bpcr-bar-released" :style="{ width: escrowPct(contract.escrow_released_cents ?? 0) }" />
              <div class="bpcr-bar-held"     :style="{ width: escrowPct(contract.escrow_held_cents ?? 0) }" />
            </div>
          </div>
          <div class="bpcr-escrow-labels">
            <span class="bpcr-escrow-held">
              <AegisIcon name="shield-check" :size="10" />
              {{ formatCents(contract.escrow_held_cents ?? 0) }} held
            </span>
            <template v-if="(contract.unfunded_cents ?? 0) > 0">
              <span class="bpcr-escrow-unfunded">
                · {{ formatCents(contract.unfunded_cents) }} unfunded
              </span>
            </template>
          </div>
        </template>
        <!-- Unfunded warning for pending_funding -->
        <div v-if="sv(contract.status) === 'pending_funding'" class="bpcr-fund-hint">
          <AegisIcon name="alert-circle" :size="11" />
          Escrow not funded
        </div>
      </div>

      <!-- Status -->
      <div class="bpcr-cell bpcr-cell--status">
        <AegisBadge :label="contractStatusLabel(contract.status)" :variant="contractStatusVariant(contract.status)" />
        <!-- Pending signature: show who has/hasn't signed -->
        <div v-if="sv(contract.status) === 'pending_signature'" class="bpcr-sig-status">
          <span :class="['bpcr-sig-chip', contract.provider_has_signed ? 'signed' : '']">
            <AegisIcon :name="contract.provider_has_signed ? 'check-circle' : 'circle'" :size="10" />
            You
          </span>
          <span :class="['bpcr-sig-chip', contract.bp_has_signed ? 'signed' : '']">
            <AegisIcon :name="contract.bp_has_signed ? 'check-circle' : 'circle'" :size="10" />
            BP
          </span>
        </div>
        <!-- Milestone needing review badge -->
        <span v-if="submittedMilestoneCount > 0" class="bpcr-review-badge">
          {{ submittedMilestoneCount }} needs review
        </span>
      </div>

      <!-- Last paid / term -->
      <div class="bpcr-cell bpcr-cell--term">
        <template v-if="contract.last_paid">
          <div class="bpcr-term-label">Last paid</div>
          <div class="bpcr-term-val">{{ contract.last_paid }}</div>
        </template>
        <template v-else>
          <div class="bpcr-term-label">Started</div>
          <div class="bpcr-term-val">{{ contract.term.split('–')[0].trim() }}</div>
        </template>
      </div>

      <!-- Actions -->
      <div class="bpcr-cell bpcr-cell--actions" @click.stop>

        <!-- Sign CTA — pending_signature, provider hasn't signed yet -->
        <button
          v-if="sv(contract.status) === 'pending_signature' && !contract.provider_has_signed"
          type="button"
          class="btn btn-primary bpcr-primary-btn"
          @click="$emit('sign', contract)"
        >
          <AegisIcon name="file-pen" :size="13" />
          Sign
        </button>

        <!-- Fund Escrow CTA — pending_funding or active with unfunded milestones -->
        <button
          v-else-if="sv(contract.status) === 'pending_funding' || (sv(contract.status) === 'active' && (contract.unfunded_cents ?? 0) > 0 && !isMilestoneDriven)"
          type="button"
          class="btn btn-primary bpcr-primary-btn"
          :disabled="!hasPaymentMethod"
          :data-tooltip="!hasPaymentMethod ? 'Add a payment method first' : 'Fund escrow to activate this contract'"
          @click="$emit('fund-contract', contract)"
        >
          <AegisIcon name="dollar" :size="13" />
          Fund Escrow
        </button>

        <!-- icon buttons: view, pdf, autopay, cancel -->
        <button type="button" class="btn-icon" data-tooltip="View contract details" @click="$emit('view', contract)">
          <AegisIcon name="file-text" :size="14" />
        </button>

        <a
          v-if="contract.id"
          :href="route('provider.jobs.contract.pdf', contract.id)"
          target="_blank"
          rel="noopener"
          class="btn-icon"
          data-tooltip="Download PDF"
          @click.stop
        >
          <AegisIcon name="download" :size="14" />
        </a>

        <button
          v-if="sv(contract.status) === 'active'"
          type="button"
          class="btn-icon"
          data-tooltip="Auto-pay settings"
          @click="$emit('autopay', contract)"
        >
          <AegisIcon name="settings" :size="14" />
        </button>

        <button
          v-if="['pending_signature','pending_funding','active'].includes(sv(contract.status))"
          type="button"
          class="btn-icon btn-icon-danger"
          data-tooltip="Cancel contract"
          @click="$emit('cancel', contract)"
        >
          <AegisIcon name="x" :size="14" />
        </button>

        <!-- Expand toggle (milestone contracts) -->
        <button
          v-if="isMilestoneDriven"
          type="button"
          class="btn-icon bpcr-expand-btn"
          :data-tooltip="expanded ? 'Collapse milestones' : 'Expand milestones'"
          @click="toggleExpand"
        >
          <AegisIcon :name="expanded ? 'chevron-up' : 'chevron-down'" :size="14" />
        </button>
      </div>
    </div>

    <!-- ── Milestone accordion ── -->
    <div v-if="isMilestoneDriven && expanded" class="bpcr-milestones">
      <!-- Milestone rows -->
      <BpMilestoneRow
        v-for="ms in contract.milestones"
        :key="ms.id"
        :milestone="ms"
        :contract="contract"
        :is-active="sv(contract.status) === 'active'"
        :has-payment-method="hasPaymentMethod"
        @fund="$emit('fund-milestone', contract, $event)"
        @refund="$emit('refund-milestone', contract, $event)"
        @review="$emit('review-milestone', contract, $event)"
        @dispute="$emit('dispute-milestone', contract, $event)"
      />

      <!-- Milestone total + add-milestone CTA -->
      <div class="bpcr-ms-footer">
        <span class="bpcr-ms-total">
          {{ contract.milestones?.length ?? 0 }} milestones ·
          {{ formatCents(milestoneTotalCents) }} total
        </span>
        <!-- Only allow adding milestones on active contracts with per_milestone funding -->
        <a
          v-if="sv(contract.status) === 'active'"
          :href="route('provider.jobs.index')"
          class="bpcr-ms-manage-link"
          data-tooltip="Manage milestones in Support Services"
        >
          <AegisIcon name="external-link" :size="11" />
          Manage in Support Services
        </a>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'
import BpMilestoneRow from '@/components/ui/BpMilestoneRow.vue'

const props = defineProps({
  contract:         { type: Object,  required: true },
  hasPaymentMethod: { type: Boolean, default: false },
})

defineEmits([
  'sign', 'fund-contract', 'cancel', 'autopay', 'view', 'pdf',
  'fund-milestone', 'refund-milestone', 'review-milestone', 'dispute-milestone',
])

const expanded = ref(false)
function toggleExpand() {
  if (isMilestoneDriven.value) expanded.value = !expanded.value
}

const sv = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

const isMilestoneDriven = computed(() =>
  props.contract.billing_type === 'milestone' || (props.contract.milestones?.length ?? 0) > 0
)

const submittedMilestoneCount = computed(() =>
  (props.contract.milestones ?? []).filter(m => sv(m.status) === 'submitted').length
)

const milestoneTotalCents = computed(() =>
  (props.contract.milestones ?? []).reduce((sum, m) => sum + (m.amount_cents ?? 0), 0)
)

function escrowPct(cents) {
  const total = props.contract.total_cents ?? 0
  if (!total) return '0%'
  return Math.min(100, Math.round((cents / total) * 100)) + '%'
}

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

/* Status accent stripe */
.bpcr--pending_signature { border-left: 3px solid var(--gold); }
.bpcr--pending_funding   { border-left: 3px solid var(--blue, #3b82f6); }
.bpcr--active            { border-left: 3px solid var(--green); }
.bpcr--disputed          { border-left: 3px solid var(--red); }
.bpcr--completed,
.bpcr--cancelled         { border-left: 3px solid var(--border-dark); opacity: 0.8; }

/* ── Row grid ── */
.bpcr-row {
  display: grid;
  grid-template-columns: 2fr 2fr 1.6fr 1.4fr 1fr 160px;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  cursor: pointer;
  transition: background var(--transition);
}
.bpcr-row:hover { background: var(--surface-2); }

/* ── Cells ── */
.bpcr-cell { min-width: 0; }
.bpcr-cell--actions {
  display: flex;
  align-items: center;
  gap: 4px;
  justify-content: flex-end;
  flex-wrap: wrap;
}

/* Party cell */
.bpcr-avatar {
  width: 34px;
  height: 34px;
  border-radius: var(--radius-sm);
  background: var(--gold-dark);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-sans);
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
}
.bpcr-cell--party {
  display: flex;
  align-items: center;
  gap: 10px;
}
.bpcr-party-info { min-width: 0; }
.bpcr-party-name {
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.bpcr-party-sub {
  display: flex;
  align-items: center;
  gap: 5px;
  font-family: var(--font-sans);
  font-size: 11px;
  color: var(--text-4);
  margin-top: 2px;
}

/* Connect dot */
.connect-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}
.connect-dot.is-connected     { background: var(--green); }
.connect-dot.is-not-connected { background: var(--text-4); }

/* Service cell */
.bpcr-service-title {
  font-family: var(--font-sans);
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.bpcr-service-sub {
  font-family: var(--font-sans);
  font-size: 11px;
  color: var(--text-4);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Amount cell */
.bpcr-amount {
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 4px;
}
.bpcr-escrow-bar-wrap { margin-bottom: 4px; }
.bpcr-escrow-bar {
  height: 4px;
  border-radius: 2px;
  background: var(--surface-3);
  overflow: hidden;
  display: flex;
}
.bpcr-bar-released { background: var(--green); height: 100%; }
.bpcr-bar-held     { background: var(--blue, #3b82f6); height: 100%; }
.bpcr-escrow-labels {
  display: flex;
  align-items: center;
  gap: 4px;
  font-family: var(--font-sans);
  font-size: 10px;
  color: var(--text-4);
  flex-wrap: wrap;
}
.bpcr-escrow-held { color: var(--blue-dark, #1d4ed8); font-weight: 600; display: flex; align-items: center; gap: 3px; }
.bpcr-escrow-unfunded { color: var(--text-4); }
.bpcr-fund-hint {
  display: flex;
  align-items: center;
  gap: 4px;
  font-family: var(--font-sans);
  font-size: 10.5px;
  color: var(--gold-dark);
  font-weight: 600;
  margin-top: 4px;
}

/* Status cell */
.bpcr-sig-status {
  display: flex;
  gap: 5px;
  margin-top: 5px;
  flex-wrap: wrap;
}
.bpcr-sig-chip {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-family: var(--font-sans);
  font-size: 10px;
  font-weight: 600;
  color: var(--text-4);
  padding: 1px 6px;
  border-radius: var(--radius-full);
  border: 1px solid var(--border-dark);
  background: var(--surface-2);
}
.bpcr-sig-chip.signed {
  color: var(--green-dark, #15803d);
  border-color: var(--green);
  background: rgba(34,197,94,0.06);
}
.bpcr-review-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-family: var(--font-sans);
  font-size: 10px;
  font-weight: 700;
  color: var(--gold-dark);
  background: rgba(160,129,62,0.10);
  border: 1px solid var(--gold);
  border-radius: var(--radius-full);
  padding: 2px 7px;
  margin-top: 4px;
}

/* Term cell */
.bpcr-term-label { font-family: var(--font-sans); font-size: 10px; color: var(--text-4); text-transform: uppercase; letter-spacing: 0.4px; }
.bpcr-term-val   { font-family: var(--font-sans); font-size: 12px; font-weight: 600; color: var(--text-2); margin-top: 2px; }

/* Buttons */
.bpcr-primary-btn { font-size: 12px; padding: 5px 12px; height: 30px; }
.bpcr-expand-btn  { color: var(--text-3); }

/* ── Milestone accordion ── */
.bpcr-milestones {
  border-top: 1px solid var(--border);
}
.bpcr-ms-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px 8px 32px;
  background: var(--surface-2);
  border-top: 1px solid var(--border);
}
.bpcr-ms-total {
  font-family: var(--font-sans);
  font-size: 11px;
  font-weight: 600;
  color: var(--text-3);
}
.bpcr-ms-manage-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-family: var(--font-sans);
  font-size: 11px;
  font-weight: 600;
  color: var(--gold-dark);
  text-decoration: none;
}
.bpcr-ms-manage-link:hover { text-decoration: underline; }
</style>
