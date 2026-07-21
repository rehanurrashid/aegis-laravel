<!--
  PostJobModal.vue — Provider "Request Support" 4-step wizard.
  Replaces job-postings.php #postJobModal. Posts to provider.jobs.store.
-->
<template>
  <AegisModal v-model="isOpen" title="Request Support" size="xl" @update:model-value="onUpdateOpen">
    <div class="modal-steps">
      <div class="modal-step" :class="stepClass(0)"><span class="modal-step-num"><AegisIcon v-if="step > 0" name="check" :size="12" /><template v-else>1</template></span>Job Details</div>
      <div class="modal-step-divider"></div>
      <div class="modal-step" :class="stepClass(1)"><span class="modal-step-num"><AegisIcon v-if="step > 1" name="check" :size="12" /><template v-else>2</template></span>Requirements</div>
      <div class="modal-step-divider"></div>
      <div class="modal-step" :class="stepClass(2)"><span class="modal-step-num"><AegisIcon v-if="step > 2" name="check" :size="12" /><template v-else>3</template></span>Compensation</div>
      <div class="modal-step-divider"></div>
      <div class="modal-step" :class="stepClass(3)"><span class="modal-step-num">4</span>Preview</div>
    </div>

    <!-- STEP 1 — Basic info -->
    <div v-show="step === 0">
      <div class="section-divider">Basic Information</div>
      <div class="form-group">
        <label class="form-label" for="pjTitle">Request Title *</label>
        <input id="pjTitle" v-model="form.title" class="form-input" :class="{ 'is-error': fieldError('title') }" maxlength="191" placeholder="e.g., Medical Billing Specialist, HIPAA IT Engineer..." @blur="v$.title.$touch()" />
        <div v-if="fieldError('title')" class="form-error">{{ fieldError('title') }}</div>
      </div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label" for="pjCategory">Service Category *</label>
          <select id="pjCategory" v-model="form.category" class="form-select" :class="{ 'is-error': fieldError('category') }" @blur="v$.category.$touch()">
            <option value="">Select category...</option>
            <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
          </select>
          <div v-if="fieldError('category')" class="form-error">{{ fieldError('category') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="pjType">Job Type</label>
          <select id="pjType" v-model="form.job_type" class="form-select">
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
          <label class="form-label" for="pjLocation">Work Location</label>
          <select id="pjLocation" v-model="form.location_pref" class="form-select">
            <option value="remote">Fully Remote</option>
            <option value="onsite">On-Site</option>
            <option value="hybrid">Hybrid</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="pjStart">Start Date</label>
          <input id="pjStart" v-model="form.start_date" type="date" class="form-input" />
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="pjTags">Tags / Skills (comma-separated)</label>
        <input id="pjTags" v-model="tagsInput" class="form-input" placeholder="e.g., Medical Billing, EHR, Athena, Denial Management" />
      </div>
      <div class="form-group">
        <label class="form-label" for="pjDesc">Job Description *</label>
        <textarea id="pjDesc" v-model="form.description" class="form-textarea" :class="{ 'is-error': fieldError('description') }" rows="5" placeholder="Describe the role, responsibilities, and ideal candidate…" @blur="v$.description.$touch()"></textarea>
        <div v-if="fieldError('description')" class="form-error">{{ fieldError('description') }}</div>
        <div class="form-hint">{{ form.description.length }} / 5000 characters</div>
      </div>
    </div>

    <!-- STEP 2 — Requirements -->
    <div v-show="step === 1">
      <div class="section-divider">Candidate Requirements</div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label" for="pjExp">Experience Level</label>
          <select id="pjExp" v-model="form.experience_level" class="form-select">
            <option value="">Any level</option>
            <option value="entry">Entry Level (1–3 years)</option>
            <option value="mid">Intermediate (3–7 years)</option>
            <option value="senior">Senior (7–15 years)</option>
            <option value="expert">Expert (15+ years)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="pjPartnerType">Partner Type Preference</label>
          <select id="pjPartnerType" v-model="form.partner_type_pref" class="form-select">
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
        <label class="form-label" for="pjCerts">Required Certifications / Credentials</label>
        <input id="pjCerts" v-model="form.certifications" class="form-input" placeholder="e.g., CPC, AHIMA, CPA, HIPAA certified, CompTIA Security+..." />
      </div>
      <div class="section-divider" style="margin-top:14px">Special Requirements</div>
      <div class="form-row form-row-3" style="margin-bottom:14px">
        <label class="jpl-avail-label" :class="{ 'jpl-selected-green': form.requires_hipaa }">
          <input v-model="form.requires_hipaa" type="checkbox" style="accent-color: var(--gold-dark)" />
          <AegisIcon name="lock" :size="13" />
          <span>HIPAA</span>
        </label>
        <label class="jpl-avail-label" :class="{ 'jpl-selected-orange': form.requires_nda }">
          <input v-model="form.requires_nda" type="checkbox" style="accent-color: var(--gold-dark)" />
          <AegisIcon name="file-text" :size="13" />
          <span>NDA</span>
        </label>
        <label class="jpl-avail-label" :class="{ 'jpl-selected-blue': form.requires_baa }">
          <input v-model="form.requires_baa" type="checkbox" style="accent-color: var(--gold-dark)" />
          <AegisIcon name="shield" :size="13" />
          <span>BAA</span>
        </label>
      </div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label" for="pjDeadline">Application Deadline (optional)</label>
          <input id="pjDeadline" v-model="form.application_deadline" type="date" class="form-input" :class="{ 'is-error': fieldError('application_deadline') }" />
          <div v-if="fieldError('application_deadline')" class="form-error">{{ fieldError('application_deadline') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="pjMaxApps">Max Applicants to Accept</label>
          <select id="pjMaxApps" v-model.number="form.max_applicants" class="form-select">
            <option :value="0">No limit</option>
            <option :value="10">10 applicants</option>
            <option :value="25">25 applicants</option>
            <option :value="50">50 applicants</option>
          </select>
        </div>
      </div>
    </div>

    <!-- STEP 3 — Compensation -->
    <div v-show="step === 2">
      <div class="section-divider">Budget &amp; Compensation</div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label" for="pjBudgetType">Budget Type</label>
          <select id="pjBudgetType" v-model="form.budget_type" class="form-select">
            <option value="hourly">Hourly Rate</option>
            <option value="retainer">Monthly Retainer</option>
            <option value="fixed">Fixed Price (Project)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="pjBudgetAmount">{{ budgetLabel }}</label>
          <div class="input-suffix-wrap">
            <input id="pjBudgetAmount" v-model="budgetDisplay" type="number" min="0" step="0.01" class="form-input" :class="{ 'is-error': fieldError('budget_amount_cents') }" placeholder="0.00" />
            <span class="input-suffix">{{ budgetUnit }}</span>
          </div>
          <div v-if="fieldError('budget_amount_cents')" class="form-error">{{ fieldError('budget_amount_cents') }}</div>
        </div>
      </div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label" for="pjPayment">Payment Method</label>
          <select id="pjPayment" v-model="form.payment_method" class="form-select">
            <option>Direct Bank Transfer</option>
            <option>Check</option>
            <option>Zelle / Venmo Business</option>
            <option>Net-30 Invoice</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="pjBilling">Billing Frequency</label>
          <select id="pjBilling" v-model="form.billing_frequency" class="form-select">
            <option>Upon completion</option>
            <option>Weekly</option>
            <option>Bi-weekly</option>
            <option>Monthly</option>
            <option>Milestone-based</option>
          </select>
        </div>
      </div>
      <div class="form-group" style="margin-top:14px">
        <label class="form-label" for="pjPerks">Additional Perks / Incentives (optional)</label>
        <input id="pjPerks" v-model="form.perks" class="form-input" placeholder="e.g., performance bonuses, long-term contract guarantee, flexible hours..." />
      </div>

      <!-- Rev 2: Default Payment Terms panel -->
      <div class="form-group form-section-divider">
        <div class="form-section-label">
          <AegisIcon name="credit-card" :size="13" />
          Default Payment Terms
        </div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Payment Structure</label>
            <select v-model="form.default_payment_structure" class="form-select">
              <option value="per_milestone">Per Milestone</option>
              <option value="full_upfront">100% Upfront</option>
              <option value="split">Split (upfront + completion)</option>
              <option value="on_completion">Pay on Completion</option>
            </select>
          </div>
          <div v-if="form.default_payment_structure === 'split'" class="form-group">
            <label class="form-label">Upfront Percentage</label>
            <div class="form-input-suffix-wrap">
              <input
                v-model.number="form.default_upfront_percentage"
                type="number" min="1" max="99" class="form-input"
                placeholder="30"
              />
              <span class="form-input-suffix">%</span>
            </div>
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0;padding-bottom:0">
          <label class="form-label">Payment Terms Note (optional)</label>
          <textarea
            v-model="form.default_terms_note"
            class="form-textarea" rows="2"
            placeholder="Any payment conditions, timelines, or special agreements BPs should know…"
            maxlength="5000"
          />
        </div>

      </div>

    </div>

    <!-- STEP 4 — Preview -->
    <div v-show="step === 3">
      <div class="section-divider">Preview Your Support Request</div>
      <div class="alert alert-success">
        <div class="alert-icon"><AegisIcon name="check" :size="16" /></div>
        <div class="alert-content">Your support request looks great! It will be visible to all Business Partners on Aegis as soon as you publish.</div>
      </div>
      <div class="jp-card" style="margin-bottom:14px;cursor:default;transform:none">
        <div class="jp-card-header">
          <div class="jp-card-logo" :style="practiceAvatarStyle">
            <template v-if="!page.props.auth?.user?.avatar_url">{{ practiceInitials }}</template>
          </div>
          <div>
            <div class="jp-card-title">{{ form.title || 'Untitled request' }}</div>
            <div class="jp-card-practice">{{ practiceName }}</div>
          </div>
        </div>
        <div class="jp-card-meta">
          <span><AegisIcon name="globe" :size="11" /> {{ locationLabel }}</span>
          <span v-if="form.job_type"><AegisIcon name="briefcase" :size="11" /> {{ jobTypeLabel }}</span>
          <span v-if="form.requires_hipaa"><AegisIcon name="lock" :size="11" /> HIPAA Required</span>
        </div>
        <div v-if="tagsInput" class="jp-card-tags">
          <span v-for="t in tagsInput.split(',').map(s => s.trim()).filter(Boolean)" :key="t" class="chip">{{ t }}</span>
        </div>
        <div class="jp-budget-badge" style="margin-top:6px">{{ budgetPreview }}</div>
        <div class="jp-card-desc" style="margin-top:8px">{{ form.description }}</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="pjNotes">Internal Job Notes (not shown to applicants)</label>
        <textarea id="pjNotes" v-model="form.internal_notes" class="form-textarea" rows="2" placeholder="Your private notes — why you're hiring, what red flags to watch for, preferred rate range..."></textarea>
      </div>
    </div>

    <template #footer>
      <button v-if="step > 0" type="button" class="btn btn-outline" :disabled="form.processing" @click="step--">
        <AegisIcon name="arrow-left" :size="13" />
        Back
      </button>
      <button type="button" class="btn btn-outline" :disabled="form.processing" @click="saveDraft">
        <AegisIcon name="save" :size="13" />
        Save Draft
      </button>
      <button v-if="step < 3" type="button" class="btn btn-primary" :disabled="form.processing" @click="next">
        Next Step
        <AegisIcon name="arrow-right-line" :size="13" />
      </button>
      <button v-else type="button" class="btn btn-primary" :disabled="form.processing" @click="publish">
        {{ form.processing ? 'Publishing…' : 'Publish Request' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import useVuelidate from '@vuelidate/core'
import { required, minLength, maxLength, integer, minValue, helpers } from '@vuelidate/validators'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { scanAndEnhance, syncFormEnhancements } from '@/plugins/FormEnhancerPlugin'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  prefill:    { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue'])

const toast = useToast()
const step = ref(0)

const isOpen = computed(() => props.modelValue)
function onUpdateOpen(v) { emit('update:modelValue', v) }

// On open: scan to init step-0 selects; on step change: scan to init newly visible selects
watch(isOpen, async (val) => {
  if (val) { await nextTick(); scanAndEnhance() }
})
watch(step, async () => { await nextTick(); scanAndEnhance(); syncFormEnhancements() })
const tagsInput = ref('')
const budgetDisplay = ref(null)

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
    // Rev 2 — default payment terms
    default_payment_structure:  'per_milestone',
    default_upfront_percentage: 30,
    default_terms_note:         '',
  }
}

const form = useForm(blankForm())

watch(() => props.prefill, (p) => {
  if (!p) return
  form.title = p.title ?? form.title
  form.category = p.category ?? form.category
  form.description = p.description ?? form.description
  step.value = 0
}, { immediate: true })

watch(budgetDisplay, (v) => {
  form.budget_amount_cents = v != null && v !== '' ? Math.round(Number(v) * 100) : null
})

const rules = {
  title:                { required:    helpers.withMessage('Request title is required.', required),
                          maxLength:   helpers.withMessage('Title must be 191 characters or fewer.', maxLength(191)) },
  category:             { required:    helpers.withMessage('Please select a service category.', required) },
  description:          { required:    helpers.withMessage('Job description is required.', required),
                          minLength:   helpers.withMessage('Description must be at least 20 characters.', minLength(20)),
                          maxLength:   helpers.withMessage('Description must be 5,000 characters or fewer.', maxLength(5000)) },
  budget_amount_cents:  { integer:     helpers.withMessage('Budget must be a whole number.', integer),
                          minValue:    helpers.withMessage('Budget cannot be negative.', minValue(0)) },
  application_deadline: { future:      helpers.withMessage('Deadline must be a future date.', (v) => !v || new Date(v) > new Date()) },
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
  2: ['budget_type', 'budget_amount_cents', 'payment_method', 'billing_frequency', 'perks'],
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
const budgetLabel = computed(() => form.budget_type === 'retainer' ? 'Monthly Rate ($)' : 'Budget ($)')
const budgetUnit = computed(() => ({ hourly: '/hr', retainer: '/mo', fixed: ' total' }[form.budget_type] || ''))
const locationLabel = computed(() => ({ remote: 'Remote', onsite: 'On-Site', hybrid: 'Hybrid' }[form.location_pref] || 'Remote'))
const jobTypeLabel = computed(() => ({
  one_time: 'One-Time Project', ongoing: 'Ongoing', contract: 'Fixed-Price', part_time: 'Part-Time', full_time: 'Full-Time',
}[form.job_type] || ''))
const page = usePage()
const practiceInitials = computed(() => page.props.auth?.user?.avatar_initials || (page.props.auth?.user?.display_name ?? 'P').slice(0, 2).toUpperCase())
const practiceName = computed(() => page.props.auth?.user?.display_name || '')
const practiceAvatarStyle = computed(() => {
  const url = page.props.auth?.user?.avatar_url
  return url ? { backgroundImage: `url(${url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}
})
const budgetPreview = computed(() => {
  if (!form.budget_amount_cents) return 'Budget TBD'
  return '$' + (form.budget_amount_cents / 100).toLocaleString() + budgetUnit.value
})



function syncTags() {
  form.tags = tagsInput.value.split(',').map(s => s.trim()).filter(Boolean)
}

function resetAll() {
  Object.assign(form, blankForm())
  form.clearErrors()
  v$.value.$reset()
  step.value = 0
  tagsInput.value = ''
  budgetDisplay.value = null
}

function close() {
  emit('update:modelValue', false)
}

function saveDraft() {
  syncTags()
  form.status = 'draft'
  form.post(route('provider.jobs.store'), {
    preserveScroll: true,
    onSuccess: () => { toast.success('Draft saved.'); resetAll(); close() },
    onError:   () => toast.error('Could not save draft.'),
  })
}

async function publish() {
  syncTags()
  const valid = await v$.value.$validate()
  v$.value.$touch()
  if (!valid) { step.value = firstInvalidStep(); return }
  form.status = 'open'
  form.post(route('provider.jobs.store'), {
    preserveScroll: true,
    onSuccess: () => { toast.success('Support request posted.'); resetAll(); close() },
    onError:   (errors) => {
      // server error renders via form.errors inline
      const firstErrorField = Object.keys(errors)[0]
      if (firstErrorField) jumpToFieldStep(firstErrorField)
    },
  })
}
</script>

<style scoped>
.section-divider { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-3); padding: 8px 0 6px; border-bottom: 1px solid var(--border); margin-bottom: 14px; }

.jp-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 18px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 10px; }
.jp-card-header { display: flex; align-items: flex-start; gap: 12px; }
.jp-card-logo { width: 44px; height: 44px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; background: var(--badge-bg-gold); color: var(--gold-dark); font-weight: 700; }
.jp-card-title { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 2px; line-height: 1.3; }
.jp-card-practice { font-size: 12px; color: var(--text-2); }
.jp-budget-badge { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--green); }
.jp-card-meta { display: flex; gap: 10px; flex-wrap: wrap; font-size: 11px; color: var(--text-3); }
.jp-card-meta span { display: flex; align-items: center; gap: 3px; }
.jp-card-tags { display: flex; gap: 5px; flex-wrap: wrap; }
.jp-card-desc { font-size: 12px; color: var(--text-2); line-height: 1.55; }

.jpl-avail-label { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; cursor:pointer; padding:9px 11px; border: 1px solid var(--border); border-radius:var(--radius-sm); transition:all var(--transition); }
.jpl-avail-label:hover { border-color: var(--soft-gold); }
.jpl-selected-green  { border-color: var(--green); background: var(--green-light); }
.jpl-selected-orange { border-color: var(--orange); background: var(--orange-light); }
.jpl-selected-blue   { border-color: var(--blue-dark); background: var(--blue-light); }


/* ── Rev 2: Default Payment Terms panel ─────────────────────────── */
.form-section-divider {
  border-top: 1px solid var(--border);
  padding-top: 16px;
  margin-top: 4px;
}
.form-section-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-family: var(--font-sans);
  font-size: 12px;
  font-weight: 700;
  color: var(--text-3);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 12px;
}
.form-input-suffix-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.form-input-suffix-wrap .form-input {
  padding-right: 32px;
}
.form-input-suffix {
  position: absolute;
  right: 10px;
  font-size: 13px;
  color: var(--text-4);
  pointer-events: none;
}
</style>
