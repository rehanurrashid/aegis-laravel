<!--
  EditJobModal.vue — Provider "Edit Support Request" modal.
  Replaces job-postings.php #editJobModal. Expanded to mirror PostJobModal's
  full field set/steps (rule: Edit must expose the same depth as Create).
  Wired to provider.jobs.update / provider.jobs.destroy.
-->
<template>
  <AegisModal v-model="isOpen" :title="modalTitle" size="xl" @update:model-value="onUpdateOpen">

    <!-- ── CLOSED STATE — read-only summary ─────────────────────────── -->
    <template v-if="isClosed">
      <div class="alert" :class="jobStatusVal === 'filled' ? 'alert-success' : 'alert-info'" style="margin-bottom:18px">
        <div class="alert-icon">
          <AegisIcon :name="jobStatusVal === 'filled' ? 'check' : 'x'" :size="16" />
        </div>
        <div class="alert-content">
          <strong>{{ jobStatusVal === 'filled' ? 'This position has been filled.' : 'This posting has been closed.' }}</strong>
          {{ jobStatusVal === 'filled'
            ? 'A Business Partner was hired for this role. See the Hired tab for contract details.'
            : 'This posting is no longer visible to Business Partners. Reopen it to accept new applications.' }}
        </div>
      </div>

      <div v-if="job" class="form-group" style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px">
        <div>
          <div class="form-label" style="margin-bottom:4px">Request Title</div>
          <div style="font-size:13px;font-weight:600;color:var(--text)">{{ job.title }}</div>
        </div>
        <div>
          <div class="form-label" style="margin-bottom:4px">Category</div>
          <div style="font-size:13px;color:var(--text)">{{ job.category }}</div>
        </div>
        <div>
          <div class="form-label" style="margin-bottom:4px">Budget</div>
          <div style="font-size:13px;color:var(--text)">{{ job.budget_amount_cents ? '$' + (job.budget_amount_cents/100).toLocaleString() : '—' }}{{ job.budget_type === 'hourly' ? '/hr' : job.budget_type === 'retainer' ? '/mo' : '' }}</div>
        </div>
        <div>
          <div class="form-label" style="margin-bottom:4px">Posted</div>
          <div style="font-size:13px;color:var(--text)">{{ postedLabel }}</div>
        </div>
        <div v-if="job.description" style="grid-column:1/-1">
          <div class="form-label" style="margin-bottom:4px">Description</div>
          <div style="font-size:13px;color:var(--text-2);line-height:1.6">{{ job.description }}</div>
        </div>
      </div>
    </template>

    <!-- ── EDIT STATE — full wizard ──────────────────────────────────── -->
    <template v-else>
    <div class="modal-steps">
      <div class="modal-step" :class="stepClass(0)"><span class="modal-step-num"><AegisIcon v-if="step > 0" name="check" :size="12" /><template v-else>1</template></span>Job Details</div>
      <div class="modal-step-divider"></div>
      <div class="modal-step" :class="stepClass(1)"><span class="modal-step-num"><AegisIcon v-if="step > 1" name="check" :size="12" /><template v-else>2</template></span>Requirements</div>
      <div class="modal-step-divider"></div>
      <div class="modal-step" :class="stepClass(2)"><span class="modal-step-num">3</span>Compensation &amp; Status</div>
    </div>

    <!-- STEP 1 -->
    <div v-show="step === 0">
      <div class="section-divider">Basic Information</div>
      <div class="form-group">
        <label class="form-label" for="ejTitle">Request Title *</label>
        <input id="ejTitle" v-model="form.title" class="form-input" :class="{ 'is-error': fieldError('title') }" maxlength="191" @blur="v$.title.$touch()" />
        <div v-if="fieldError('title')" class="form-error">{{ fieldError('title') }}</div>
      </div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label" for="ejCategory">Service Category *</label>
          <select id="ejCategory" v-model="form.category" class="form-select" :class="{ 'is-error': fieldError('category') }" @blur="v$.category.$touch()">
            <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
          </select>
          <div v-if="fieldError('category')" class="form-error">{{ fieldError('category') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="ejType">Job Type</label>
          <select id="ejType" v-model="form.job_type" class="form-select">
            <option value="">Select type...</option>
            <option value="one_time">One-Time Project</option>
            <option value="ongoing">Ongoing / Retainer</option>
            <option value="contract">Fixed-Price Contract</option>
            <option value="part_time">Part-Time (Hours/Week)</option>
            <option value="full_time">Full-Time</option>
          </select>
        </div>
      </div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label" for="ejLocation">Work Location</label>
          <select id="ejLocation" v-model="form.location_pref" class="form-select">
            <option value="remote">Fully Remote</option>
            <option value="onsite">On-Site</option>
            <option value="hybrid">Hybrid</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="ejStart">Start Date</label>
          <input id="ejStart" v-model="form.start_date" type="date" class="form-input" />
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="ejTags">Tags / Skills (comma-separated)</label>
        <input id="ejTags" v-model="tagsInput" class="form-input" placeholder="e.g., Medical Billing, EHR, Athena" />
      </div>
      <div class="form-group">
        <label class="form-label" for="ejDesc">Job Description *</label>
        <textarea id="ejDesc" v-model="form.description" class="form-textarea" :class="{ 'is-error': fieldError('description') }" rows="5" @blur="v$.description.$touch()"></textarea>
        <div v-if="fieldError('description')" class="form-error">{{ fieldError('description') }}</div>
      </div>
    </div>

    <!-- STEP 2 -->
    <div v-show="step === 1">
      <div class="section-divider">Candidate Requirements</div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label" for="ejExp">Experience Level</label>
          <select id="ejExp" v-model="form.experience_level" class="form-select">
            <option value="">Any level</option>
            <option value="entry">Entry Level (1–3 years)</option>
            <option value="mid">Intermediate (3–7 years)</option>
            <option value="senior">Senior (7–15 years)</option>
            <option value="expert">Expert (15+ years)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="ejPartnerType">Partner Type Preference</label>
          <select id="ejPartnerType" v-model="form.partner_type_pref" class="form-select">
            <option value="">Any type</option>
            <option value="freelancer">Freelancer</option>
            <option value="agency">Agency</option>
            <option value="consultant">Consultant</option>
            <option value="firm">Firm</option>
            <option value="solopreneur">Solopreneur</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="ejCerts">Required Certifications / Credentials</label>
        <input id="ejCerts" v-model="form.certifications" class="form-input" />
      </div>
      <div class="section-divider" style="margin-top:14px">Special Requirements</div>
      <div class="form-row form-row-3" style="margin-bottom:14px">
        <label class="jpl-avail-label" :class="{ 'jpl-selected-green': form.requires_hipaa }">
          <input v-model="form.requires_hipaa" type="checkbox" style="accent-color: var(--gold-dark)" />
          <AegisIcon name="lock" :size="13" /><span>HIPAA</span>
        </label>
        <label class="jpl-avail-label" :class="{ 'jpl-selected-orange': form.requires_nda }">
          <input v-model="form.requires_nda" type="checkbox" style="accent-color: var(--gold-dark)" />
          <AegisIcon name="file-text" :size="13" /><span>NDA</span>
        </label>
        <label class="jpl-avail-label" :class="{ 'jpl-selected-blue': form.requires_baa }">
          <input v-model="form.requires_baa" type="checkbox" style="accent-color: var(--gold-dark)" />
          <AegisIcon name="shield" :size="13" /><span>BAA</span>
        </label>
      </div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label" for="ejDeadline">Application Deadline</label>
          <input id="ejDeadline" v-model="form.application_deadline" type="date" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label" for="ejMaxApps">Max Applicants to Accept</label>
          <select id="ejMaxApps" v-model.number="form.max_applicants" class="form-select">
            <option :value="0">No limit</option>
            <option :value="10">10 applicants</option>
            <option :value="25">25 applicants</option>
            <option :value="50">50 applicants</option>
          </select>
        </div>
      </div>
    </div>

    <!-- STEP 3 -->
    <div v-show="step === 2">
      <div class="section-divider">Budget &amp; Compensation</div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label" for="ejBudgetType">Budget Type</label>
          <select id="ejBudgetType" v-model="form.budget_type" class="form-select">
            <option value="hourly">Hourly Rate</option>
            <option value="retainer">Monthly Retainer</option>
            <option value="fixed">Fixed Price (Project)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="ejBudgetAmount">Budget ($)</label>
          <div class="input-suffix-wrap">
            <input id="ejBudgetAmount" v-model="budgetDisplay" type="number" min="0" step="0.01" class="form-input" placeholder="0.00" />
            <span class="input-suffix">{{ rateUnit }}</span>
          </div>
        </div>
      </div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label" for="ejPayment">Payment Method</label>
          <select id="ejPayment" v-model="form.payment_method" class="form-select">
            <option>Direct Bank Transfer</option>
            <option>Check</option>
            <option>Zelle / Venmo Business</option>
            <option>Net-30 Invoice</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="ejBilling">Billing Frequency</label>
          <select id="ejBilling" v-model="form.billing_frequency" class="form-select">
            <option>Upon completion</option>
            <option>Weekly</option>
            <option>Bi-weekly</option>
            <option>Monthly</option>
            <option>Milestone-based</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="ejPerks">Additional Perks / Incentives</label>
        <input id="ejPerks" v-model="form.perks" class="form-input" />
      </div>
      <div class="section-divider" style="margin-top:14px">Status</div>
      <div class="form-group">
        <label class="form-label" for="ejStatus">Posting Status</label>
        <select id="ejStatus" v-model="form.status" class="form-select">
          <option value="open">Active</option>
          <option value="paused">Paused</option>
          <option value="draft">Draft</option>
          <option value="closed">Closed</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="ejNotes">Internal Job Notes</label>
        <textarea id="ejNotes" v-model="form.internal_notes" class="form-textarea" rows="2"></textarea>
      </div>
    </div>

    </template><!-- end v-else edit state -->

    <template #footer>
      <!-- CLOSED STATE footer -->
      <template v-if="isClosed">
        <div v-if="job" class="modal-footer-meta">
          <AegisIcon name="briefcase" :size="12" />
          <span class="modal-footer-meta-title">{{ job.title }}</span>
          <span class="modal-footer-meta-sep">·</span>
          <AegisIcon name="calendar" :size="12" />
          <span>Posted {{ postedLabel }}</span>
        </div>
        <button type="button" class="btn btn-outline" @click="close">Close</button>
        <button
          v-if="jobStatusVal === 'closed' || jobStatusVal === 'cancelled'"
          type="button"
          class="btn btn-primary"
          @click="confirmResume"
        >
          <AegisIcon name="refresh-cw" :size="13" />
          Reopen Posting
        </button>
      </template>

      <!-- EDIT STATE footer -->
      <template v-else>
      <!-- Job meta — left side, pushes buttons to the right -->
      <div v-if="job" class="modal-footer-meta">
        <AegisIcon name="briefcase" :size="12" />
        <span class="modal-footer-meta-title">{{ job.title }}</span>
        <span class="modal-footer-meta-sep">·</span>
        <AegisIcon name="calendar" :size="12" />
        <span>Posted {{ postedLabel }}</span>
      </div>

      <button v-if="step > 0" type="button" class="btn btn-outline" :disabled="form.processing" @click="step--">
        <AegisIcon name="arrow-left" :size="13" />
        Back
      </button>
      <button type="button" class="btn btn-danger" :disabled="form.processing" @click="confirmClose">
        <AegisIcon name="x" :size="12" />
        Close Posting
      </button>
      <button v-if="step < 2" type="button" class="btn btn-primary" :disabled="form.processing" @click="next">
        Next Step
        <AegisIcon name="arrow-right-line" :size="13" />
      </button>
      <button v-else type="button" class="btn btn-primary" :disabled="form.processing" @click="save">
        <AegisIcon name="save" :size="12" />
        {{ form.processing ? 'Saving…' : 'Save Changes' }}
      </button>
      </template><!-- end edit-state footer -->
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import useVuelidate from '@vuelidate/core'
import { required, minLength, maxLength, helpers } from '@vuelidate/validators'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { syncFormEnhancements, scanAndEnhance } from '@/plugins/FormEnhancerPlugin'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  job:        { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue'])

const toast = useToast()
const { confirmAction } = useConfirm()
const budgetDisplay = ref(null)
const tagsInput = ref('')
const step = ref(0)

const categories = [
  { value: 'billing',       label: 'Medical Billing & Coding' },
  { value: 'it',             label: 'IT / Software Development' },
  { value: 'accounting',     label: 'Accounting / CPA' },
  { value: 'legal',          label: 'Legal / Healthcare Law' },
  { value: 'marketing',      label: 'Marketing & Growth' },
  { value: 'hr',             label: 'HR / Staffing' },
  { value: 'credentialing',  label: 'Credentialing & Enrollment' },
  { value: 'consulting',     label: 'Practice Consulting' },
  { value: 'design',         label: 'Design & Branding' },
  { value: 'admin',          label: 'Admin / Virtual Assistant' },
]

function blankForm() {
  return {
    title: '', category: '', job_type: '', location_pref: 'remote', start_date: '',
    tags: [], description: '', experience_level: '', partner_type_pref: '', certifications: '',
    requires_hipaa: false, requires_nda: false, requires_baa: false,
    application_deadline: '', max_applicants: 0,
    budget_type: 'hourly', budget_amount_cents: null, payment_method: 'Direct Bank Transfer',
    billing_frequency: 'Monthly', perks: '', is_featured: false, is_urgent: false,
    internal_notes: '', status: 'open',
  }
}

const form = useForm(blankForm())

const isOpen = computed(() => props.modelValue)
function onUpdateOpen(v) { emit('update:modelValue', v) }

// Detect closed/filled/cancelled status — these jobs are read-only
const jobStatusVal = computed(() => {
  const s = props.job?.status
  return (s && typeof s === 'object' && 'value' in s) ? s.value : (s ?? '')
})
const isClosed = computed(() => ['filled', 'closed', 'cancelled'].includes(jobStatusVal.value))
const modalTitle = computed(() => {
  if (!props.job) return 'Edit Support Request'
  const labels = { filled: 'Posting Filled', closed: 'Posting Closed', cancelled: 'Posting Cancelled' }
  return labels[jobStatusVal.value] ?? 'Edit Support Request'
})

function syncEnhancedFields() { syncFormEnhancements() }

watch(() => props.job, (j) => {
  if (!j) return

  // Unwrap backed enum objects {value:'x'} → plain string
  const str  = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')
  // Normalise dates to YYYY-MM-DD — Laravel 'date' cast serialises as full ISO
  const date = (v) => v ? String(v).slice(0, 10) : ''

  Object.assign(form, blankForm(), {
    title:                j.title                                ?? '',
    category:             str(j.category)                        ?? '',
    job_type:             str(j.job_type)                        ?? '',
    location_pref:        str(j.location_pref)                   ?? 'remote',
    start_date:           date(j.start_date),
    tags:                 j.tags                                 ?? [],
    description:          j.description                          ?? '',
    experience_level:     str(j.experience_level)                ?? '',
    partner_type_pref:    str(j.partner_type_pref)               ?? '',
    certifications:       j.certifications                       ?? '',
    requires_hipaa:       !!j.requires_hipaa,
    requires_nda:         !!j.requires_nda,
    requires_baa:         !!j.requires_baa,
    application_deadline: date(j.application_deadline),
    max_applicants:       j.max_applicants                       ?? 0,
    budget_type:          str(j.budget_type)                     ?? 'hourly',
    budget_amount_cents:  j.budget_amount_cents                  ?? null,
    payment_method:       j.payment_method                       ?? 'Direct Bank Transfer',
    billing_frequency:    j.billing_frequency                    ?? 'Monthly',
    perks:                j.perks                                ?? '',
    is_featured:          !!j.is_featured,
    is_urgent:            !!j.is_urgent,
    internal_notes:       j.internal_notes                       ?? '',
    status:               str(j.status)                          ?? 'open',
  })

  tagsInput.value     = (j.tags ?? []).join(', ')
  budgetDisplay.value = j.budget_amount_cents ? j.budget_amount_cents / 100 : null
  step.value          = 0
  form.clearErrors()
  v$.value.$reset()

  // After Vue has updated the DOM with the new form values, force every
  // TomSelect and flatpickr instance in the modal to re-read the underlying
  // element value. This is necessary because TomSelect/flatpickr capture
  // their displayed value at init time and won't react to Vue's reactive
  // writes unless explicitly told to refresh.
  syncEnhancedFields()
}, { immediate: true })

// Also sync when the modal becomes visible — selects are in DOM via v-show
// so TomSelect initialises with blank defaults before the job data arrives
watch(() => props.modelValue, async (open) => {
  if (open) {
    await nextTick()
    scanAndEnhance()
    requestAnimationFrame(() => syncFormEnhancements())
  }
})

watch(step, async () => { await nextTick(); scanAndEnhance(); syncFormEnhancements() })

watch(budgetDisplay, (v) => {
  form.budget_amount_cents = v != null && v !== '' ? Math.round(Number(v) * 100) : null
})

const rules = {
  title:       { required:  helpers.withMessage('Request title is required.', required),
                 maxLength: helpers.withMessage('Title must be 191 characters or fewer.', maxLength(191)) },
  category:    { required:  helpers.withMessage('Please select a service category.', required) },
  description: { required:  helpers.withMessage('Job description is required.', required),
                 minLength: helpers.withMessage('Description must be at least 20 characters.', minLength(20)),
                 maxLength: helpers.withMessage('Description must be 5,000 characters or fewer.', maxLength(5000)) },
}
const v$ = useVuelidate(rules, form, { $scope: false })

function fieldError(field) {
  return form.errors[field] || (v$.value[field]?.$error ? v$.value[field].$errors[0]?.$message : null)
}

function stepClass(i) {
  if (i < step.value) return 'done'
  if (i === step.value) return 'active'
  return 'pending'
}

const stepFields = {
  0: ['title', 'category', 'job_type', 'location_pref', 'start_date', 'description'],
  1: ['experience_level', 'partner_type_pref', 'certifications', 'application_deadline', 'max_applicants'],
  2: ['budget_type', 'budget_amount_cents', 'payment_method', 'billing_frequency', 'perks', 'status'],
}

async function next() {
  const fields = stepFields[step.value] || []
  fields.forEach(f => v$.value[f]?.$touch())
  const valid = fields.every(f => !v$.value[f]?.$invalid)
  if (!valid) return
  step.value++
}

function firstInvalidStep() {
  for (const [stepIndex, fields] of Object.entries(stepFields)) {
    if (fields.some(f => v$.value[f]?.$invalid)) return Number(stepIndex)
  }
  return 0
}

function jumpToFieldStep(fieldName) {
  for (const [stepIndex, fields] of Object.entries(stepFields)) {
    if (fields.includes(fieldName)) { step.value = Number(stepIndex); return }
  }
}

const postedLabel = computed(() => {
  if (!props.job?.posted_at) return '—'
  return new Date(props.job.posted_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
})

const rateUnit = computed(() => ({ hourly: '/hr', retainer: '/mo', fixed: ' total' }[form.budget_type] || ''))

function syncTags() {
  form.tags = tagsInput.value.split(',').map(s => s.trim()).filter(Boolean)
}

function close() { emit('update:modelValue', false) }

function confirmResume() {
  confirmAction(
    { title: 'Reopen Posting', message: `Reopen "${props.job?.title}"? It will become visible to Business Partners again.`, confirmLabel: 'Reopen', destructive: false },
    () => {
      router.post(route('provider.jobs.status', props.job.id), { status: 'open' }, {
        preserveScroll: true,
        onSuccess: () => { toast.success('Posting reopened.'); close() },
        onError:   () => toast.error('Could not reopen posting.'),
      })
    },
  )
}

async function save() {
  syncTags()
  const valid = await v$.value.$validate()
  v$.value.$touch()
  if (!valid) { step.value = firstInvalidStep(); return }
  form.put(route('provider.jobs.update', props.job.id), {
    preserveScroll: true,
    onSuccess: () => { toast.success('Changes saved.'); close() },
    onError:   (errors) => {
      // server error — form.errors will render inline
      const firstErrorField = Object.keys(errors)[0]
      if (firstErrorField) jumpToFieldStep(firstErrorField)
    },
  })
}

function confirmClose() {
  confirmAction(
    { title: 'Close Request', message: 'Close this Support Request? Business Partners will no longer see it.', confirmLabel: 'Close', destructive: true },
    () => {
      router.delete(route('provider.jobs.destroy', props.job.id), {
        preserveScroll: true,
        onSuccess: () => { toast.success('Posting closed.'); close() },
        onError:   () => toast.error('Could not close posting.'),
      })
    },
  )
}
</script>

<style scoped>
.section-divider { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-3); padding: 8px 0 6px; border-bottom: 1px solid var(--border); margin-bottom: 14px; }
.jpl-avail-label { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; cursor:pointer; padding:9px 11px; border:1px solid var(--border); border-radius:var(--radius-sm); transition:all var(--transition); }
.jpl-avail-label:hover { border-color: var(--soft-gold); }
.jpl-selected-green  { border-color: var(--green); background: var(--green-light); }
.jpl-selected-orange { border-color: var(--orange); background: var(--orange-light); }
.jpl-selected-blue   { border-color: var(--blue-dark); background: var(--blue-light); }

/* Footer meta — sits at far left, pushes buttons right via flex layout */
.modal-footer-meta {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--text-3);
  margin-right: auto;   /* pushes everything else to the right */
  min-width: 0;
  overflow: hidden;
  white-space: nowrap;
}
.modal-footer-meta-title {
  font-weight: 600;
  color: var(--text-2);
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}
.modal-footer-meta-sep { color: var(--border-dark); }
</style>
