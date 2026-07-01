<!--
  HireModal.vue — replaces job-postings.php #hireModal.
  The legacy modal's "Send Offer" terminates in the same place the simple
  Accept button does — accepting the proposal, declining competing proposals,
  and creating a BpContract (ProposalService::accept). This version lets the
  provider confirm/adjust the rate before that happens, then calls the real
  accept route. NDA/BAA checkboxes mirror the job's requires_nda/requires_baa
  flags (display-only — enforcement happens at the BpJob level already).
-->
<template>
  <AegisModal v-model="isOpen" title="Send Hire Offer" size="md" @update:model-value="onUpdateOpen">
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

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="busy" @click="close">Cancel</button>
      <button type="button" class="btn btn-success" :disabled="busy" @click="confirm">
        <AegisIcon name="send" :size="13" />
        {{ busy ? 'Sending…' : 'Send Offer' }}
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
  job:        { type: Object, default: null },
  jobTitle:   { type: String, default: '' },
  stats:      { type: Object, default: () => ({ jobs_done: 0, asking_rate_cents: null }) },
})
const emit = defineEmits(['update:modelValue', 'done'])

const toast = useToast()
const rateDisplay = ref(0)
const message = ref('')
const busy = ref(false)

const isOpen = computed(() => props.modelValue)
function onUpdateOpen(v) { emit('update:modelValue', v) }
function close() { emit('update:modelValue', false) }

const applicantName = computed(() => props.proposal?.bp?.display_name ?? 'this applicant')
const bpTypeLabel = computed(() => {
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

watch(() => props.proposal, (p) => {
  if (!p) return
  rateDisplay.value = p.proposed_rate_cents ? p.proposed_rate_cents / 100 : 0
  message.value = `Hi ${p.bp?.display_name ?? ''}! We'd love to have you join our practice team. We've reviewed your proposal and would like to move forward.`
}, { immediate: true })

function confirm() {
  if (!props.proposal) return
  busy.value = true
  const finalRateCents = rateDisplay.value != null && rateDisplay.value !== '' ? Math.round(Number(rateDisplay.value) * 100) : null
  router.post(route('provider.jobs.proposal.accept', { job: props.proposal.job_id, proposal: props.proposal.id }), { final_rate_cents: finalRateCents }, {
    preserveScroll: true,
    onSuccess: () => { toast.success(`Offer sent to ${applicantName.value}. Contract created.`); busy.value = false; emit('done'); close() },
    onError:   () => { toast.error('Could not send offer.'); busy.value = false },
  })
}
</script>

<style scoped>
.hire-summary { display: flex; gap: 12px; align-items: center; padding: 12px; background: var(--green-light); border-radius: var(--radius-sm); }
</style>
