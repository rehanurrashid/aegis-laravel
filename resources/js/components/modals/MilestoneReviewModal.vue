<!--
  MilestoneReviewModal.vue — Provider reviews a BP milestone submission.

  Three actions:
    approve          → EscrowService::releaseMilestone() → funds transfer to BP
    request_revision → ContractService::requestRevision() → notes sent to BP, resubmit allowed
    reject           → opens OpenDisputeModal (dispute path)

  Posts to:
    provider.jobs.contract.milestones.review

  Props:
    contract    Object | null
    milestone   Object | null   — must be status='submitted'
    submission  Object | null   — latest BpMilestoneSubmission row
-->
<template>
  <AegisModal
    :model-value="!!(contract && milestone && milestone.status === 'submitted')"
    title="Review milestone submission"
    size="xl"
    @update:model-value="onClose"
  >
    <div v-if="contract && milestone" class="ms-review-modal">

      <!-- Header -->
      <div class="ms-review-header">
        <div>
          <div class="ms-review-eyebrow">Submitted by {{ contract.bp?.display_name }}</div>
          <div class="ms-review-title">{{ milestone.title }}</div>
          <div class="ms-review-meta">
            {{ pricing.formatCents(milestone.amount_cents) }} in escrow
            <span v-if="milestone.auto_release_at" class="ms-review-auto-release">
              · Auto-releases {{ formatAutoRelease(milestone.auto_release_at) }}
            </span>
          </div>
        </div>
        <AegisBadge label="Under Review" variant="blue" />
      </div>

      <!-- Auto-release countdown banner -->
      <div v-if="milestone.auto_release_at && daysUntilAutoRelease <= 2" class="alert-banner alert-banner-warning">
        <AegisIcon name="hourglass" :size="14" />
        <span>
          <strong>Review required soon:</strong>
          If no action is taken, funds auto-release to the Business Partner
          {{ formatAutoRelease(milestone.auto_release_at) }}.
        </span>
      </div>

      <!-- Submission content -->
      <div class="ms-review-submission">
        <div class="ms-review-section-title">Submission notes</div>
        <div v-if="submission?.submission_notes" class="ms-review-notes">
          {{ submission.submission_notes }}
        </div>
        <div v-else class="ms-review-notes ms-review-notes-empty">
          No notes provided.
        </div>

        <div v-if="submission?.hours_logged" class="ms-review-hours">
          <AegisIcon name="clock" :size="12" />
          {{ submission.hours_logged }} hours logged
        </div>
      </div>

      <!-- Revision history -->
      <div v-if="(milestone.revision_count ?? 0) > 0" class="ms-review-revision-history">
        <div class="ms-review-section-title">
          Revision history ({{ milestone.revision_count }} round{{ milestone.revision_count === 1 ? '' : 's' }})
        </div>
        <div class="ms-review-revision-note">
          <AegisIcon name="message-circle" :size="12" />
          Previous revision notes: {{ milestone.revision_notes ?? '—' }}
        </div>
      </div>

      <!-- Action selector -->
      <div class="section-divider">Your decision</div>

      <div class="ms-review-actions">
        <label
          v-for="opt in actionOptions"
          :key="opt.value"
          class="ms-review-action-card"
          :class="{ 'is-selected': form.action === opt.value, [`is-${opt.color}`]: true }"
        >
          <input
            v-model="form.action"
            type="radio"
            :value="opt.value"
            class="ms-review-action-radio"
            @change="v$.action.$touch()"
          />
          <div class="ms-review-action-icon">
            <AegisIcon :name="opt.icon" :size="16" />
          </div>
          <div class="ms-review-action-body">
            <div class="ms-review-action-title">{{ opt.label }}</div>
            <div class="ms-review-action-desc">{{ opt.desc }}</div>
          </div>
        </label>
      </div>

      <!-- Revision notes (shown when revision_requested) -->
      <div v-if="form.action === 'revision_requested'" class="form-group ms-review-notes-field">
        <label class="form-label" for="ms-review-notes">
          Feedback for Business Partner <span class="req">*</span>
        </label>
        <textarea
          id="ms-review-notes"
          v-model="form.notes"
          class="form-textarea"
          :class="{ 'is-error': fieldError('notes') }"
          rows="4"
          placeholder="Describe what needs to change and why. Be specific — the BP will see this directly."
          @blur="v$.notes.$touch()"
        />
        <div v-if="fieldError('notes')" class="form-error">{{ fieldError('notes') }}</div>
        <div class="form-hint">{{ form.notes.length }} / 1000 characters</div>
      </div>

      <!-- Approve confirmation detail -->
      <div v-if="form.action === 'approved'" class="alert-banner alert-banner-green">
        <AegisIcon name="check-circle" :size="14" />
        <span>
          Approving will immediately transfer
          <strong>{{ pricing.formatCents(milestone.amount_cents) }}</strong>
          from Aegis escrow to {{ contract.bp?.display_name }}'s Stripe Connect account.
          This cannot be undone.
        </span>
      </div>

      <!-- Reject info -->
      <div v-if="form.action === 'rejected'" class="alert-banner alert-banner-warning">
        <AegisIcon name="alert-triangle" :size="14" />
        <span>
          Rejecting opens a formal dispute. Aegis will mediate and decide whether to release
          or refund the escrow. You will be asked to provide evidence.
        </span>
      </div>

    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="form.processing" @click="onClose">
        Cancel
      </button>
      <button
        type="button"
        class="btn btn-primary"
        :class="{ 'btn-danger': form.action === 'rejected' }"
        :disabled="form.processing || !canSubmit"
        @click="submit"
      >
        <AegisIcon v-if="form.processing" name="refresh-cw" :size="13" class="btn-spin" />
        {{ submitLabel }}
      </button>
    </template>
  </AegisModal>

  <!-- Dispute modal — opened when provider selects "reject" -->
  <OpenDisputeModal
    v-model="showDisputeModal"
    :subject="disputeSubject"
    post-route="provider.disputes.store"
    @opened="onDisputeOpened"
  />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import useVuelidate from '@vuelidate/core'
