<!--
  pages/business-partner/Milestones.vue — work milestones across contracts.

  Wave 1 fixes:
  - btn-sm removed (banned)
  - Redundant global component imports removed
  - New status values from expanded enum: pending_funding | funded | in_progress |
    submitted | revision_requested | approved | released | disputed | refunded | paid
  - Rejection reason displayed inline when status=rejected/disputed
  - Revision notes displayed when status=revision_requested with Resubmit CTA
  - Auto-release countdown displayed on submitted milestones
  - Submit action uses useForm() at top-level with notes textarea (Wave 4 adds full modal)
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Work"
      title="Milestones"
      :subtitle="`${pendingCount} pending · ${submittedCount} under review · ${overdueCount} overdue`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="flag-2"       :value="pendingCount"   label="Pending"     bg-color="var(--icon-bg-gold)"  icon-color="var(--gold-dark)" />
      <AegisStatChip icon="send"         :value="submittedCount" label="Under review" bg-color="var(--icon-bg-blue)"  icon-color="var(--blue-dark)" />
      <AegisStatChip icon="check-circle" :value="releasedCount"  label="Paid"        bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="alert-triangle" :value="overdueCount" label="Overdue"     bg-color="var(--icon-bg-red)"   icon-color="var(--red-dark)" />
      <AegisStatChip icon="dollar"       :value="pricing.formatCents(pendingValue)" label="Pending value" />
    </div>

    <AegisEmptyState
      v-if="!milestones.length"
      icon="flag-2"
      title="No milestones yet"
      description="Milestones appear once you have an active contract with milestone-based payments."
    />

    <template v-else>
      <!-- Revision-requested banner (urgent attention needed) -->
      <div v-if="revisionNeeded.length" class="alert-banner alert-banner-warning">
        <AegisIcon name="alert-triangle" :size="16" />
        <span>{{ revisionNeeded.length }} milestone{{ revisionNeeded.length === 1 ? '' : 's' }} need{{ revisionNeeded.length === 1 ? 's' : '' }} revision — review provider feedback below.</span>
      </div>

      <AegisCard>
        <table class="data-table">
          <thead>
            <tr>
              <th>Milestone</th>
              <th>Contract</th>
              <th>Due</th>
              <th>Amount</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="m in milestones"
              :key="m.id"
              :class="{
                'is-overdue':          isOverdue(m),
                'is-revision':         m.status === 'revision_requested',
                'is-disputed':         m.status === 'disputed',
              }"
            >
              <td class="data-table-primary">
                <div class="milestone-title-wrap">
                  {{ m.title }}
                  <!-- Revision notes from provider -->
                  <div v-if="m.status === 'revision_requested' && m.revision_notes" class="milestone-revision-notes">
                    <AegisIcon name="message-circle" :size="12" />
                    <em>{{ m.revision_notes }}</em>
                  </div>
                  <!-- Rejection reason -->
                  <div v-if="(m.status === 'rejected' || m.status === 'disputed') && m.rejection_reason" class="milestone-rejection-reason">
                    <AegisIcon name="x-circle" :size="12" />
                    {{ m.rejection_reason }}
                  </div>
                  <!-- Auto-release countdown on submitted -->
                  <div v-if="m.status === 'submitted' && m.auto_release_at" class="milestone-auto-release">
                    <AegisIcon name="hourglass" :size="12" />
                    Auto-approves {{ formatAutoRelease(m.auto_release_at) }}
                  </div>
                </div>
              </td>
              <td>{{ m.contract_title }}</td>
              <td>{{ m.due_at ? formatDate(m.due_at) : '—' }}</td>
              <td>{{ pricing.formatCents(m.amount_cents) }}</td>
              <td>
                <AegisBadge :label="statusLabel(m.status)" :variant="statusVariant(m.status)" />
              </td>
              <td class="data-table-actions">
                <!-- Awaiting funding — provider needs to fund before BP can work -->
                <span v-if="m.status === 'pending_funding'" class="milestone-awaiting-funding">
                  <AegisIcon name="lock" :size="12" />
                  Awaiting funding
                </span>

                <!-- Submit (pending / funded / in_progress) -->
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
              </td>
            </tr>
          </tbody>
        </table>
      </AegisCard>
    </template>

    <!-- Submit / Resubmit inline form (Wave 4 replaces with full MilestoneSubmitModal) -->
    <AegisModal
      v-model="submitModal"
      title="Submit milestone work"
      size="md"
    >
      <div v-if="activeMs">
        <p class="modal-desc">
          Submitting <strong>{{ activeMs.title }}</strong>
          <span v-if="activeMs.status === 'revision_requested'"> (revision)</span>
          for provider review.
        </p>
        <div class="form-group">
          <label class="form-label" for="ms-notes">Work summary / notes <span class="req">*</span></label>
          <textarea
            id="ms-notes"
            v-model="submitForm.notes"
            class="form-textarea"
            :class="{ 'is-error': fieldError('notes') }"
            rows="5"
            placeholder="Describe what was completed, any relevant details, or links to deliverables…"
            @blur="v$.notes.$touch()"
          />
          <div v-if="fieldError('notes')" class="form-error">{{ fieldError('notes') }}</div>
          <div class="form-hint">{{ submitForm.notes.length }} / 2000 characters</div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" :disabled="submitForm.processing" @click="submitModal = false">
          Cancel
        </button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="submitForm.processing || !canConfirmSubmit"
          @click="confirmSubmit"
        >
          <AegisIcon v-if="submitForm.processing" name="refresh-cw" :size="13" class="btn-spin" />
          {{ submitForm.processing ? 'Submitting…' : 'Submit for review' }}
        </button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm }       from '@inertiajs/vue3'
