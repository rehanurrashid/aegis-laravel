<!--
  BpContractRow.vue — sic-row pattern for contract rows.
  3 columns: Party+contract info | Status+badges | Expand chevron
  All detail + milestone accordion + actions live in an inline AegisModal.
-->
<template>
  <div class="bpcr-wrap" :class="`bpcr--${sv(contract.status)}`">

    <!-- ── ROW (mirrors sic-row layout) ── -->
    <div class="bpcr-row">

      <!-- Col 1: Avatar + name + contract title + billing type -->
      <div class="bpcr-td bpcr-td--party" @click="open = true">
        <div class="bpcr-party">
          <div class="bpcr-avatar">{{ initials(contract.bp_name) }}</div>
          <div class="bpcr-party-info">
            <a
              v-if="contract.bp_slug"
              :href="`/public/bp/${contract.bp_slug}`"
              class="bpcr-party-name bpcr-party-name--link"
              @click.stop
            >{{ contract.bp_name }}</a>
            <span v-else class="bpcr-party-name">{{ contract.bp_name }}</span>
            <span class="bpcr-service-name">{{ contract.title }}</span>
            <span class="bpcr-date-sub">{{ contract.billing_type_label }} · {{ contract.term }}</span>
          </div>
        </div>
      </div>

      <!-- Col 2: Status + review badge + amount summary -->
      <div class="bpcr-td bpcr-td--status" @click="open = true">
        <div class="bpcr-badges">
          <AegisBadge :label="contractStatusLabel(contract.status)" :variant="contractStatusVariant(contract.status)" />
          <span v-if="submittedMilestoneCount > 0" class="bpcr-review-badge">
            {{ submittedMilestoneCount }} needs review
          </span>
        </div>
        <div class="bpcr-amount-sub">{{ formatCents(contract.total_cents) }}</div>
        <!-- Escrow line -->
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
      <div class="bpcr-td bpcr-td--actions" @click="open = true">
        <button type="button" class="btn-icon" data-tooltip="View details & actions">
          <AegisIcon name="chevron-right" :size="15" />
        </button>
      </div>
    </div>

    <!-- ── DETAIL MODAL ── -->
    <AegisModal
      v-model="open"
      :title="contract.title || 'Contract Details'"
      size="lg"
    >
      <template #default>

        <!-- Party header -->
        <div class="bpcr-modal-party">
          <div class="bpcr-avatar bpcr-avatar--lg">{{ initials(contract.bp_name) }}</div>
          <div class="bpcr-modal-party-info">
            <a
              v-if="contract.bp_slug"
              :href="`/public/bp/${contract.bp_slug}`"
              class="bpcr-modal-party-name"
              target="_blank"
            >{{ contract.bp_name }}</a>
            <span v-else class="bpcr-modal-party-name">{{ contract.bp_name }}</span>
            <span class="bpcr-modal-service">{{ contract.billing_type_label }}</span>
          </div>
          <div class="bpcr-modal-badges">
            <AegisBadge :label="contractStatusLabel(contract.status)" :variant="contractStatusVariant(contract.status)" />
            <span
              class="bpcr-connect-pill"
              :class="contract.bp_connected ? 'is-connected' : 'is-not-connected'"
            >
              <span class="bpcr-connect-dot"></span>
              {{ contract.bp_connected ? 'Stripe Connected' : 'Not Connected' }}
            </span>
          </div>
        </div>

        <!-- Meta row -->
        <div class="bpcr-modal-meta">
          <span class="bpcr-modal-meta-item">
            <AegisIcon name="calendar" :size="12" /> {{ contract.term }}
          </span>
          <span v-if="contract.last_paid" class="bpcr-modal-meta-item">
            <AegisIcon name="check-circle" :size="12" /> Last paid {{ contract.last_paid }}
          </span>
        </div>

        <!-- Pending signature chips -->
        <div v-if="sv(contract.status) === 'pending_signature'" class="bpcr-modal-sig-row">
          <span :class="['bpcr-sig-chip', contract.provider_has_signed ? 'signed' : '']">
            <AegisIcon :name="contract.provider_has_signed ? 'check-circle' : 'circle'" :size="10" />
            You
          </span>
          <span :class="['bpcr-sig-chip', contract.bp_has_signed ? 'signed' : '']">
            <AegisIcon :name="contract.bp_has_signed ? 'check-circle' : 'circle'" :size="10" />
            BP
          </span>
        </div>

        <!-- Amount + escrow breakdown -->
        <div class="bpcr-modal-amounts">
          <div class="bpcr-modal-amounts-head">
            <span>Contract Value</span>
            <span class="bpcr-modal-total">{{ formatCents(contract.total_cents) }}</span>
          </div>
          <template v-if="isMilestoneDriven">
            <div class="bpcr-modal-amount-row">
              <span class="bpcr-modal-amount-label">
                <AegisIcon name="shield-check" :size="12" /> Held in Escrow
              </span>
              <span class="bpcr-modal-amount-val">{{ formatCents(contract.escrow_held_cents ?? 0) }}</span>
            </div>
            <div class="bpcr-modal-amount-row">
              <span class="bpcr-modal-amount-label">
                <AegisIcon name="check" :size="12" /> Released to BP
              </span>
              <span class="bpcr-modal-amount-val bpcr-modal-amount-val--paid">{{ formatCents(contract.escrow_released_cents ?? 0) }}</span>
            </div>
            <div v-if="(contract.unfunded_cents ?? 0) > 0" class="bpcr-modal-amount-row">
              <span class="bpcr-modal-amount-label">
                <AegisIcon name="alert-circle" :size="12" /> Unfunded
              </span>
              <span class="bpcr-modal-amount-val bpcr-modal-amount-val--warn">{{ formatCents(contract.unfunded_cents) }}</span>
            </div>
          </template>
        </div>

        <!-- Escrow progress bar -->
        <template v-if="isMilestoneDriven && contract.total_cents > 0">
          <div class="bpcr-modal-bar-wrap">
            <div class="bpcr-modal-bar">
              <div class="bpcr-bar-released" :style="{ width: escrowPct(contract.escrow_released_cents ?? 0) }" />
              <div class="bpcr-bar-held"     :style="{ width: escrowPct(contract.escrow_held_cents ?? 0) }" />
            </div>
            <div class="bpcr-modal-bar-labels">
              <span style="color:var(--green)">Released</span>
              <span style="color:var(--blue, #3b82f6)">Held</span>
              <span style="color:var(--border-dark)">Unfunded</span>
            </div>
          </div>
        </template>

        <!-- Milestone list -->
        <template v-if="isMilestoneDriven && contract.milestones?.length">
          <div class="bpcr-modal-milestones-head">
            <AegisIcon name="layers" :size="12" />
            Milestones ({{ contract.milestones.length }})
          </div>
          <div class="bpcr-modal-milestones">
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
          </div>
          <div class="bpcr-modal-ms-footer">
            <a
              v-if="sv(contract.status) === 'active'"
              :href="route('provider.jobs.index')"
              class="bpcr-ms-manage-link"
            >
              <AegisIcon name="external-link" :size="11" />
              Manage milestones in Support Services
            </a>
          </div>
        </template>

      </template>

      <template #footer>
        <button type="button" class="btn btn-outline" @click="open = false">Close</button>

        <!-- Download PDF -->
        <a
          v-if="contract.id"
          :href="route('provider.jobs.contract.pdf', contract.id)"
          target="_blank"
          rel="noopener"
          class="btn btn-ghost"
        >
          <AegisIcon name="download" :size="13" /> PDF
        </a>

        <!-- Sign -->
        <button
          v-if="sv(contract.status) === 'pending_signature' && !contract.provider_has_signed"
          type="button"
          class="btn btn-primary"
          @click="$emit('sign', contract); open = false"
        >
          <AegisIcon name="file-pen" :size="13" /> Sign Contract
        </button>

        <!-- Fund Escrow -->
        <button
          v-if="sv(contract.status) === 'pending_funding' || (sv(contract.status) === 'active' && (contract.unfunded_cents ?? 0) > 0 && !isMilestoneDriven)"
          type="button"
          class="btn btn-primary"
          :disabled="!hasPaymentMethod"
          @click="$emit('fund-contract', contract); open = false"
        >
          <AegisIcon name="dollar" :size="13" /> Fund Escrow
        </button>

        <!-- Auto-pay -->
        <button
          v-if="sv(contract.status) === 'active'"
          type="button"
          class="btn btn-outline"
          @click="$emit('autopay', contract); open = false"
        >
          <AegisIcon name="settings" :size="13" /> Auto-pay
        </button>

        <!-- Cancel -->
        <button
          v-if="['pending_signature','pending_funding','active'].includes(sv(contract.status))"
          type="button"
          class="btn btn-danger"
          @click="$emit('cancel', contract); open = false"
        >
          <AegisIcon name="x" :size="13" /> Cancel
        </button>
      </template>
    </AegisModal>

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

