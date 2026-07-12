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

    <div v-if="proposal" class="ap-status-card">
      <div class="ap-status-card-header">
        <div class="ap-status-card-title">
          <AegisIcon name="menu" :size="14" />
          Application Status
        </div>
        <!-- Current stage pill -->
        <span v-if="!isTerminal" class="ap-stage-pill" :class="stagePillClass">{{ currentStageLabel }}</span>
      </div>

      <!-- Terminal state banners -->
      <div v-if="isHired" class="ap-terminal ap-terminal-hired">
        <div class="ap-terminal-icon"><AegisIcon name="check-circle" :size="18" /></div>
        <div>
          <div class="ap-terminal-title">{{ proposal.bp?.display_name ?? 'This applicant' }} has been hired</div>
          <div class="ap-terminal-sub">A contract has been created and is ready to sign.</div>
        </div>
      </div>
      <div v-else-if="isDeclined" class="ap-terminal ap-terminal-declined">
        <div class="ap-terminal-icon"><AegisIcon name="x-circle" :size="18" /></div>
        <div>
          <div class="ap-terminal-title">Application declined</div>
          <div class="ap-terminal-sub">This proposal was not accepted. You can still message the applicant.</div>
        </div>
      </div>

      <!-- Active pipeline actions -->
      <template v-if="!isTerminal">
        <div class="ap-pipeline-stages">
          <button
            v-for="act in pipelineActions"
            :key="act.key"
            class="ap-pipeline-btn"
            :class="act.cls"
            :disabled="busy"
            @click="$emit(act.emit, proposal)"
          >
            <div class="ap-pipeline-btn-icon"><AegisIcon :name="act.icon" :size="14" /></div>
            <div class="ap-pipeline-btn-label">{{ act.label }}</div>
          </button>
        </div>
      </template>

      <div class="ap-notes-wrap">
        <label class="form-label" for="apNotes">Private Notes on Applicant</label>
        <textarea id="apNotes" v-model="notes" class="form-textarea" style="min-height:70px" placeholder="Your private notes — great communicator, strong billing background..." @blur="saveNotes" />
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
        <button type="button" class="btn btn-outline" :disabled="msgLoading === props.proposal?.bp?.id" @click="messageApplicant">
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
import { useMessageButton } from '@/composables/useMessageButton'

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
const { openConversation, loading: msgLoading } = useMessageButton()
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

const currentStageLabel = computed(() => {
  const stage = props.proposal?.pipeline_stage
  return { new: 'New', reviewed: 'Reviewed', shortlisted: 'Shortlisted', interview: 'Interview Scheduled' }[stage] ?? 'New'
})
const stagePillClass = computed(() => {
  const stage = props.proposal?.pipeline_stage
  return { new: 'is-new', reviewed: 'is-reviewed', shortlisted: 'is-shortlisted', interview: 'is-interview' }[stage] ?? 'is-new'
})

const pipelineActions = [
  { key: 'review',    emit: 'reviewed',  icon: 'eye',      label: 'Mark Reviewed',     cls: 'is-review' },
  { key: 'shortlist', emit: 'shortlist', icon: 'star',     label: 'Shortlist',          cls: 'is-shortlist' },
  { key: 'schedule',  emit: 'schedule',  icon: 'calendar', label: 'Schedule Interview', cls: 'is-schedule' },
  { key: 'reject',    emit: 'reject',    icon: 'x',        label: 'Reject',             cls: 'is-reject' },
]

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
  openConversation(props.proposal?.bp?.id)
}
</script>

<style scoped>
/* ── Hero ─────────────────────────────────────────────────────────── */
.ap-hero { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 20px; }
.ap-name { font-family: var(--font-serif); font-size: 22px; font-weight: 700; color: var(--text); }
.ap-name-link { text-decoration: none; cursor: pointer; color: var(--gold-dark); }
.ap-name-link:hover { text-decoration: underline; }
.ap-role { font-family: var(--font-sans); font-size: 13px; color: var(--text-2); margin-top: 2px; }
.ap-meta { display: flex; gap: 12px; flex-wrap: wrap; font-family: var(--font-sans); font-size: 12px; color: var(--text-3); margin-top: 8px; }
.ap-meta span { display: flex; align-items: center; gap: 4px; }

