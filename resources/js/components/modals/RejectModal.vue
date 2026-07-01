<!--
  RejectModal.vue — replaces job-postings.php #rejectModal.
  Wired to provider.jobs.proposal.decline (reason + optional message combined).
-->
<template>
  <AegisModal v-model="isOpen" title="Reject Application" size="sm" @update:model-value="onUpdateOpen">
    <div class="alert alert-danger" style="margin-bottom:14px">
      <div class="alert-icon"><AegisIcon name="alert-triangle" :size="16" /></div>
      <div class="alert-content"><strong>{{ applicantName }}</strong> will be notified that their application was not selected. This cannot be undone.</div>
    </div>
    <div class="form-group">
      <label class="form-label" for="rjReason">Reason for Rejection <span class="required">*</span></label>
      <select id="rjReason" v-model="reason" class="form-select" data-no-enhance :class="{ 'is-error': touched && !reason }" @blur="touched = true">
        <option value="">Select a reason...</option>
        <option v-for="r in reasons" :key="r" :value="r">{{ r }}</option>
      </select>
      <div v-if="touched && !reason" class="form-error">Please select a reason.</div>
    </div>
    <div class="form-group">
      <label class="form-label" for="rjMessage">Message to Applicant (optional)</label>
      <textarea id="rjMessage" v-model="message" class="form-textarea" style="min-height:64px" placeholder="Thank you for applying. We've decided to move forward with another candidate..."></textarea>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="busy" @click="close">Cancel</button>
      <button type="button" class="btn btn-danger" :disabled="busy" @click="confirm">
        <AegisIcon name="x" :size="13" />
        {{ busy ? 'Rejecting…' : 'Reject Application' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  proposal:   { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'done'])

const toast = useToast()
const reason = ref('')
const message = ref('')
const touched = ref(false)
const busy = ref(false)

const reasons = [
  'Not enough experience', 'Rate too high', 'Specialty mismatch',
  'Position already filled', 'Not HIPAA certified', 'Other',
]

const isOpen = computed(() => props.modelValue)
function onUpdateOpen(v) { emit('update:modelValue', v) }
function close() { emit('update:modelValue', false) }
const applicantName = computed(() => props.proposal?.bp?.display_name ?? 'This applicant')

function confirm() {
  touched.value = true
  if (!reason.value) return
  if (!props.proposal) return
  busy.value = true
  const fullReason = message.value ? `${reason.value} — ${message.value}` : reason.value
  router.post(route('provider.jobs.proposal.decline', { job: props.proposal.job_id, proposal: props.proposal.id }), { reason: fullReason }, {
    preserveScroll: true,
    onSuccess: () => { toast.info('Application rejected.'); reset(); emit('done'); close() },
    onError:   () => { toast.error('Could not reject application.'); busy.value = false },
  })
}

function reset() {
  reason.value = ''
  message.value = ''
  touched.value = false
  busy.value = false
}
</script>
