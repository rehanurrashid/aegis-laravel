<!--
  ProposalModal.vue — BP submits a proposal to a posted job.

  Replaces _shared/modals/proposal_modal.php. Receives the parent job
  via the `job` prop. Fields mirror the schema (cover letter, bid
  cents, estimated weeks, attachments).
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
          :label="job.engagement === 'fixed' ? 'Fixed price' : 'Hourly'"
          variant="gold"
        />
      </header>

      <form @submit.prevent="submit">
        <!-- Bid -->
        <div class="form-group">
          <label class="form-label">
            {{ job.engagement === 'fixed' ? 'Your bid (total)' : 'Your rate (per hour)' }}
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
              :placeholder="job.engagement === 'fixed' ? '2500' : '85'"
            />
            <span v-if="job.engagement !== 'fixed'" class="input-affix-suffix">/hr</span>
          </div>
          <div v-if="form.errors.bid_cents" class="form-error">{{ form.errors.bid_cents }}</div>
        </div>

        <!-- Duration -->
        <div class="form-group">
          <label class="form-label" for="prop-weeks">Estimated duration</label>
          <select id="prop-weeks" v-model="form.estimated_weeks" class="form-input">
            <option :value="1">Less than 1 week</option>
            <option :value="2">1–2 weeks</option>
            <option :value="4">3–4 weeks</option>
            <option :value="8">1–2 months</option>
            <option :value="16">3–4 months</option>
            <option :value="24">5–6 months</option>
            <option :value="52">More than 6 months</option>
          </select>
        </div>

        <!-- Cover letter -->
        <div class="form-group">
          <label class="form-label" for="prop-cover">Cover letter <span class="req">*</span></label>
          <textarea
            id="prop-cover"
            v-model="form.cover_letter"
            class="form-input"
            rows="7"
            placeholder="Why you're the right fit for this role. Be specific — reference the posting and your relevant work."
          ></textarea>
          <div class="form-hint">{{ form.cover_letter.length }} / 2000 characters</div>
          <div v-if="form.errors.cover_letter" class="form-error">{{ form.errors.cover_letter }}</div>
        </div>

        <!-- Attachments -->
        <div class="form-group">
          <label class="form-label">Attachments <span class="form-label-hint">(optional)</span></label>
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

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="form.processing" @click="onClose">
        Cancel
      </button>
      <button
        type="button"
        class="btn btn-primary"
        :disabled="form.processing || !canSubmit"
        @click="submit"
      >{{ form.processing ? 'Submitting…' : 'Submit proposal' }}</button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisDropzone from '@/components/ui/AegisDropzone.vue'

const props = defineProps({
  job: { type: Object, default: null },
})

const { isOpen, closeModal } = useModal()
const toast = useToast()

const bidInput = ref(null)

const form = useForm({
  job_id:          null,
  bid_cents:       null,
  estimated_weeks: 4,
  cover_letter:    '',
  attachments:     [],
})

// Sync job_id when the parent updates the job prop.
watch(() => props.job?.id, (id) => { form.job_id = id ?? null }, { immediate: true })

// Bid input is in dollars; convert to integer cents on the form.
watch(bidInput, (v) => {
  form.bid_cents = v != null && v !== '' ? Math.round(Number(v) * 100) : null
})

const canSubmit = computed(() =>
  form.bid_cents > 0 && form.cover_letter.trim().length > 0 && form.cover_letter.length <= 2000,
)

function onFiles(files) {
  form.attachments = [...form.attachments, ...files]
}

function removeAttachment(i) {
  form.attachments.splice(i, 1)
}

function onUpdateOpen(v) { if (!v) onClose() }

function onClose() {
  closeModal('proposalModal')
  setTimeout(() => {
    form.reset()
    bidInput.value = null
  }, 200)
}

function submit() {
  if (!canSubmit.value) return
  form.post(route('bp.proposals.store'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      toast.success('Proposal submitted.')
      onClose()
    },
    onError: () => toast.error('Could not submit proposal.'),
  })
}
</script>