/* ── Cover letter ─────────────────────────────────────────────────── */
.ap-cover-text {
  font-family: var(--font-sans);
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.7;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 14px 16px;
  white-space: pre-line;
}

/* ── Application Status card ──────────────────────────────────────── */
.ap-status-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  margin-bottom: 14px;
  box-shadow: var(--shadow-sm);
}
.ap-status-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 18px;
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
}
.ap-status-card-title {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
}

/* Stage pill — current pipeline stage */
.ap-stage-pill {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: var(--radius-full);
  font-family: var(--font-sans);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.3px;
  border: 1px solid;
}
.ap-stage-pill.is-new       { background: var(--surface-3); border-color: var(--border-dark); color: var(--text-4); }
.ap-stage-pill.is-reviewed  { background: var(--orange-light); border-color: var(--orange); color: var(--orange-dark); }
.ap-stage-pill.is-shortlisted { background: var(--blue-light); border-color: var(--blue); color: var(--blue-dark); }
.ap-stage-pill.is-interview { background: var(--badge-bg-gold, rgba(160,129,62,0.07)); border-color: var(--gold); color: var(--gold-dark); }

/* ── Terminal banners ─────────────────────────────────────────────── */
.ap-terminal {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px 18px;
  border-bottom: 1px solid var(--border);
}
.ap-terminal-hired   { background: var(--green-light); }
.ap-terminal-declined { background: var(--red-light); }
.ap-terminal-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.ap-terminal-hired   .ap-terminal-icon { background: var(--green); color: #fff; }
.ap-terminal-declined .ap-terminal-icon { background: var(--red); color: #fff; }
.ap-terminal-title {
  font-family: var(--font-sans);
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 3px;
}
.ap-terminal-sub {
  font-family: var(--font-sans);
  font-size: 12px;
  color: var(--text-3);
}

/* ── Pipeline action buttons ──────────────────────────────────────── */
.ap-pipeline-stages {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  border-bottom: 1px solid var(--border);
}
.ap-pipeline-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 14px 8px;
  background: var(--surface);
  border: none;
  border-right: 1px solid var(--border);
  cursor: pointer;
  transition: background var(--transition), color var(--transition);
  font-family: var(--font-sans);
}
.ap-pipeline-btn:last-child { border-right: none; }
.ap-pipeline-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.ap-pipeline-btn-icon {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background var(--transition), color var(--transition);
}
.ap-pipeline-btn-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-3);
  text-align: center;
  line-height: 1.3;
  transition: color var(--transition);
}

/* Review — orange */
.ap-pipeline-btn.is-review .ap-pipeline-btn-icon { background: var(--orange-light); color: var(--orange-dark); }
.ap-pipeline-btn.is-review:hover:not(:disabled) { background: var(--orange-light); }
.ap-pipeline-btn.is-review:hover:not(:disabled) .ap-pipeline-btn-label { color: var(--orange-dark); }

/* Shortlist — gold */
.ap-pipeline-btn.is-shortlist .ap-pipeline-btn-icon { background: rgba(160,129,62,0.08); color: var(--gold-dark); }
.ap-pipeline-btn.is-shortlist:hover:not(:disabled) { background: rgba(160,129,62,0.05); }
.ap-pipeline-btn.is-shortlist:hover:not(:disabled) .ap-pipeline-btn-label { color: var(--gold-dark); }

/* Schedule — blue */
.ap-pipeline-btn.is-schedule .ap-pipeline-btn-icon { background: var(--blue-light); color: var(--blue-dark); }
.ap-pipeline-btn.is-schedule:hover:not(:disabled) { background: var(--blue-light); }
.ap-pipeline-btn.is-schedule:hover:not(:disabled) .ap-pipeline-btn-label { color: var(--blue-dark); }

/* Reject — red */
.ap-pipeline-btn.is-reject .ap-pipeline-btn-icon { background: var(--red-light); color: var(--red); }
.ap-pipeline-btn.is-reject:hover:not(:disabled) { background: var(--red-light); }
.ap-pipeline-btn.is-reject:hover:not(:disabled) .ap-pipeline-btn-label { color: var(--red); }

/* ── Private notes ────────────────────────────────────────────────── */
.ap-notes-wrap {
  padding: 16px 18px;
}
</style>
