<!--
  pages/business-partner/Milestones.vue — work milestones across contracts.

  Wave 1+4 complete:
  - btn-sm removed
  - Redundant global imports removed
  - All new status values labelled + coloured
  - Revision notes + rejection reason displayed inline
  - Auto-release countdown on submitted milestones
  - MilestoneSubmitModal for submit + resubmit (with notes + hours)
  - "Awaiting funding" lock state clearly shown
  - Urgent revision banner
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Work"
      title="Milestones"
      :subtitle="`${pendingCount} pending · ${submittedCount} under review · ${releasedCount} paid`"
    />

    <div class="stat-chips-row">
      <AegisStatChip
        icon="flag-2"
        :value="pendingCount"
        label="Pending"
        bg-color="var(--icon-bg-gold)"
        icon-color="var(--gold-dark)"
      />
      <AegisStatChip
        icon="send"
        :value="submittedCount"
        label="Under review"
        bg-color="var(--icon-bg-blue)"
        icon-color="var(--blue-dark)"
      />
      <AegisStatChip
        icon="check-circle"
        :value="releasedCount"
        label="Paid"
        bg-color="var(--icon-bg-green)"
        icon-color="var(--green-dark)"
      />
      <AegisStatChip
        icon="alert-triangle"
        :value="overdueCount"
        label="Overdue"
        bg-color="var(--icon-bg-red)"
        icon-color="var(--red-dark)"
      />
      <AegisStatChip
        icon="dollar"
        :value="pricing.formatCents(pendingValue)"
        label="Pending value"
      />
    </div>

    <!-- Revision needed urgent banner -->
    <div v-if="revisionNeeded.length" class="alert-banner alert-banner-warning">
      <AegisIcon name="alert-triangle" :size="16" />
      <span>
        <strong>{{ revisionNeeded.length }} milestone{{ revisionNeeded.length === 1 ? '' : 's' }}</strong>
        {{ revisionNeeded.length === 1 ? 'needs' : 'need' }} revision —
        review provider feedback and resubmit.
      </span>
    </div>

    <AegisEmptyState
      v-if="!milestones.length"
      icon="flag-2"
      title="No milestones yet"
      description="Milestones appear once you have an active contract with milestone-based payments."
    />

    <div v-else class="card-list">
      <AegisCard
        v-for="m in milestones"
        :key="m.id"
        class="milestone-card"
        :class="{
          'is-overdue':  isOverdue(m),
          'is-revision': m.status === 'revision_requested',
          'is-disputed': m.status === 'disputed',
        }"
      >
        <div class="milestone-card-body">
          <!-- Left: info -->
          <div class="milestone-card-main">
            <div class="milestone-card-header">
              <div class="milestone-card-title">{{ m.title }}</div>
              <AegisBadge :label="statusLabel(m.status)" :variant="statusVariant(m.status)" />
            </div>

            <div class="milestone-card-meta">
              <span>
                <AegisIcon name="agreement" :size="12" />
                {{ m.contract_title }}
              </span>
              <span>
                <AegisIcon name="dollar" :size="12" />
                {{ pricing.formatCents(m.amount_cents) }}
              </span>
              <span v-if="m.due_at" :class="{ 'is-overdue-text': isOverdue(m) }">
                <AegisIcon name="calendar" :size="12" />
                Due {{ m.due_at }}
              </span>
              <span v-if="['revision_requested'].includes(m.status) && m.revision_count">
                <AegisIcon name="refresh-cw" :size="12" />
                Revision #{{ m.revision_count }}
              </span>
            </div>

            <!-- Provider revision feedback -->
            <div v-if="m.status === 'revision_requested' && m.revision_notes" class="milestone-revision-notes">
              <AegisIcon name="message-circle" :size="12" />
              <em>{{ m.revision_notes }}</em>
            </div>

            <!-- Rejection reason -->
            <div v-if="['rejected', 'disputed'].includes(m.status) && m.rejection_reason" class="milestone-rejection-reason">
              <AegisIcon name="x-circle" :size="12" />
              {{ m.rejection_reason }}
            </div>

            <!-- Auto-release countdown on submitted -->
            <div v-if="m.status === 'submitted' && m.auto_release_at" class="milestone-auto-release">
              <AegisIcon name="hourglass" :size="12" />
              Auto-releases {{ formatAutoRelease(m.auto_release_at) }} if provider doesn't respond
            </div>

            <!-- Previous submission notes for context -->
            <div v-if="m.status === 'revision_requested' && m.latest_submission?.submission_notes" class="milestone-prev-submission">
              <span class="milestone-prev-label">Your last submission:</span>
              {{ truncate(m.latest_submission.submission_notes, 120) }}
            </div>
          </div>

          <!-- Right: actions -->
          <div class="milestone-card-actions">
            <!-- Rev 2: payment_failed notice -->
            <div v-if="m.status === 'payment_failed'" class="milestone-locked milestone-failed">
              <AegisIcon name="alert-triangle" :size="13" />
              <span>Payment failed — provider must retry</span>
            </div>

            <!-- Submit (pending / in_progress) -->
            <button
              v-else-if="canSubmit(m)"
              type="button"
              class="btn btn-primary"
              @click="openSubmit(m)"
            >
              <AegisIcon name="send" :size="13" />
              Submit work
            </button>

            <!-- Resubmit after revision request -->
            <button
              v-else-if="m.status === 'revision_requested'"
              type="button"
              class="btn btn-primary"
              @click="openSubmit(m)"
            >
              <AegisIcon name="refresh-cw" :size="13" />
              Resubmit
            </button>

            <!-- Under review — waiting -->
            <div v-else-if="m.status === 'submitted'" class="milestone-under-review">
              <AegisIcon name="clock" :size="13" />
              <span>Awaiting review</span>
            </div>

            <!-- Paid -->
            <div v-else-if="['released', 'paid'].includes(m.status)" class="milestone-paid">
              <AegisIcon name="check-circle" :size="13" />
              <span>{{ pricing.formatCents(m.released_cents || m.amount_cents) }} paid</span>
            </div>
          </div>
        </div>
      </AegisCard>
    </div>

    <!-- Submit modal -->
    <MilestoneSubmitModal
      :milestone="activeMilestone"
      @update:model-value="activeMilestone = null"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed }      from 'vue'
