<!--
  StageActionModal.vue — replaces job-postings.php #markReviewedModal and #shortlistModal.
  One component, parametrized by `stage`, since both modals are functionally
  identical (confirm + optional note, then update pipeline_stage).
-->
<template>
  <AegisModal v-model="isOpen" :title="copy.title" size="sm" @update:model-value="onUpdateOpen">
    <div class="alert" :class="copy.alertClass" style="margin-bottom:14px">
      <div class="alert-icon"><AegisIcon :name="copy.icon" :size="16" /></div>
      <div class="alert-content">{{ copy.message(applicantName) }}</div>
    </div>
    <div class="form-group">
      <label class="form-label" for="saNote">{{ copy.noteLabel }}</label>
      <textarea id="saNote" v-model="note" class="form-textarea" style="min-height:64px" :placeholder="copy.notePlaceholder"></textarea>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="busy" @click="close">Cancel</button>
      <button type="button" :class="copy.confirmBtnClass" :disabled="busy" @click="confirm">
        <AegisIcon :name="copy.icon" :size="13" />
        <span v-if="busy" class="spinner spinner-sm" />
        {{ busy ? 'Saving…' : copy.confirmLabel }}
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
  stage:      { type: String, default: 'reviewed' }, // 'reviewed' | 'shortlisted'
})
const emit = defineEmits(['update:modelValue', 'done'])

const toast = useToast()
const note = ref('')
const busy = ref(false)

const isOpen = computed(() => props.modelValue)
function onUpdateOpen(v) { emit('update:modelValue', v) }
function close() { emit('update:modelValue', false) }

const applicantName = computed(() => props.proposal?.bp?.display_name ?? 'This applicant')

const copyMap = {
  reviewed: {
    title: 'Mark as Reviewed',
    icon: 'eye',
    alertClass: 'alert-warning',
    message: (name) => `You're marking ${name}'s application as reviewed. This moves it out of the "New" queue.`,
    noteLabel: 'Quick Note (optional)',
    notePlaceholder: 'e.g., Strong background, follow up next week...',
    confirmLabel: 'Confirm',
    confirmBtnClass: 'btn btn-primary',
    toast: 'Marked as reviewed.',
  },
  shortlisted: {
    title: 'Shortlist Applicant',
    icon: 'star',
    alertClass: 'alert-info',
    message: (name) => `Adding ${name} to your shortlist moves them to the Shortlisted column in the Hiring Pipeline.`,
    noteLabel: 'Shortlist Note (optional)',
    notePlaceholder: 'e.g., Top candidate — strong billing background, HIPAA certified...',
    confirmLabel: 'Shortlist',
    confirmBtnClass: 'btn btn-success',
    toast: 'Added to shortlist.',
  },
}
const copy = computed(() => copyMap[props.stage] || copyMap.reviewed)

function confirm() {
  if (!props.proposal) return
  busy.value = true
  router.post(route('provider.jobs.proposal.stage', { job: props.proposal.job_id, proposal: props.proposal.id }), {
    pipeline_stage: props.stage,
    note: note.value || null,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success(copy.value.toast); note.value = ''; busy.value = false; emit('done'); close() },
    onError:   () => { toast.error('Could not update applicant.'); busy.value = false },
  })
}
</script>
