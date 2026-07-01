<!--
  ScheduleInterviewModal.vue — replaces job-postings.php #scheduleInterviewModal.
  Sets pipeline_stage='interview' with interview_type/interview_at via
  provider.jobs.proposal.stage, and (notify_applicant: true) sends a real
  message to the Business Partner via the messaging system with the date,
  type, and any notes.
-->
<template>
  <AegisModal v-model="isOpen" title="Schedule Interview" size="sm" @update:model-value="onUpdateOpen">
    <div v-if="proposal" class="modal-sub" style="margin-bottom:14px">With {{ applicantName }} · {{ jobTitle }}</div>

    <div class="form-group">
      <label class="form-label">Interview Type</label>
      <div class="jp-chip-group">
        <div v-for="t in types" :key="t.value" class="jp-chip" :class="{ active: form.interview_type === t.value }" @click="form.interview_type = t.value">{{ t.label }}</div>
      </div>
    </div>
    <div class="form-row form-row-2">
      <div class="form-group">
        <label class="form-label" for="siDate">Date</label>
        <input id="siDate" v-model="date" type="date" class="form-input" :class="{ 'is-error': touched && !date }" />
      </div>
      <div class="form-group">
        <label class="form-label" for="siTime">Time</label>
        <input id="siTime" v-model="time" type="time" class="form-input" />
      </div>
    </div>
    <div class="form-row form-row-2">
      <div class="form-group">
        <label class="form-label" for="siDuration">Duration</label>
        <select id="siDuration" v-model="duration" class="form-select" data-no-enhance>
          <option value="15">15 minutes</option>
          <option value="30">30 minutes</option>
          <option value="45">45 minutes</option>
          <option value="60">1 hour</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="siLink">Video Link (optional)</label>
        <input id="siLink" v-model="videoLink" class="form-input" placeholder="Zoom, Google Meet, Teams..." />
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="siNotes">Notes to Applicant</label>
      <textarea id="siNotes" v-model="note" class="form-textarea" style="min-height:70px" placeholder="What to prepare, what to expect..."></textarea>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="busy" @click="close">Cancel</button>
      <button type="button" class="btn btn-primary" :disabled="busy" @click="confirm">
        <AegisIcon name="send" :size="13" />
        {{ busy ? 'Sending…' : 'Send Invite' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  proposal:   { type: Object, default: null },
  jobTitle:   { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue', 'done'])

const toast = useToast()
const types = [
  { value: 'video', label: 'Video Call' },
  { value: 'phone', label: 'Phone Call' },
  { value: 'in_person', label: 'In-Person' },
]
const form = ref({ interview_type: 'video' })
const date = ref('')
const time = ref('10:00')
const duration = ref('30')
const videoLink = ref('')
const note = ref('')
const touched = ref(false)
const busy = ref(false)

const isOpen = computed(() => props.modelValue)
function onUpdateOpen(v) { emit('update:modelValue', v) }
function close() { emit('update:modelValue', false) }
const applicantName = computed(() => props.proposal?.bp?.display_name ?? 'this applicant')

watch(() => props.modelValue, (v) => {
  if (v) {
    const today = new Date()
    today.setDate(today.getDate() + 2)
    date.value = today.toISOString().slice(0, 10)
  }
})

function confirm() {
  touched.value = true
  if (!date.value || !props.proposal) return
  busy.value = true
  const interviewAt = `${date.value} ${time.value}:00`
  const noteParts = [`Duration: ${duration.value} min`]
  if (videoLink.value) noteParts.push(`Link: ${videoLink.value}`)
  if (note.value) noteParts.push(note.value)

  router.post(route('provider.jobs.proposal.stage', { job: props.proposal.job_id, proposal: props.proposal.id }), {
    pipeline_stage: 'interview',
    interview_type: form.value.interview_type,
    interview_at: interviewAt,
    note: noteParts.join(' — '),
    notify_applicant: true,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Interview scheduled. Applicant notified.'); reset(); emit('done'); close() },
    onError:   () => { toast.error('Could not schedule interview.'); busy.value = false },
  })
}

function reset() {
  videoLink.value = ''
  note.value = ''
  touched.value = false
  busy.value = false
}
</script>

<style scoped>
.jp-chip-group { display: flex; flex-wrap: wrap; gap: 5px; }
.jp-chip {
  padding: 4px 10px; border: 1px solid var(--border);
  border-radius: var(--radius-full); font-size: 11px; font-weight: 600;
  color: var(--text-2); cursor: pointer; transition: all var(--transition); white-space: nowrap;
}
.jp-chip:hover { border-color: var(--gold-dark); color: var(--gold-dark); }
.jp-chip.active { background: var(--gold-dark); border-color: var(--gold-dark); color: var(--text-inverted); }
</style>