import useVuelidate      from '@vuelidate/core'
import { required, minLength, maxLength } from '@vuelidate/validators'
import AppLayout         from '@/layouts/AppLayout.vue'
import { useToast }      from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({ milestones: { type: Array, default: () => [] } })
const toast   = useToast()
const pricing = usePricingStore()

// ── Submit modal state ──────────────────────────────────────────────────────
const submitModal = ref(false)
const activeMs    = ref(null)

const submitForm = useForm({ notes: '' })

const rules = {
  notes: { required, minLength: minLength(10), maxLength: maxLength(2000) },
}
const v$ = useVuelidate(rules, submitForm)

function fieldError(f) {
  const e = v$.value[f]?.$errors
  return e?.length ? e[0].$message : null
}

const canConfirmSubmit = computed(() =>
  submitForm.notes.trim().length >= 10 && submitForm.notes.length <= 2000,
)

function openSubmit(m) {
  activeMs.value   = m
  submitForm.notes = ''
  v$.value.$reset()
  submitModal.value = true
}

async function confirmSubmit() {
  const valid = await v$.value.$validate()
  if (!valid) return
  submitForm.post(route('bp.milestones.submit', { milestone: activeMs.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Milestone submitted for review.')
      submitModal.value = false
    },
    onError: () => toast.error('Could not submit milestone.'),
  })
}

// ── Computed ────────────────────────────────────────────────────────────────
const pendingCount   = computed(() => props.milestones.filter((m) => ['pending', 'funded', 'in_progress'].includes(m.status)).length)
const submittedCount = computed(() => props.milestones.filter((m) => ['submitted', 'approved'].includes(m.status)).length)
const releasedCount  = computed(() => props.milestones.filter((m) => ['released', 'paid'].includes(m.status)).length)
const overdueCount   = computed(() => props.milestones.filter((m) => isOverdue(m)).length)
const pendingValue   = computed(() =>
  props.milestones
    .filter((m) => ['pending', 'funded', 'in_progress'].includes(m.status))
    .reduce((s, m) => s + (m.amount_cents || 0), 0),
)
const revisionNeeded = computed(() => props.milestones.filter((m) => m.status === 'revision_requested'))

function canSubmit(m) {
  return ['pending', 'funded', 'in_progress'].includes(m.status)
}

function isOverdue(m) {
  return ['pending', 'funded', 'in_progress'].includes(m.status) && m.due_at && new Date(m.due_at) < Date.now()
}

// ── Labels & variants ───────────────────────────────────────────────────────
function statusLabel(s) {
  return {
    pending:            'Pending',
    pending_funding:    'Awaiting Funding',
    funded:             'Funded',
    in_progress:        'In Progress',
    submitted:          'Under Review',
    revision_requested: 'Revision Requested',
    approved:           'Approved',
    released:           'Paid',
    paid:               'Paid',
    disputed:           'Disputed',
    refunded:           'Refunded',
    rejected:           'Rejected',
  }[s] ?? s
}

function statusVariant(s) {
  return {
    pending:            'gold',
    pending_funding:    'neutral',
    funded:             'blue',
    in_progress:        'blue',
    submitted:          'blue',
    revision_requested: 'gold',
    approved:           'green',
    released:           'green',
    paid:               'green',
    disputed:           'red',
    refunded:           'neutral',
    rejected:           'red',
  }[s] ?? 'neutral'
}

// ── Date helpers ─────────────────────────────────────────────────────────────
function formatDate(iso) {
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatAutoRelease(iso) {
  const diff  = new Date(iso) - Date.now()
  if (diff <= 0) return 'imminently'
  const days  = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  if (days > 0) return `in ${days}d ${hours}h`
  return `in ${hours}h`
}
</script>

<style scoped>
.is-overdue td { background: rgba(220, 38, 38, 0.05); }
.is-revision td { background: rgba(234, 179, 8, 0.05); }
.is-disputed td { background: rgba(220, 38, 38, 0.08); }

.milestone-title-wrap { display: flex; flex-direction: column; gap: 4px; }
.milestone-revision-notes,
.milestone-rejection-reason,
.milestone-auto-release {
  display: flex;
  align-items: flex-start;
  gap: 4px;
  font-size: 12px;
  margin-top: 2px;
}
.milestone-revision-notes { color: var(--text-secondary); font-style: italic; }
.milestone-rejection-reason { color: var(--red-dark); }
.milestone-auto-release { color: var(--text-tertiary); }

.milestone-awaiting-funding {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--text-tertiary);
}

.contract-escrow-strip {
  padding: 10px 0 0;
  margin-top: 10px;
  border-top: 1px solid var(--border-subtle);
}
.contract-escrow-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: 6px;
}
.contract-escrow-bar {
  height: 4px;
  background: var(--surface-2);
  border-radius: 2px;
  display: flex;
  overflow: hidden;
}
.contract-escrow-bar-released {
  background: var(--green-dark);
  transition: width 0.3s;
}
.contract-escrow-bar-held {
  background: var(--gold-dark);
  transition: width 0.3s;
}
</style>