import { required, maxLength, requiredIf } from '@vuelidate/validators'
import { useToast } from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'
import OpenDisputeModal from '@/components/modals/OpenDisputeModal.vue'

const props = defineProps({
  contract:   { type: Object, default: null },
  milestone:  { type: Object, default: null },
  submission: { type: Object, default: null },   // latest BpMilestoneSubmission
})

const emit = defineEmits(['update:modelValue'])

// ── Dispute sub-modal (reject path) ──────────────────────────────────────────
const showDisputeModal = ref(false)
const disputeSubject   = computed(() => {
  if (!props.milestone) return null
  return {
    type:         'bp_milestone',
    id:           props.milestone.id,
    amount_cents: props.milestone.amount_cents,
    label:        props.milestone.title,
  }
})

function onDisputeOpened() {
  // Dispute opened — close the review modal too
  emit('update:modelValue', null)
}

const toast   = useToast()
const pricing = usePricingStore()

const form = useForm({
  action: '',   // 'approved' | 'revision_requested' | 'rejected'
  notes:  '',
})

// ── Config ───────────────────────────────────────────────────────────────────
const actionOptions = [
  {
    value: 'approved',
    label: 'Approve & release payment',
    desc:  'Work is complete. Transfer escrow funds to Business Partner immediately.',
    icon:  'check-circle',
    color: 'green',
  },
  {
    value: 'revision_requested',
    label: 'Request revision',
    desc:  'Work needs changes. Send feedback and let Business Partner resubmit.',
    icon:  'refresh-cw',
    color: 'gold',
  },
  {
    value: 'rejected',
    label: 'Reject & open dispute',
    desc:  'Work is unacceptable. Open a formal Aegis mediation for escrow resolution.',
    icon:  'x-circle',
    color: 'red',
  },
]

// ── Vuelidate ─────────────────────────────────────────────────────────────────
const rules = {
  action: { required },
  notes:  {
    requiredIfRevision: requiredIf(() => form.action === 'revision_requested'),
    maxLength: maxLength(1000),
  },
}
const v$ = useVuelidate(rules, form)

function fieldError(f) {
  const e = v$.value[f]?.$errors
  return e?.length ? e[0].$message : null
}

// ── Computed ─────────────────────────────────────────────────────────────────
const canSubmit = computed(() => {
  if (!form.action) return false
  if (form.action === 'revision_requested' && form.notes.trim().length < 10) return false
  return !form.processing
})

const submitLabel = computed(() => {
  if (form.processing) return 'Submitting…'
  return {
    approved:           'Approve & release payment',
    revision_requested: 'Send revision request',
    rejected:           'Reject & open dispute',
  }[form.action] ?? 'Submit review'
})

const daysUntilAutoRelease = computed(() => {
  if (!props.milestone?.auto_release_at) return 999
  const diff = new Date(props.milestone.auto_release_at) - Date.now()
  return Math.ceil(diff / 86400000)
})

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatAutoRelease(iso) {
  const diff  = new Date(iso) - Date.now()
  if (diff <= 0) return 'imminently'
  const days  = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  if (days > 0) return `in ${days}d ${hours}h`
  return `in ${hours}h`
}

function onClose() {
  emit('update:modelValue', null)
  setTimeout(() => { form.reset(); v$.value.$reset() }, 200)
}

// ── Submit ────────────────────────────────────────────────────────────────────
async function submit() {
  const valid = await v$.value.$validate()
  if (!valid) return

  // "rejected" opens the dispute modal with milestone pre-filled
  if (form.action === 'rejected') {
    showDisputeModal.value = true
    return
  }

  form.post(route('provider.jobs.contract.milestones.review', {
    contract:  props.contract.id,
    milestone: props.milestone.id,
  }), {
    preserveScroll: true,
    onSuccess: () => {
      const msgs = {
        approved:           'Milestone approved. Payment released to Business Partner.',
        revision_requested: 'Revision requested. Business Partner has been notified.',
      }
      toast.success(msgs[form.action] ?? 'Review submitted.')
      onClose()
    },
    onError: (e) => toast.error(e.milestone ?? e.contract ?? 'Review failed.'),
  })
}
</script>
