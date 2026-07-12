<!--
  HireModal.vue — two-step hire flow.
  Step 1: Confirm rate + message (all job types).
  Step 2: Add initial milestones (non-fixed jobs only — milestone contracts).
  
  Milestone-based contracts (hourly / retainer budget_type) require at least
  one milestone before the contract can go active. Step 2 enforces this inline
  before the offer is sent, so the contract is born with real deliverables.
-->
<template>
  <AegisModal v-model="isOpen" :title="step === 1 ? 'Send Hire Offer' : 'Add Contract Milestones'" size="md" @update:model-value="onUpdateOpen">

    <!-- ── Step 1: Confirm Rate + Message ── -->
    <template v-if="step === 1">
      <div v-if="proposal" class="modal-sub" style="margin-bottom:14px">To: {{ applicantName }} · {{ jobTitle }}</div>

      <div v-if="proposal" class="hire-summary">
        <div class="avatar avatar-md avatar-gold" :style="avatarStyle">
          <template v-if="!proposal.bp?.avatar_url">{{ initials }}</template>
        </div>
        <div>
          <div style="font-size:14px;font-weight:700;color:var(--text)">{{ applicantName }}</div>
          <div style="font-size:12px;color:var(--text-2)">{{ bpTypeLabel }} · {{ jobTitle }} · {{ stats.jobs_done }} jobs completed</div>
          <div v-if="proposal.bp?.verified" style="font-size:11px;color:var(--green);margin-top:2px;display:flex;align-items:center;gap:4px">
            <AegisIcon name="dot" :size="12" :filled="true" />
            Verified · HIPAA-trained
          </div>
        </div>
      </div>

      <div class="form-group" style="margin-top:14px">
        <label class="form-label" for="hireRate">Agreed Rate</label>
        <div class="input-suffix-wrap">
          <input id="hireRate" v-model="rateDisplay" type="number" min="0" step="0.01" class="form-input" placeholder="0.00" />
          <span class="input-suffix">{{ rateUnit }}</span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="hireMessage">Message to Candidate</label>
        <textarea id="hireMessage" v-model="message" class="form-textarea" style="min-height:80px"></textarea>
      </div>

      <div v-if="job" style="display:flex;flex-direction:column;gap:7px">
        <label v-if="job.requires_nda" style="display:flex;gap:8px;align-items:center;font-size:13px"><AegisIcon name="check" :size="13" style="color:var(--green-dark)" /> NDA required for this role</label>
        <label v-if="job.requires_baa" style="display:flex;gap:8px;align-items:center;font-size:13px"><AegisIcon name="check" :size="13" style="color:var(--green-dark)" /> BAA required for this role</label>
      </div>

      <!-- Milestone notice for non-fixed jobs -->
      <div v-if="isMilestoneContract" class="hire-milestone-notice">
        <AegisIcon name="layers" :size="14" />
        <span>This is a <strong>milestone-based contract</strong>. You'll define deliverables and payment schedule in the next step.</span>
      </div>
    </template>

    <!-- ── Step 2: Add Milestones ── -->
    <template v-else>
      <div class="modal-sub" style="margin-bottom:14px">
        {{ applicantName }} · {{ jobTitle }}
      </div>

      <div class="hire-milestone-intro">
        <AegisIcon name="layers" :size="15" />
        <div>
          <div class="hire-milestone-intro-title">Define contract milestones</div>
          <div class="hire-milestone-intro-desc">
            At least one milestone is required. Each milestone represents a deliverable the BP completes before escrow is released. You can add more later.
          </div>
        </div>
      </div>

      <!-- Milestone rows -->
      <div class="hire-ms-list">
        <div v-for="(ms, i) in milestones" :key="i" class="hire-ms-row">
          <div class="hire-ms-row-header">
            <span class="hire-ms-num">Milestone {{ i + 1 }}</span>
            <button
              v-if="milestones.length > 1"
              type="button"
              class="btn-icon btn-icon-danger"
              data-tooltip="Remove"
              @click="removeMilestone(i)"
            >
              <AegisIcon name="x" :size="12" />
            </button>
          </div>
          <div class="form-group" style="margin-bottom:8px">
            <input
              v-model="ms.title"
              type="text"
              class="form-input"
              :class="{ 'is-error': msErrors[i]?.title }"
              placeholder="Deliverable title (e.g. SOP #1 — Client Intake Workflow)"
              maxlength="191"
            />
            <div v-if="msErrors[i]?.title" class="form-error">{{ msErrors[i].title }}</div>
          </div>
          <div class="hire-ms-row-2col">
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">Amount</label>
              <div class="input-suffix-wrap">
                <input
                  v-model="ms.amount_dollars"
                  type="number"
                  min="0"
                  step="0.01"
                  class="form-input"
                  :class="{ 'is-error': msErrors[i]?.amount }"
                  placeholder="0.00"
                />
                <span class="input-suffix">USD</span>
              </div>
              <div v-if="msErrors[i]?.amount" class="form-error">{{ msErrors[i].amount }}</div>
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">Due Date <span class="form-optional">(optional)</span></label>
              <input v-model="ms.due_at" type="date" class="form-input" />
            </div>
          </div>
        </div>
      </div>

      <button type="button" class="btn btn-outline hire-ms-add-btn" @click="addMilestone">
        <AegisIcon name="plus" :size="13" />
        Add Another Milestone
      </button>

      <!-- Total -->
      <div v-if="totalCents > 0" class="hire-ms-total">
        <span>Milestones total</span>
        <span class="hire-ms-total-val">{{ formatCents(totalCents) }}</span>
      </div>
    </template>

    <!-- ── Footer ── -->
    <template #footer>
      <!-- Step 1 footer -->
      <template v-if="step === 1">
        <button type="button" class="btn btn-outline" :disabled="busy" @click="close">Cancel</button>
        <button
          type="button"
          class="btn btn-success"
          :class="{ 'btn-spin': busy && !isMilestoneContract }"
          :disabled="busy"
          @click="isMilestoneContract ? goToStep2() : confirm()"
        >
          <AegisIcon v-if="busy && !isMilestoneContract" name="refresh-cw" :size="13" class="spin" />
          {{ isMilestoneContract ? 'Next: Define Milestones →' : (busy ? 'Sending…' : 'Send Offer') }}
        </button>
      </template>

      <!-- Step 2 footer -->
      <template v-else>
        <button type="button" class="btn btn-outline" @click="step = 1">← Back</button>
        <button
          type="button"
          class="btn btn-success"
          :class="{ 'btn-spin': busy }"
          :disabled="busy"
          @click="confirm"
        >
          <AegisIcon v-if="busy" name="refresh-cw" :size="13" class="spin" />
          {{ busy ? 'Sending Offer…' : 'Send Offer & Create Contract' }}
        </button>
      </template>
    </template>

  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  proposal:   { type: Object, default: null },
  job:        { type: Object, default: null },
  jobTitle:   { type: String, default: '' },
  stats:      { type: Object, default: () => ({ jobs_done: 0, asking_rate_cents: null }) },
})
const emit = defineEmits(['update:modelValue', 'done'])

