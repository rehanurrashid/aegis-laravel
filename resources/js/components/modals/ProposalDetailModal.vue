<!--
  ProposalDetailModal.vue — proposal detail view for the BP portal.

  Replaces the broken bp.proposals.show route link in Proposals.vue.
  Shows: job details, bid, status timeline, cover letter preview, decline reason.
  Withdraw action delegates back to parent via emit.

  Props:
    proposal  Object | null   — the selected proposal row from ProposalService::getForBp
-->
<template>
  <AegisModal
    :model-value="!!proposal"
    title="Proposal details"
    size="lg"
    @update:model-value="onClose"
  >
    <div v-if="proposal" class="proposal-detail">
      <!-- Job + status header -->
      <div class="proposal-detail-header">
        <div>
          <div class="proposal-detail-eyebrow">Proposal for</div>
          <div class="proposal-detail-job-title">{{ proposal.job_title }}</div>
          <div class="proposal-detail-client">{{ proposal.client_name }}</div>
        </div>
        <AegisBadge :label="statusLabel(proposal.status)" :variant="statusVariant(proposal.status)" />
      </div>

      <!-- Status timeline -->
      <div class="proposal-timeline">
        <div
          v-for="step in timeline"
          :key="step.status"
          class="proposal-timeline-step"
          :class="{
            'is-done':    stepDone(step.status),
            'is-current': stepCurrent(step.status),
            'is-skipped': stepSkipped(step.status),
          }"
        >
          <div class="proposal-timeline-dot">
            <AegisIcon v-if="stepDone(step.status)" name="check" :size="10" />
            <AegisIcon v-else-if="stepSkipped(step.status)" name="x" :size="10" />
          </div>
          <div class="proposal-timeline-label">{{ step.label }}</div>
        </div>
      </div>

      <!-- Key details grid -->
      <div class="proposal-detail-grid">
        <div class="proposal-detail-item">
          <div class="proposal-detail-label">Your bid</div>
          <div class="proposal-detail-value">{{ pricing.formatCents(proposal.bid_cents) }}</div>
        </div>
        <div class="proposal-detail-item">
          <div class="proposal-detail-label">Timeline</div>
          <div class="proposal-detail-value">{{ timelineDays(proposal.timeline_days) }}</div>
        </div>
        <div class="proposal-detail-item">
          <div class="proposal-detail-label">Submitted</div>
          <div class="proposal-detail-value">{{ proposal.created_at ?? '—' }}</div>
        </div>
        <div v-if="proposal.responded_at" class="proposal-detail-item">
          <div class="proposal-detail-label">Responded</div>
          <div class="proposal-detail-value">{{ proposal.responded_at }}</div>
        </div>
      </div>

      <!-- Decline reason -->
      <div v-if="proposal.status === 'declined' && proposal.decline_reason" class="proposal-decline-reason">
        <AegisIcon name="message-circle" :size="14" />
        <div>
          <div class="proposal-decline-label">Reason for decline</div>
          <div class="proposal-decline-text">{{ proposal.decline_reason }}</div>
        </div>
      </div>

      <!-- Cover letter -->
      <div class="form-group">
        <div class="proposal-detail-label">Cover letter</div>
        <div class="proposal-cover-letter">{{ proposal.cover_letter }}</div>
      </div>

      <!-- Pipeline stage info (when under review) -->
      <div
        v-if="['under_review', 'shortlisted', 'interview'].includes(proposal.pipeline_stage)"
        class="proposal-stage-notice"
      >
        <AegisIcon name="star" :size="14" />
        <span v-if="proposal.pipeline_stage === 'shortlisted'">
          You have been shortlisted for this role.
        </span>
        <span v-else-if="proposal.pipeline_stage === 'interview'">
          An interview has been scheduled.
          <span v-if="proposal.interview_at"> · {{ proposal.interview_at }}</span>
        </span>
        <span v-else>Your proposal is under review.</span>
      </div>
    </div>

    <template #footer>
      <button
        v-if="proposal && proposal.status === 'pending'"
        type="button"
        class="btn btn-ghost btn-danger-ghost"
        @click="$emit('withdraw', proposal)"
      >
        Withdraw proposal
      </button>
      <button type="button" class="btn btn-outline" @click="onClose">
        Close
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  proposal: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'withdraw'])

const pricing = usePricingStore()

function onClose() {
  emit('update:modelValue', null)
}

// Status timeline steps in order
const timeline = [
  { status: 'pending',     label: 'Submitted' },
  { status: 'under_review', label: 'Under Review' },
  { status: 'shortlisted', label: 'Shortlisted' },
  { status: 'interview',   label: 'Interview' },
  { status: 'accepted',    label: 'Hired' },
]

const terminalStatuses = ['declined', 'withdrawn']

const statusOrder = ['pending', 'under_review', 'shortlisted', 'interview', 'accepted']

function currentIndex() {
  if (!props.proposal) return -1
  return statusOrder.indexOf(props.proposal.status)
}

function stepDone(stepStatus) {
  if (!props.proposal) return false
  if (terminalStatuses.includes(props.proposal.status)) return false
  return statusOrder.indexOf(stepStatus) < currentIndex()
}

function stepCurrent(stepStatus) {
  return props.proposal?.status === stepStatus
}

function stepSkipped(stepStatus) {
  // If proposal was declined or withdrawn, mark later-stage steps as skipped
  if (!props.proposal) return false
  if (!terminalStatuses.includes(props.proposal.status)) return false
  return statusOrder.indexOf(stepStatus) >= 0
}

function statusLabel(s) {
  return {
    pending:     'Pending',
    under_review:'Under Review',
    shortlisted: 'Shortlisted',
    interview:   'Interview',
    accepted:    'Accepted',
    declined:    'Declined',
    withdrawn:   'Withdrawn',
  }[s] ?? s
}

function statusVariant(s) {
  return {
    pending:     'gold',
    under_review:'blue',
    shortlisted: 'blue',
    interview:   'blue',
    accepted:    'green',
    declined:    'red',
    withdrawn:   'neutral',
  }[s] ?? 'neutral'
}

function timelineDays(days) {
  if (!days) return '—'
  if (days < 14) return 'Less than 1 week'
  if (days < 30) return '1–2 weeks'
  if (days < 60) return '3–4 weeks'
  if (days < 90) return '1–2 months'
  if (days < 180) return '3–4 months'
  if (days < 270) return '5–6 months'
  return 'More than 6 months'
}
</script>
