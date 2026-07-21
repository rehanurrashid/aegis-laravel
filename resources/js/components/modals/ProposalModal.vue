<!--
  ProposalModal.vue — BP submits a proposal to a posted job.

  Wave 1 fixes:
  - Route: bp.proposals.store (non-existent) → bp.jobs.propose (correct route with {job} param)
  - Field name: bid_cents → proposed_rate_cents (matches SubmitProposalRequest)
  - Field name: estimated_weeks → timeline_days (matches SubmitProposalRequest)
  - Added portfolio_url field (SubmitProposalRequest supports it, was missing from UI)
  - Added Vuelidate on required fields (was missing entirely)
  - AegisDropzone import kept (not globally registered) — attachments stored in
    bp_proposal_attachments table (Wave 1 migration 4); server-side wiring in Wave 4
-->
<template>
  <AegisModal
    :model-value="isOpen('proposalModal').value"
    title="Submit a proposal"
    size="lg"
    @update:model-value="onUpdateOpen"
  >
    <div v-if="job" class="proposal-modal">
      <!-- Job summary header -->
      <header class="proposal-job-summary">
        <div>
          <div class="proposal-job-eyebrow">Proposing on</div>
          <div class="proposal-job-title">{{ job.title }}</div>
          <div class="proposal-job-poster">{{ job.poster_name }}</div>
        </div>
        <AegisBadge
          :label="job.engagement === 'fixed' ? 'Fixed price' : job.engagement === 'hourly' ? 'Hourly' : 'Retainer'"
          variant="gold"
        />
      </header>

      <!-- HIPAA / NDA / BAA disclosure -->
      <div
        v-if="job.requires_hipaa || job.requires_nda || job.requires_baa"
        class="proposal-compliance-notice"
      >
        <AegisIcon name="shield-check" :size="14" />
        <span>
          This role requires:
          <strong v-if="job.requires_hipaa">HIPAA compliance</strong>
          <span v-if="job.requires_hipaa && (job.requires_nda || job.requires_baa)"> · </span>
          <strong v-if="job.requires_nda">NDA</strong>
          <span v-if="job.requires_nda && job.requires_baa"> · </span>
          <strong v-if="job.requires_baa">BAA</strong>.
          By submitting, you confirm you meet these requirements.
        </span>
      </div>

      <form @submit.prevent="submit">
        <!-- Bid -->
        <div class="form-group">
          <label class="form-label">
            {{ job.engagement === 'fixed' ? 'Your bid (total project)' : job.engagement === 'hourly' ? 'Your hourly rate' : 'Monthly retainer' }}
            <span class="req">*</span>
          </label>
          <div class="input-affix">
            <span class="input-affix-prefix">$</span>
            <input
              v-model.number="bidInput"
              type="number"
              min="1"
              step="1"
              class="form-input"
              :class="{ 'is-error': fieldError('proposed_rate_cents') }"
              :placeholder="job.engagement === 'fixed' ? '2500' : '85'"
              @blur="v$.proposed_rate_cents.$touch()"
            />
            <span v-if="job.engagement === 'hourly'" class="input-affix-suffix">/hr</span>
            <span v-else-if="job.engagement === 'retainer'" class="input-affix-suffix">/mo</span>
          </div>
          <div v-if="fieldError('proposed_rate_cents')" class="form-error">{{ fieldError('proposed_rate_cents') }}</div>
        </div>

        <!-- Timeline -->
        <div class="form-group">
          <label class="form-label" for="prop-timeline">Estimated duration <span class="req">*</span></label>
          <select
            id="prop-timeline"
            v-model.number="form.timeline_days"
            class="form-select"
            :class="{ 'is-error': fieldError('timeline_days') }"
            @blur="v$.timeline_days.$touch()"
          >
            <option :value="null" disabled>Select duration…</option>
            <option :value="7">Less than 1 week</option>
            <option :value="14">1–2 weeks</option>
            <option :value="30">3–4 weeks</option>
            <option :value="60">1–2 months</option>
            <option :value="120">3–4 months</option>
            <option :value="180">5–6 months</option>
            <option :value="365">More than 6 months</option>
          </select>
          <div v-if="fieldError('timeline_days')" class="form-error">{{ fieldError('timeline_days') }}</div>
        </div>

        <!-- Portfolio URL -->
        <div class="form-group">
          <label class="form-label" for="prop-portfolio">
            Portfolio / work samples URL <span class="form-label-hint">(optional)</span>
          </label>
          <input
            id="prop-portfolio"
            v-model="form.portfolio_url"
            type="url"
            class="form-input"
            :class="{ 'is-error': fieldError('portfolio_url') }"
            placeholder="https://yourportfolio.com"
            @blur="v$.portfolio_url.$touch()"
          />
          <div v-if="fieldError('portfolio_url')" class="form-error">{{ fieldError('portfolio_url') }}</div>
        </div>

        <!-- Cover letter -->
        <div class="form-group">
          <label class="form-label" for="prop-cover">
            Cover letter <span class="req">*</span>
          </label>
          <textarea
            id="prop-cover"
            v-model="form.cover_letter"
            class="form-textarea"
            :class="{ 'is-error': fieldError('cover_letter') }"
            rows="7"
            placeholder="Why you're the right fit for this role. Be specific — reference the posting and your relevant work."
            @blur="v$.cover_letter.$touch()"
          />
          <div class="form-hint">{{ form.cover_letter.length }} / 3000 characters</div>
          <div v-if="fieldError('cover_letter')" class="form-error">{{ fieldError('cover_letter') }}</div>
        </div>

        <!-- Attachments (stored in bp_proposal_attachments; server wiring in Wave 4) -->
        <div class="form-group">
          <label class="form-label">
            Attachments <span class="form-label-hint">(optional — PDF, DOC, or images)</span>
          </label>
          <AegisDropzone
            multiple
            accept=".pdf,.doc,.docx,image/*"
            hint="PDF, DOC, or images up to 25 MB each"
            @files="onFiles"
          />
          <ul v-if="form.attachments.length" class="proposal-attachment-list">
            <li v-for="(file, i) in form.attachments" :key="i" class="proposal-attachment">
              <AegisIcon name="paperclip" :size="13" />
              <span class="proposal-attachment-name">{{ file.name }}</span>
              <button
                type="button"
                class="proposal-attachment-remove"
                aria-label="Remove"
                @click="removeAttachment(i)"
              >
                <AegisIcon name="x" :size="11" />
              </button>
            </li>
          </ul>
        </div>
      </form>
    </div>

    <!-- Rev 2: Payment terms selection + disclosure -->
    <div v-if="job" style="padding: 0 20px 16px">
      <PaymentTermsInline
        :provider-defaults="{
          structure:         job.default_payment_structure ?? 'per_milestone',
          upfrontPercentage: job.default_upfront_percentage ?? 30,
          termsNote:         job.default_terms_note,
          allowCompletionOnly: false,
        }"
        :model-value="{
          structure:         form.proposed_payment_structure,
          upfrontPercentage: form.proposed_upfront_percentage,
          termsNote:         form.proposed_terms_note,
          termsSource:       form.terms_source,
        }"
        :allowed-structures="['full_upfront','split','per_milestone','on_completion']"
        @update:model-value="v => {
          form.proposed_payment_structure  = v.structure
          form.proposed_upfront_percentage = v.upfrontPercentage
          form.proposed_terms_note         = v.termsNote
          form.terms_source                = v.termsSource
        }"
      />

      <!-- Agreement checkbox -->
      <label class="proposal-agree-check">
        <input v-model="form.agree_terms" type="checkbox" />
        I agree to these payment terms. Payment routes directly from the provider to my Stripe Connect account. Aegis is not the paymaster.
      </label>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="form.processing" @click="onClose">
        Cancel
      </button>
      <button
        type="button"
        class="btn btn-primary"
        :disabled="form.processing || !canSubmit"
        @click="submit"
      >
        <span v-if="form.processing" class="spinner spinner-sm" />
        {{ form.processing ? 'Submitting…' : 'Submit proposal' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm }  from '@inertiajs/vue3'
import useVuelidate from '@vuelidate/core'
import { required, minLength, maxLength, minValue, url } from '@vuelidate/validators'
import PaymentTermsInline from '@/components/ui/PaymentTermsInline.vue'
import AegisDropzone      from '@/components/ui/AegisDropzone.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import AegisBadge   from '@/components/ui/AegisBadge.vue'
import AegisDropzone from '@/components/ui/AegisDropzone.vue'

const props = defineProps({
  job: { type: Object, default: null },
})

const { isOpen, closeModal } = useModal()
const toast = useToast()

const bidInput = ref(null)

// useForm at top-level (required by platform rules)
const form = useForm({
  job_id:              null,
  proposed_rate_cents: null,   // FIXED: was bid_cents
  timeline_days:       null,   // FIXED: was estimated_weeks
  portfolio_url:       '',
  cover_letter:        '',
  attachments:         [],
  // Rev 2 — payment terms
  proposed_payment_structure:  '',
  proposed_upfront_percentage: 30,
  proposed_terms_note:         '',
  terms_source:                'provider_default',
  agree_terms:                 false,
})

// Sync job_id when parent updates job prop
watch(() => props.job?.id, (id) => { form.job_id = id ?? null }, { immediate: true })

// Sync payment terms defaults from job when job changes (Rev 2)
watch(() => props.job, (j) => {
  if (!j) return
  form.proposed_payment_structure  = j.default_payment_structure ?? 'per_milestone'
  form.proposed_upfront_percentage = j.default_upfront_percentage ?? 30
  form.proposed_terms_note         = j.default_terms_note ?? ''
  form.terms_source                = 'provider_default'
}, { immediate: true })

// Dollar → cents
watch(bidInput, (v) => {
  form.proposed_rate_cents = v != null && v !== '' ? Math.round(Number(v) * 100) : null
})

// ── Vuelidate ───────────────────────────────────────────────────────────────
const rules = {
  proposed_rate_cents: { required, minValue: minValue(1) },
  timeline_days:       { required },
  portfolio_url:       {},
  cover_letter:        { required, minLength: minLength(50), maxLength: maxLength(3000) },
}
const v$ = useVuelidate(rules, form, { $scope: false })

function fieldError(f) {
  const e = v$.value[f]?.$errors
  return e?.length ? e[0].$message : null
}

const canSubmit = computed(() =>
  (form.proposed_rate_cents ?? 0) > 0 &&
  form.timeline_days != null &&
  form.cover_letter.trim().length >= 50 &&
  form.cover_letter.length <= 3000 &&
  form.agree_terms === true,
)

// ── Attachments ─────────────────────────────────────────────────────────────
function onFiles(files)         { form.attachments = [...form.attachments, ...files] }
function removeAttachment(i)    { form.attachments.splice(i, 1) }

// ── Modal lifecycle ─────────────────────────────────────────────────────────
function onUpdateOpen(v) { if (!v) onClose() }

function onClose() {
  closeModal('proposalModal')
  setTimeout(() => {
    form.reset()
    bidInput.value = null
    v$.value.$reset()
  }, 200)
}

// ── Submit ──────────────────────────────────────────────────────────────────
async function submit() {
  const valid = await v$.value.$validate()
  if (!valid) return

  // FIXED route: bp.jobs.propose with {job} param (not bp.proposals.store)
  form.post(route('bp.jobs.propose', { job: form.job_id }), {
    preserveScroll: true,
    forceFormData:  true,   // needed for file attachments
    onSuccess: () => {
      toast.success('Proposal submitted.')
      onClose()
    },
    onError: () => toast.error('Could not submit proposal. Check the form for errors.'),
  })
}
</script>

<style scoped>
.proposal-agree-check {
  display: flex; align-items: flex-start; gap: 10px;
  margin-top: 12px;
  font-size: 12px; color: var(--text-3);
  cursor: pointer; line-height: 1.5;
}
.proposal-agree-check input { flex-shrink: 0; margin-top: 2px; }
</style>