const open = ref(false)

const sv = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

const isMilestoneDriven = computed(() =>
  props.contract.billing_type === 'milestone' || (props.contract.milestones?.length ?? 0) > 0
)

const submittedMilestoneCount = computed(() =>
  (props.contract.milestones ?? []).filter(m => sv(m.status) === 'submitted').length
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

/* Status accent */
.bpcr--pending_signature { border-left: 3px solid var(--gold); }
.bpcr--pending_funding   { border-left: 3px solid var(--blue, #3b82f6); }
.bpcr--active            { border-left: 3px solid var(--green); }
.bpcr--disputed          { border-left: 3px solid var(--red); }
.bpcr--completed,
.bpcr--cancelled         { border-left: 3px solid var(--border-dark); opacity: 0.8; }

/* ── Row — mirrors sic-row 3-col layout ── */
.bpcr-row {
  display: flex;
  align-items: stretch;
  cursor: pointer;
  transition: background var(--transition);
}
.bpcr-row:hover { background: var(--surface-2); }

.bpcr-td { padding: 10px 12px; vertical-align: middle; font-family: var(--font-sans); }
.bpcr-td--party   { flex: 0 0 58%; min-width: 0; }
.bpcr-td--status  { flex: 1; min-width: 0; }
.bpcr-td--actions { flex: 0 0 8%; display: flex; align-items: center; justify-content: flex-end; }

/* Party */
.bpcr-party { display: flex; align-items: center; gap: 9px; }
.bpcr-avatar {
  width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
  background: var(--badge-bg-gold); color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700;
}
.bpcr-avatar--lg { width: 40px; height: 40px; font-size: 13px; }
.bpcr-party-info { min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.bpcr-party-name { font-size: 13px; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bpcr-party-name--link { color: var(--gold-dark); text-decoration: none; }
.bpcr-party-name--link:hover { text-decoration: underline; color: var(--gold); }
.bpcr-service-name { font-size: 11px; color: var(--text-4); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bpcr-date-sub    { font-size: 11px; color: var(--text-4); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Status col */
.bpcr-badges { display: flex; flex-wrap: wrap; gap: 4px; align-items: center; margin-bottom: 3px; }
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

/* ── MODAL ── */
.bpcr-modal-party {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px; margin-bottom: 14px;
  background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius);
}
.bpcr-modal-party-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.bpcr-modal-party-name { font-size: 15px; font-weight: 700; color: var(--text); text-decoration: none; }
a.bpcr-modal-party-name:hover { color: var(--gold-dark); text-decoration: underline; }
.bpcr-modal-service    { font-size: 12px; color: var(--text-3); font-weight: 600; }
.bpcr-modal-badges     { display: flex; flex-direction: column; gap: 5px; align-items: flex-end; }

.bpcr-connect-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 100px; border: 1px solid var(--border); background: var(--surface-2); white-space: nowrap; }
.bpcr-connect-pill.is-connected     { color: var(--green-dark, #2e7d32); border-color: var(--green); background: rgba(34,197,94,.07); }
.bpcr-connect-pill.is-not-connected { color: var(--gold-dark); border-color: var(--gold); }
.bpcr-connect-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }

.bpcr-modal-meta { display: flex; flex-wrap: wrap; gap: 8px 14px; align-items: center; margin-bottom: 14px; }
.bpcr-modal-meta-item { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: var(--text-3); }

.bpcr-modal-sig-row { display: flex; gap: 6px; margin-bottom: 14px; }
.bpcr-sig-chip {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: 10px; font-weight: 600; color: var(--text-4);
  padding: 2px 8px; border-radius: var(--radius-full);
  border: 1px solid var(--border-dark); background: var(--surface-2);
}
.bpcr-sig-chip.signed { color: var(--green-dark, #15803d); border-color: var(--green); background: rgba(34,197,94,0.06); }

.bpcr-modal-amounts { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 12px; }
.bpcr-modal-amounts-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 8px 14px; background: var(--surface-3); border-bottom: 1px solid var(--border);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--text-4);
}
.bpcr-modal-total { font-size: 16px; font-weight: 700; color: var(--text); font-family: var(--font-serif, serif); text-transform: none; letter-spacing: 0; }
.bpcr-modal-amount-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 14px; border-top: 1px solid var(--border); }
.bpcr-modal-amount-label { display: flex; align-items: center; gap: 5px; font-weight: 600; color: var(--text-3); font-size: 12px; }
.bpcr-modal-amount-val { font-weight: 700; color: var(--text); font-family: var(--font-serif, serif); }
.bpcr-modal-amount-val--paid { color: var(--green); }
.bpcr-modal-amount-val--warn { color: var(--gold-dark); }

.bpcr-modal-bar-wrap { margin-bottom: 14px; }
.bpcr-modal-bar { height: 6px; border-radius: 3px; background: var(--surface-3); overflow: hidden; display: flex; margin-bottom: 4px; }
.bpcr-bar-released { background: var(--green); height: 100%; }
.bpcr-bar-held     { background: var(--blue, #3b82f6); height: 100%; }
.bpcr-modal-bar-labels { display: flex; gap: 12px; font-size: 10px; font-weight: 600; }

.bpcr-modal-milestones-head {
  display: flex; align-items: center; gap: 5px;
  font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
  color: var(--text-4); margin-bottom: 6px;
}
.bpcr-modal-milestones {
  border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 8px;
}
.bpcr-modal-ms-footer { margin-bottom: 4px; }
.bpcr-ms-manage-link {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 600; color: var(--gold-dark); text-decoration: none;
}
.bpcr-ms-manage-link:hover { text-decoration: underline; }
</style>