const toast = useToast()

// ── State ─────────────────────────────────────────────────────────────────────
const step        = ref(1)
const rateDisplay = ref(0)
const message     = ref('')
const busy        = ref(false)
const milestones  = ref([{ title: '', amount_dollars: '', due_at: '' }])
const msErrors    = ref([])

// ── Computed ──────────────────────────────────────────────────────────────────
const isOpen = computed(() => props.modelValue)

const isMilestoneContract = computed(() => {
  const bt = props.job?.budget_type
  const val = bt && typeof bt === 'object' && 'value' in bt ? bt.value : (bt ?? '')
  return val !== 'fixed'
})

const applicantName = computed(() => props.proposal?.bp?.display_name ?? 'this applicant')
const bpTypeLabel   = computed(() => {
  const t = props.proposal?.bp?.bp_type
  return t ? t.charAt(0).toUpperCase() + t.slice(1) : 'Business Partner'
})
const initials = computed(() => {
  const n = props.proposal?.bp?.display_name
  if (!n) return 'BP'
  return n.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
})
const avatarStyle = computed(() => {
  const url = props.proposal?.bp?.avatar_url
  return url ? { backgroundImage: `url(${url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}
})
const rateUnit = computed(() => ({ hourly: '/hr', retainer: '/mo', fixed: ' total' }[props.proposal?.proposed_rate_type] || ''))

const totalCents = computed(() =>
  milestones.value.reduce((sum, ms) => sum + Math.round((parseFloat(ms.amount_dollars) || 0) * 100), 0)
)

// ── Watchers ──────────────────────────────────────────────────────────────────
watch(() => props.proposal, (p) => {
  if (!p) return
  rateDisplay.value = p.proposed_rate_cents ? p.proposed_rate_cents / 100 : 0
  message.value = `Hi ${p.bp?.display_name ?? ''}! We'd love to have you join our practice team. We've reviewed your proposal and would like to move forward.`
}, { immediate: true })

watch(() => props.modelValue, (v) => {
  if (!v) {
    // Reset on close
    step.value = 1
    milestones.value = [{ title: '', amount_dollars: '', due_at: '' }]
    msErrors.value   = []
  }
})

// ── Methods ───────────────────────────────────────────────────────────────────
function onUpdateOpen(v) { emit('update:modelValue', v) }
function close() { emit('update:modelValue', false) }

function formatCents(c) {
  return '$' + (c / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })
}

function addMilestone() {
  milestones.value.push({ title: '', amount_dollars: '', due_at: '' })
}

function removeMilestone(i) {
  milestones.value.splice(i, 1)
}

function goToStep2() {
  step.value = 2
}

function validateMilestones() {
  msErrors.value = milestones.value.map(ms => {
    const e = {}
    if (!ms.title.trim()) e.title = 'Title is required.'
    if (!ms.amount_dollars || parseFloat(ms.amount_dollars) <= 0) e.amount = 'Amount must be greater than 0.'
    return e
  })
  return msErrors.value.every(e => Object.keys(e).length === 0)
}

function confirm() {
  if (!props.proposal) return

  // Validate milestones on step 2
  if (isMilestoneContract.value && step.value === 2) {
    if (!validateMilestones()) return
  }

  busy.value = true

  const finalRateCents = rateDisplay.value != null && rateDisplay.value !== ''
    ? Math.round(Number(rateDisplay.value) * 100)
    : null

  const payload = { final_rate_cents: finalRateCents }

  // Include initial milestones for milestone contracts
  if (isMilestoneContract.value) {
    payload.milestones = milestones.value.map((ms, i) => ({
      title:        ms.title.trim(),
      amount_cents: Math.round((parseFloat(ms.amount_dollars) || 0) * 100),
      due_at:       ms.due_at || null,
      sort_order:   i + 1,
    }))
  }

  router.post(
    route('provider.jobs.proposal.accept', { job: props.proposal.job_id, proposal: props.proposal.id }),
    payload,
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(`Offer sent to ${applicantName.value}. Contract created.`)
        busy.value = false
        emit('done')
        close()
      },
      onError: () => {
        toast.error('Could not send offer.')
        busy.value = false
      },
    }
  )
}
</script>

<style scoped>
.hire-summary { display: flex; gap: 12px; align-items: center; padding: 12px; background: var(--green-light); border-radius: var(--radius-sm); }

.hire-milestone-notice {
  display: flex; align-items: flex-start; gap: 8px;
  margin-top: 14px; padding: 10px 14px;
  background: rgba(160,129,62,0.07); border: 1px solid var(--gold);
  border-radius: var(--radius-sm);
  font-size: 12.5px; color: var(--gold-dark); line-height: 1.5;
}

.hire-milestone-intro {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 12px 14px; margin-bottom: 16px;
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}
.hire-milestone-intro-title { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.hire-milestone-intro-desc  { font-size: 12px; color: var(--text-3); line-height: 1.5; }

.hire-ms-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 12px; }
.hire-ms-row {
  padding: 12px 14px;
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}
.hire-ms-row-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 8px;
}
.hire-ms-num { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-4); }
.hire-ms-row-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

.hire-ms-add-btn { width: 100%; justify-content: center; margin-bottom: 14px; }

.hire-ms-total {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 14px;
  background: var(--surface-3); border-radius: var(--radius-sm);
  font-size: 13px; font-weight: 600; color: var(--text-3);
}
.hire-ms-total-val { font-family: var(--font-serif, serif); font-size: 16px; font-weight: 700; color: var(--text); }
</style>