import AppLayout              from '@/layouts/AppLayout.vue'
import MilestoneSubmitModal   from '@/components/modals/MilestoneSubmitModal.vue'
import { usePricingStore }    from '@/stores/pricing'

const props   = defineProps({ milestones: { type: Array, default: () => [] } })
const pricing = usePricingStore()

const activeMilestone = ref(null)

// ── Computed ──────────────────────────────────────────────────────────────────
const pendingCount   = computed(() => props.milestones.filter((m) => ['pending', 'in_progress'].includes(m.status)).length)
const submittedCount = computed(() => props.milestones.filter((m) => ['submitted', 'approved'].includes(m.status)).length)
const releasedCount  = computed(() => props.milestones.filter((m) => ['released', 'paid'].includes(m.status)).length)
const overdueCount   = computed(() => props.milestones.filter((m) => isOverdue(m)).length)
const pendingValue   = computed(() =>
  props.milestones
    .filter((m) => ['pending', 'in_progress', 'prepaid'].includes(m.status))
    .reduce((s, m) => s + (m.amount_cents || 0), 0),
)
const revisionNeeded = computed(() => props.milestones.filter((m) => m.status === 'revision_requested'))

function canSubmit(m) {
  return ['pending', 'funded', 'in_progress'].includes(m.status)
}

function isOverdue(m) {
  return ['pending', 'funded', 'in_progress'].includes(m.status) &&
         m.due_at &&
         new Date(m.due_at) < Date.now()
}

// ── Status labels ─────────────────────────────────────────────────────────────
function statusLabel(s) {
  return {
    pending:            'Awaiting work',
    pending_funding:    'Awaiting work',
    funded:             'In progress',
    in_progress:        'In progress',
    submitted:          'Awaiting review',
    revision_requested: 'Revision requested',
    approved:           'Approved — payment fired',
    released:           'Paid',
    paid:               'Paid',
    prepaid:            'Pre-paid',
    payment_failed:     'Payment failed',
    disputed:           'Disputed',
    refunded:           'Refunded',
    rejected:           'Rejected',
    cancelled:          'Cancelled',
  }[s] ?? s
}

function statusVariant(s) {
  return {
    pending:            'neutral',
    pending_funding:    'neutral',
    funded:             'blue',
    in_progress:        'blue',
    submitted:          'warning',
    revision_requested: 'warning',
    approved:           'blue',
    released:           'success',
    paid:               'success',
    prepaid:            'success',
    payment_failed:     'danger',
    disputed:           'danger',
    refunded:           'neutral',
    rejected:           'danger',
    cancelled:          'neutral',
  }[s] ?? 'neutral'
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatAutoRelease(iso) {
  const diff  = new Date(iso) - Date.now()
  if (diff <= 0) return 'imminently'
  const days  = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  return days > 0 ? `in ${days}d ${hours}h` : `in ${hours}h`
}

function truncate(str, len) {
  if (!str) return ''
  return str.length <= len ? str : str.slice(0, len) + '…'
}

function openSubmit(m) {
  activeMilestone.value = m
}
</script>

<style scoped>
.milestone-card { transition: all var(--transition); }
.milestone-card.is-overdue  { border-color: var(--red-border, rgba(220, 38, 38, 0.4)); }
.milestone-card.is-revision { border-color: var(--gold-dark); }
.milestone-card.is-disputed { border-color: var(--red-dark); }

.milestone-card-body { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.milestone-card-main { flex: 1; min-width: 0; }
.milestone-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 8px; }
.milestone-card-title  { font-size: 14px; font-weight: 700; color: var(--text); }
.milestone-card-meta   { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; font-size: 12px; color: var(--text-secondary); margin-bottom: 6px; }
.milestone-card-meta span { display: inline-flex; align-items: center; gap: 4px; }
.is-overdue-text { color: var(--red-dark); font-weight: 600; }

.milestone-revision-notes,
.milestone-rejection-reason,
.milestone-auto-release,
.milestone-prev-submission {
  display: flex; align-items: flex-start; gap: 6px;
  font-size: 12px; margin-top: 4px; padding: 6px 10px;
  border-radius: var(--radius); border: 1px solid;
}
.milestone-revision-notes    { background: rgba(202,158,72,0.06); border-color: var(--gold-dark); color: var(--text-secondary); font-style: italic; }
.milestone-rejection-reason  { background: rgba(220,38,38,0.06);  border-color: var(--red-dark); color: var(--red-dark); }
.milestone-auto-release      { background: var(--surface-2); border-color: var(--border); color: var(--text-tertiary); }
.milestone-prev-submission   { background: var(--surface-2); border-color: var(--border); color: var(--text-secondary); font-size: 11px; }
.milestone-prev-label        { font-weight: 700; margin-right: 4px; white-space: nowrap; }

.milestone-card-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }

.milestone-locked,
.milestone-under-review,
.milestone-paid {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--text-secondary);
  white-space: nowrap;
}
.milestone-paid { color: var(--green-dark); font-weight: 600; }
.milestone-locked { color: var(--text-tertiary); }
</style>
