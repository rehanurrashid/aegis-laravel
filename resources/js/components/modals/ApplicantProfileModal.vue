<!--
  ApplicantProfileModal.vue — replaces job-postings.php #applicantDetailModal.
  Central hub for reviewing a proposal: cover letter, real stats (jobs done /
  asking rate — no fabricated ratings since the schema has no reviews table),
  status actions, and private notes (saved via provider.jobs.proposal.notes).
-->
<template>
  <AegisModal v-model="isOpen" title="Applicant Profile" size="xl" @update:model-value="onUpdateOpen">
    <div v-if="proposal" class="modal-sub" style="margin-bottom:14px">Reviewing for: {{ jobTitle }}</div>

    <div v-if="proposal" class="ap-hero">
      <div class="avatar avatar-lg avatar-gold" :style="avatarStyle">
        <template v-if="!proposal.bp?.avatar_url">{{ initials }}</template>
      </div>
      <div style="flex:1;min-width:0">
        <Link v-if="proposal.bp?.slug" :href="route('public.bp', proposal.bp.slug)" target="_blank" class="ap-name ap-name-link">{{ proposal.bp?.display_name ?? 'Business Partner' }}</Link>
        <div v-else class="ap-name">{{ proposal.bp?.display_name ?? 'Business Partner' }}</div>
        <div class="ap-role">{{ jobTitle }} · {{ bpTypeLabel }}</div>
        <div class="ap-meta">
          <span v-if="proposal.bp?.location"><AegisIcon name="map-pin" :size="11" /> {{ proposal.bp.location }}</span>
          <span><AegisIcon name="briefcase" :size="11" /> {{ stats.jobs_done }} job{{ stats.jobs_done === 1 ? '' : 's' }} completed on Aegis</span>
          <span v-if="proposal.bp?.verified"><AegisIcon name="lock" :size="11" /> Verified</span>
        </div>
      </div>
    </div>

    <div v-if="proposal" class="stat-chips-row" style="margin-bottom:18px">
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="credit-card" :size="16" /></div>
        <div><div class="stat-chip-value">{{ formatCents(proposal.proposed_rate_cents) }}</div><div class="stat-chip-label">Proposed Rate</div></div>
      </div>
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="dollar" :size="16" /></div>
        <div><div class="stat-chip-value">{{ stats.asking_rate_cents ? formatCents(stats.asking_rate_cents) + '/hr' : '—' }}</div><div class="stat-chip-label">Standard Rate</div></div>
      </div>
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="briefcase" :size="16" /></div>
        <div><div class="stat-chip-value">{{ stats.jobs_done }}</div><div class="stat-chip-label">Jobs Done</div></div>
      </div>
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="clock" :size="16" /></div>
        <div><div class="stat-chip-value">{{ submittedLabel }}</div><div class="stat-chip-label">Applied</div></div>
      </div>
    </div>

    <div v-if="proposal" class="card" style="padding:18px 20px;margin-bottom:14px">
      <div class="section-title-h" style="margin-bottom:12px"><AegisIcon name="file-text" :size="15" /> Cover Letter / Proposal</div>
      <div class="ap-cover-text">{{ proposal.cover_letter || 'No cover letter provided.' }}</div>
    </div>

    <div v-if="proposal" class="card" style="padding:18px 20px;margin-bottom:14px">
      <div class="section-title-h" style="margin-bottom:12px"><AegisIcon name="menu" :size="15" /> Application Status</div>

      <!-- Terminal state banners -->
      <div v-if="isHired" class="alert alert-success" style="margin-bottom:0">
        <div class="alert-icon"><AegisIcon name="check" :size="16" /></div>
        <div class="alert-content"><strong>{{ proposal.bp?.display_name ?? 'This applicant' }} has been hired.</strong> A contract has been created.</div>
      </div>
      <div v-else-if="isDeclined" class="alert alert-danger" style="margin-bottom:0">
        <div class="alert-icon"><AegisIcon name="x" :size="16" /></div>
        <div class="alert-content">This application was declined.</div>
      </div>

      <!-- Active pipeline actions — hidden once hired or declined -->
      <template v-if="!isTerminal">
        <div class="ap-status-btns" style="margin-bottom:12px">
          <button class="ap-status-btn ap-status-review"    :disabled="busy" @click="$emit('reviewed', proposal)"><AegisIcon name="eye"      :size="12" /> Mark Reviewed</button>
          <button class="ap-status-btn ap-status-shortlist" :disabled="busy" @click="$emit('shortlist', proposal)"><AegisIcon name="star"     :size="12" /> Shortlist</button>
          <button class="ap-status-btn ap-status-schedule"  :disabled="busy" @click="$emit('schedule', proposal)"><AegisIcon name="calendar" :size="12" /> Schedule Interview</button>
          <button class="ap-status-btn ap-status-reject"    :disabled="busy" @click="$emit('reject', proposal)"><AegisIcon name="x"       :size="12" /> Reject</button>
        </div>
      </template>

      <div class="form-group" style="margin-top:4px">
        <label class="form-label" for="apNotes">Private Notes on Applicant</label>
        <textarea id="apNotes" v-model="notes" class="form-textarea" style="min-height:70px" placeholder="Your private notes — great communicator, strong billing background..." @blur="saveNotes"></textarea>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="close">Close</button>
      <template v-if="isHired">
        <span class="badge badge-green" style="padding:6px 12px"><AegisIcon name="check" :size="13" /> Hired</span>
      </template>
      <template v-else-if="isDeclined">
        <span class="badge badge-red" style="padding:6px 12px"><AegisIcon name="x" :size="13" /> Declined</span>
      </template>
      <template v-else>
        <button type="button" class="btn btn-outline" @click="messageApplicant">
          <AegisIcon name="message-square" :size="13" />
          Message
        </button>
        <button type="button" class="btn btn-outline" :disabled="busy" @click="$emit('schedule', proposal)">
          <AegisIcon name="calendar" :size="13" />
          Schedule Interview
        </button>
        <button type="button" class="btn btn-success" :disabled="busy" @click="$emit('hire', proposal)">
          <AegisIcon name="check" :size="13" />
          Hire This Person
        </button>
      </template>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue:          { type: Boolean, default: false },
  proposal:            { type: Object,  default: null },
  jobTitle:            { type: String,  default: '' },
  stats:               { type: Object,  default: () => ({ jobs_done: 0, asking_rate_cents: null }) },
  busy:                { type: Boolean, default: false },
  // Set of proposal IDs that have an active contract — used as a hired fallback
  // when proposal.status is stale (idempotency bug / race condition).
  contractProposalIds: { type: Array,   default: () => [] },
})
const emit = defineEmits(['update:modelValue', 'reviewed', 'shortlist', 'schedule', 'reject', 'hire'])

const toast = useToast()
const notes = ref('')

const isOpen = computed(() => props.modelValue)
function onUpdateOpen(v) { emit('update:modelValue', v) }
function close() { emit('update:modelValue', false) }

// Normalise backed enum — may arrive as {value:'x'} or plain string
const statusVal = computed(() => {
  const s = props.proposal?.status
  return (s && typeof s === 'object' && 'value' in s) ? s.value : (s ?? '')
})
const isHired    = computed(() =>
  statusVal.value === 'accepted' ||
  props.proposal?.pipeline_stage === 'hired' ||
  (props.proposal?.id && props.contractProposalIds.includes(props.proposal.id))
)
const isDeclined = computed(() => statusVal.value === 'declined' || props.proposal?.pipeline_stage === 'rejected')
const isTerminal = computed(() => isHired.value || isDeclined.value)

watch(() => props.proposal, (p) => { notes.value = p?.internal_notes ?? '' }, { immediate: true })

const initials = computed(() => {
  const n = props.proposal?.bp?.display_name
  if (!n) return 'BP'
  return n.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
})
const avatarStyle = computed(() => {
  const url = props.proposal?.bp?.avatar_url
  return url ? { backgroundImage: `url(${url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}
})
const bpTypeLabel = computed(() => {
  const t = props.proposal?.bp?.bp_type
  return t ? t.charAt(0).toUpperCase() + t.slice(1) : 'Business Partner'
})
const submittedLabel = computed(() => {
  if (!props.proposal?.submitted_at) return '—'
  const days = Math.floor((Date.now() - new Date(props.proposal.submitted_at)) / 86400000)
  if (days <= 0) return 'Today'
  return days === 1 ? '1 day ago' : `${days} days ago`
})

function formatCents(cents) {
  if (!cents) return '—'
  return '$' + (cents / 100).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function saveNotes() {
  if (!props.proposal) return
  if (notes.value === (props.proposal.internal_notes ?? '')) return
  router.post(route('provider.jobs.proposal.notes', { job: props.proposal.job_id, proposal: props.proposal.id }), { internal_notes: notes.value }, {
    preserveScroll: true,
    preserveState: true,
    onError: () => toast.error('Could not save notes.'),
  })
}

function messageApplicant() {
  router.visit(route('provider.messages'))
}
</script>

<style scoped>
.ap-hero { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 20px; }
.ap-name { font-family: var(--font-serif); font-size: 22px; font-weight: 700; color: var(--text); }
.ap-name-link { text-decoration: none; cursor: pointer; color: var(--gold-dark); }
.ap-name-link:hover { color: var(--gold-dark); text-decoration: underline; }
.ap-role { font-size: 13px; color: var(--text-2); margin-top: 2px; }
.ap-meta { display: flex; gap: 12px; flex-wrap: wrap; font-size: 12px; color: var(--text-3); margin-top: 8px; }
.ap-meta span { display: flex; align-items: center; gap: 4px; }
.ap-cover-text { font-size: 13px; color: var(--text-2); line-height: 1.7; background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; padding: 14px 16px; white-space: pre-line; }
.ap-status-btns { display: flex; flex-wrap: wrap; gap: 8px; }
.ap-status-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 13px; font-size: 12.5px; font-weight: 600; border-radius: 99px; border: 1px solid; cursor: pointer; transition: all 0.15s; background: var(--surface); }
.ap-status-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.ap-status-review    { color: var(--orange); border-color: var(--orange); }
.ap-status-review:hover:not(:disabled)    { background: var(--orange); color: #fff; }
.ap-status-shortlist  { color: var(--green); border-color: var(--green); }
.ap-status-shortlist:hover:not(:disabled) { background: var(--green); color: #fff; }
.ap-status-schedule   { color: var(--blue-dark); border-color: var(--soft-blue); background: var(--blue-light); }
.ap-status-schedule:hover:not(:disabled)  { background: var(--blue-dark); color: #fff; }
.ap-status-reject     { color: var(--red); border-color: var(--red); }
.ap-status-reject:hover:not(:disabled)    { background: var(--red); color: #fff; }
</style>
