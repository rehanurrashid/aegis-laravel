<!--
  pages/provider/SupportServices.vue — "Support & Services" (job postings).
  Converted from job-postings.php. AEGIS_VUE_RULES.md governs every pattern here.

  Tabs: My Postings (full parity) · Applications · Hiring Pipeline · Hired
  (Applications / Pipeline / Hired are functional v1s wired to real data and
  real write routes; the rich modal-driven applicant workflows from the legacy
  page — Applicant Profile, Mark Reviewed, Shortlist, Reject, Schedule Interview,
  Contract view — ship in the next batch.)
-->
<template>
  <AppLayout>
    <AegisHeroBanner quiet eyebrow="Support &amp; Services" title="Request support and connect with business partners" subtitle="Find business partners for billing, marketing, IT, and operations — vetted on Aegis with HIPAA-trained backgrounds.">
      <template #actions>
        <button class="btn-hero-ghost is-on-light" @click="router.visit(route('provider.activity') + '?event_type=job_postings')">
          <AegisIcon name="activity" :size="14" />
          Activity
        </button>
        <button class="btn-hero-solid is-on-light" @click="showPostJob = true">
          <AegisIcon name="plus" :size="14" />
          Request Support
        </button>
      </template>
    </AegisHeroBanner>

    <!-- KPI strip -->
    <div class="stat-chips-row" style="margin-top:18px;margin-bottom:18px">
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="briefcase" :size="18" /></div>
        <div><div class="stat-chip-value">{{ stats.open }}</div><div class="stat-chip-label">Active Postings</div></div>
      </div>
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="download" :size="18" /></div>
        <div><div class="stat-chip-value">{{ stats.total_proposals }}</div><div class="stat-chip-label">Total Applications</div></div>
      </div>
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="clock" :size="18" /></div>
        <div><div class="stat-chip-value">{{ stats.pending_proposals }}</div><div class="stat-chip-label">Awaiting Review</div></div>
      </div>
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="check" :size="18" /></div>
        <div><div class="stat-chip-value">{{ stats.hired }}</div><div class="stat-chip-label">Hired</div></div>
      </div>
    </div>

    <!-- TABS -->
    <div class="tabs-primary" role="tablist" style="margin-bottom:24px">
      <button class="tab-primary" :class="{ active: tab === 'my-postings' }" role="tab" :aria-selected="tab === 'my-postings'" @click="tab = 'my-postings'">
        <AegisIcon name="briefcase" :size="15" />
        My Postings <span class="tab-count">{{ jobs.length }}</span>
      </button>
      <button class="tab-primary" :class="{ active: tab === 'applications' }" role="tab" :aria-selected="tab === 'applications'" @click="tab = 'applications'">
        <AegisIcon name="download" :size="15" />
        Applications <span v-if="stats.pending_proposals" class="tab-count">{{ stats.pending_proposals }}</span>
      </button>
      <button class="tab-primary" :class="{ active: tab === 'pipeline' }" role="tab" :aria-selected="tab === 'pipeline'" @click="tab = 'pipeline'">
        <AegisIcon name="grid" :size="15" />
        Hiring Pipeline
      </button>
      <button class="tab-primary" :class="{ active: tab === 'hired' }" role="tab" :aria-selected="tab === 'hired'" @click="tab = 'hired'">
        <AegisIcon name="check" :size="15" />
        Hired <span v-if="stats.hired" class="tab-count">{{ stats.hired }}</span>
      </button>
    </div>

    <!-- ============================================================
         PANE 1: MY POSTINGS
    ============================================================ -->
    <div v-show="tab === 'my-postings'">
      <div class="section-header" style="margin-bottom:16px">
        <div class="section-title-h"><AegisIcon name="briefcase" :size="16" /> My Support Requests</div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-outline btn-sm" @click="showTemplates = true">
            <AegisIcon name="download" :size="12" />
            Use Template
          </button>
          <button class="btn btn-primary" @click="showPostJob = true">
            <AegisIcon name="plus" :size="12" />
            Request Support
          </button>
        </div>
      </div>

      <div class="tabs-segmented" style="margin-top:16px;margin-bottom:16px">
        <button class="tab-pill" :class="{ active: postingFilter === 'all' }" @click="postingFilter = 'all'">All <span class="badge-pill">{{ jobs.length }}</span></button>
        <button class="tab-pill" :class="{ active: postingFilter === 'open' }" @click="postingFilter = 'open'">Active <span v-if="stats.open" class="badge-pill">{{ stats.open }}</span></button>
        <button class="tab-pill" :class="{ active: postingFilter === 'draft' }" @click="postingFilter = 'draft'">Draft <span v-if="stats.draft" class="badge-pill">{{ stats.draft }}</span></button>
        <button class="tab-pill" :class="{ active: postingFilter === 'paused' }" @click="postingFilter = 'paused'">Paused <span v-if="stats.paused" class="badge-pill">{{ stats.paused }}</span></button>
        <button class="tab-pill" :class="{ active: postingFilter === 'closed' }" @click="postingFilter = 'closed'">Closed <span v-if="stats.closed" class="badge-pill">{{ stats.closed }}</span></button>
      </div>

      <div class="jp-my-table">
        <div class="jp-my-table-head">
          <div>Request Title</div><div>Category</div><div>Budget</div>
          <div>Applicants</div><div>Status</div><div>Actions</div>
        </div>

        <AegisEmptyState v-if="!filteredJobs.length" icon="briefcase" title="No support requests yet" description="Submit your first request to start receiving proposals from vetted business partners.">
          <template #action>
            <button class="btn btn-primary" @click="showPostJob = true"><AegisIcon name="plus" :size="13" /> Request Support</button>
          </template>
        </AegisEmptyState>

        <div
          v-for="job in pagedJobs"
          :key="job.id"
          class="jp-my-row"
          :style="rowStyle(job.status)"
          @click="openEdit(job)"
        >
          <div>
            <div class="jp-my-title">{{ job.title }}</div>
            <div class="jp-my-sub"><AegisIcon name="briefcase" :size="11" /> {{ categoryLabel(job.category) }} · Posted {{ formatDate(job.posted_at) }} · {{ locationLabel(job.location_pref) }}</div>
          </div>
          <div><span class="tag-chip">{{ categoryLabel(job.category) }}</span></div>
          <div :style="{ fontSize: '13px', fontWeight: 700, color: val(job.status) === 'open' ? 'var(--green)' : 'var(--text-3)' }">{{ formatBudget(job) }}</div>
          <div>
            <div style="font-size:13px;font-weight:700;color:var(--text)">{{ proposalCount(job.id) }}</div>
            <div :style="{ fontSize: '11px', color: newProposalCount(job.id) > 0 ? 'var(--gold-dark)' : 'var(--text-3)' }">
              {{ newProposalCount(job.id) > 0 ? newProposalCount(job.id) + ' new' : '0 new' }}
            </div>
          </div>
          <div><span class="badge" :class="statusBadgeClass(job.status)"><AegisIcon name="dot" :size="8" :filled="true" /> {{ statusLabel(job.status) }}</span></div>
          <div style="display:flex;gap:5px" @click.stop>
            <button v-if="['open','draft','paused'].includes(val(job.status))" class="btn-icon" data-tooltip="Edit" @click="openEdit(job)"><AegisIcon name="pencil" :size="12" /></button>
            <button v-if="proposalCount(job.id) > 0" class="btn-icon" :data-tooltip="proposalCount(job.id) + ' applicants'" @click="openManageApps(job)"><AegisIcon name="users" :size="12" /></button>
            <button v-if="val(job.status) === 'open'" class="btn-icon btn-icon-danger" data-tooltip="Pause" @click="confirmPause(job)"><AegisIcon name="pause" :size="12" /></button>
            <button v-else-if="val(job.status) === 'draft'" class="btn btn-xs btn-success" @click="confirmPublish(job)">Publish</button>
            <button v-else-if="val(job.status) === 'paused'" class="btn-icon" data-tooltip="Resume posting" @click="confirmResume(job)"><AegisIcon name="refresh-cw" :size="12" /></button>
          </div>
        </div>
      </div>

      <div v-if="filteredJobs.length" class="pager">
        <div class="pager-info">
          Showing <strong>{{ (jobsPage - 1) * 5 + 1 }}–{{ Math.min(jobsPage * 5, filteredJobs.length) }}</strong>
          of {{ filteredJobs.length }} posting{{ filteredJobs.length !== 1 ? 's' : '' }}
        </div>
        <AegisPagination
          :current-page="jobsPage"
          :total-pages="jobsTotalPages"
          @change="jobsPage = $event"
        />
      </div>
    </div>

    <!-- ============================================================
         PANE 2: APPLICATIONS
    ============================================================ -->
    <div v-show="tab === 'applications'">
      <div class="section-header" style="margin-bottom:16px">
        <div class="section-title-h"><AegisIcon name="download" :size="16" /> Applications</div>
        <select v-model="applicationsJobFilter" class="form-select" style="width:240px;font-size:12px">
          <option value="">All Jobs</option>
          <option v-for="j in jobs" :key="j.id" :value="j.id">{{ j.title }}</option>
        </select>
      </div>

      <AegisEmptyState v-if="!filteredApplications.length" icon="download" title="No applications yet" description="Proposals from Business Partners will appear here as they apply to your postings." />

      <div v-else class="jp-my-table">
        <div class="jp-app-table-head">
          <div></div><div>Applicant</div><div>Applying For</div><div>Bid</div><div>Status</div><div>Actions</div>
        </div>
        <div v-for="p in pagedApplications" :key="p.id" class="jp-app-row" @click="openProfile(p)">
          <div class="jp-app-avatar" :style="avatarStyle(p.bp)">
            <template v-if="!p.bp?.avatar_url">{{ initials(p.bp?.display_name) }}</template>
          </div>
          <div>
            <div class="jp-app-name">{{ p.bp?.display_name ?? 'Business Partner' }}</div>
            <div class="jp-app-role">{{ bpTypeLabel(p.bp?.bp_type) }}</div>
          </div>
          <div style="font-size:12.5px;color:var(--text-2)">{{ jobTitle(p.job_id) }}</div>
          <div style="font-size:13px;font-weight:700;color:var(--green)">{{ formatCents(p.proposed_rate_cents) }}</div>
          <div><span class="badge" :class="proposalStatusBadgeClass(p)">{{ proposalStatusLabel(p) }}</span></div>
          <div style="display:flex;gap:5px" @click.stop>
            <button class="btn-icon" data-tooltip="View profile" @click="openProfile(p)"><AegisIcon name="eye" :size="12" /></button>
            <template v-if="val(p.status) === 'pending'">
              <button class="btn-icon" data-tooltip="Hire" @click="openHire(p)"><AegisIcon name="check" :size="12" /></button>
              <button class="btn-icon btn-icon-danger" data-tooltip="Reject" @click="openReject(p)"><AegisIcon name="x" :size="12" /></button>
            </template>
          </div>
        </div>
      </div>

      <div v-if="filteredApplications.length" class="pager">
        <div class="pager-info">
          Showing <strong>{{ (appsPage - 1) * 8 + 1 }}–{{ Math.min(appsPage * 8, filteredApplications.length) }}</strong>
          of {{ filteredApplications.length }} application{{ filteredApplications.length !== 1 ? 's' : '' }}
        </div>
        <AegisPagination
          :current-page="appsPage"
          :total-pages="appsTotalPages"
          @change="appsPage = $event"
        />
      </div>
    </div>

    <!-- ============================================================
         PANE 3: HIRING PIPELINE (kanban)
    ============================================================ -->
    <div v-show="tab === 'pipeline'">
      <div class="section-header" style="margin-bottom:16px">
        <div class="section-title-h"><AegisIcon name="grid" :size="16" /> Hiring Pipeline</div>
        <select v-model="pipelineJobFilter" class="form-select" style="width:240px;font-size:12px">
          <option value="">All Active Jobs</option>
          <option v-for="j in openJobs" :key="j.id" :value="j.id">{{ j.title }}</option>
        </select>
      </div>

      <div class="jp-kanban">
        <div v-for="col in kanbanColumns" :key="col.stage" class="jp-kanban-col">
          <div class="jp-kanban-col-header" :style="{ color: col.color }">
            <span>{{ col.label }}</span>
            <span class="jp-kanban-count">{{ pipelineGroup(col.stage).length }}</span>
          </div>
          <div>
            <div v-for="p in pipelineGroup(col.stage)" :key="p.id" class="jp-kanban-card" @click="openProfile(p)">
              <div class="jp-kanban-name">{{ p.bp?.display_name ?? 'Business Partner' }}</div>
              <div class="jp-kanban-role">{{ jobTitle(p.job_id) }}</div>
              <div class="jp-kanban-rate">{{ formatCents(p.proposed_rate_cents) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 4: HIRED
    ============================================================ -->
    <div v-show="tab === 'hired'">
      <div class="section-header" style="margin-bottom:16px">
        <div class="section-title-h"><AegisIcon name="check" :size="16" /> Hired Business Partners</div>
      </div>

      <AegisEmptyState v-if="!activeContracts.length" icon="users" title="No hired partners yet" description="Accepted proposals will appear here. Accept an application to hire a business partner." />

      <div v-else class="jp-grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
        <div v-for="c in activeContracts" :key="c.id" class="jp-card" style="border-color:var(--green)" @click="openContract(c)">
          <div class="jp-card-header">
            <div class="jp-card-logo avatar avatar-gold" style="font-size:13px;font-weight:700;border-radius:var(--radius-sm)" :style="avatarStyle(c.bp)">
              <template v-if="!c.bp?.avatar_url">{{ initials(c.bp?.display_name) }}</template>
            </div>
            <div>
              <div class="jp-card-title">{{ c.bp?.display_name ?? 'Business Partner' }}</div>
              <div class="jp-card-practice">{{ c.title }} · {{ bpTypeLabel(c.bp?.bp_type) }}</div>
            </div>
          </div>
          <div class="jp-card-footer">
            <span class="badge badge-green"><AegisIcon name="dot" :size="8" :filled="true" /> {{ val(c.status) === 'active' ? 'Active Contract' : 'Completed' }}</span>
            <div class="jp-card-actions" @click.stop>
              <button class="btn-icon" data-tooltip="Message" :disabled="msgLoading === c.bp?.id" @click="openConversation(c.bp?.id)"><AegisIcon name="message-square" :size="14" /></button>
              <button class="btn-icon" data-tooltip="View contract" @click="openContract(c)"><AegisIcon name="file-text" :size="14" /></button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <PostJobModal v-model="showPostJob" :prefill="postJobPrefill" @update:model-value="(v) => { if (!v) postJobPrefill = null }" />
    <ImportJobTemplatesModal v-model="showTemplates" @use="onUseTemplate" />
    <EditJobModal v-model="showEdit" :job="editingJob" />
    <ManageApplicationsModal
      v-model="showManageApps"
      :job="manageAppsJob"
      :proposals="manageAppsJob ? proposalsForJob(manageAppsJob.id) : []"
      @open-profile="openProfile"
      @view-pipeline="() => { showManageApps = false; tab = 'pipeline' }"
    />
    <ApplicantProfileModal
      v-model="showProfile"
      :proposal="activeProposal"
      :job-title="activeProposal ? jobTitle(activeProposal.job_id) : ''"
      :stats="activeProposal ? (bpStats[activeProposal.bp_id] || {}) : {}"
      :contract-proposal-ids="activeContracts.map(c => c.proposal_id).filter(Boolean)"
      @reviewed="(p) => openStage(p, 'reviewed')"
      @shortlist="(p) => openStage(p, 'shortlisted')"
      @schedule="(p) => openSchedule(p)"
      @reject="(p) => openReject(p)"
      @hire="(p) => openHire(p)"
    />
    <StageActionModal v-model="showStage" :proposal="activeProposal" :stage="stageMode" @done="showProfile = false" />
    <ScheduleInterviewModal v-model="showSchedule" :proposal="activeProposal" :job-title="activeProposal ? jobTitle(activeProposal.job_id) : ''" @done="showProfile = false" />
    <RejectModal v-model="showReject" :proposal="activeProposal" @done="showProfile = false" />
    <HireModal
      v-model="showHire"
      :proposal="activeProposal"
      :job="activeProposal ? jobs.find(j => j.id === activeProposal.job_id) : null"
      :job-title="activeProposal ? jobTitle(activeProposal.job_id) : ''"
      :stats="activeProposal ? (bpStats[activeProposal.bp_id] || {}) : {}"
      @done="() => { showProfile = false; showHire = false; showManageApps = false }"
    />
    <ContractModal
      v-model="showContract"
      :contract="activeContract"
      :milestones="activeContract ? (milestonesByContract?.[activeContract.id] ?? []) : []"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { syncFormEnhancements } from '@/plugins/FormEnhancerPlugin'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisPagination from '@/components/ui/AegisPagination.vue'
import PostJobModal from '@/components/modals/PostJobModal.vue'
import EditJobModal from '@/components/modals/EditJobModal.vue'
import ImportJobTemplatesModal from '@/components/modals/ImportJobTemplatesModal.vue'
import ManageApplicationsModal from '@/components/modals/ManageApplicationsModal.vue'
import ApplicantProfileModal from '@/components/modals/ApplicantProfileModal.vue'
import StageActionModal from '@/components/modals/StageActionModal.vue'
import ScheduleInterviewModal from '@/components/modals/ScheduleInterviewModal.vue'
import RejectModal from '@/components/modals/RejectModal.vue'
import HireModal from '@/components/modals/HireModal.vue'
import ContractModal from '@/components/modals/ContractModal.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useMessageButton } from '@/composables/useMessageButton'

const props = defineProps({
  jobs:            { type: Array,  default: () => [] },
  proposalsByJob:  { type: Object, default: () => ({}) },
  activeContracts:       { type: Array,  default: () => [] },
  milestonesByContract:  { type: Object, default: () => ({}) },
  bpStats:         { type: Object, default: () => ({}) },
  stats: {
    type: Object,
    default: () => ({ open: 0, draft: 0, paused: 0, filled: 0, closed: 0, total_jobs: 0, total_proposals: 0, pending_proposals: 0, hired: 0, total_spent_cents: 0 }),
  },
})

const toast = useToast()
const { confirmAction } = useConfirm()
const { openConversation, loading: msgLoading } = useMessageButton()

const tab = ref('my-postings')
watch(tab, () => syncFormEnhancements())
const postingFilter = ref('all')
const applicationsJobFilter = ref('')
const pipelineJobFilter = ref('')

const showPostJob = ref(false)
const showTemplates = ref(false)
const showEdit = ref(false)
const editingJob = ref(null)
const postJobPrefill = ref(null)

const showManageApps = ref(false)
const manageAppsJob = ref(null)

const showProfile = ref(false)
const showStage = ref(false)
const stageMode = ref('reviewed')
const showSchedule = ref(false)
const showReject = ref(false)
const showHire = ref(false)
// Store only the ID of the active proposal — the object itself is always
// derived live from proposalsByJob so it reflects Inertia page reloads.
const _activeProposalId = ref(null)
const activeProposal = computed(() => {
  if (!_activeProposalId.value) return null
  return allProposals.value.find(p => p.id === _activeProposalId.value) ?? null
})

const showContract = ref(false)
const activeContract = ref(null)

// Unwrap backed enum values — Inertia may serialise them as {value:'x'} objects
// instead of plain strings depending on Laravel/Inertia version. Always use val()
// when comparing model enum fields (status, budget_type, etc.) in Vue.
const val = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

const openJobs = computed(() => props.jobs.filter(j => val(j.status) === 'open'))

const allProposals = computed(() => Object.values(props.proposalsByJob).flat())

const filteredJobs = computed(() => {
  if (postingFilter.value === 'all') return props.jobs
  if (postingFilter.value === 'closed') return props.jobs.filter(j => ['closed', 'cancelled', 'filled'].includes(val(j.status)))
  return props.jobs.filter(j => val(j.status) === postingFilter.value)
})

const filteredApplications = computed(() => {
  const list = applicationsJobFilter.value
    ? (props.proposalsByJob[applicationsJobFilter.value] || [])
    : allProposals.value
  return [...list].sort((a, b) => new Date(b.submitted_at) - new Date(a.submitted_at))
})

// ── Client-side pagination ────────────────────────────────────────────────────
const JOBS_PER_PAGE  = 5
const APPS_PER_PAGE  = 8

const jobsPage  = ref(1)
const appsPage  = ref(1)

// Reset to page 1 whenever filter changes
watch(postingFilter,         () => { jobsPage.value = 1 })
watch(applicationsJobFilter, () => { appsPage.value = 1 })

const jobsTotalPages  = computed(() => Math.max(1, Math.ceil(filteredJobs.value.length / JOBS_PER_PAGE)))
const appsTotalPages  = computed(() => Math.max(1, Math.ceil(filteredApplications.value.length / APPS_PER_PAGE)))

const pagedJobs = computed(() => {
  const start = (jobsPage.value - 1) * JOBS_PER_PAGE
  return filteredJobs.value.slice(start, start + JOBS_PER_PAGE)
})
const pagedApplications = computed(() => {
  const start = (appsPage.value - 1) * APPS_PER_PAGE
  return filteredApplications.value.slice(start, start + APPS_PER_PAGE)
})

const kanbanColumns = [
  { stage: 'new',         label: 'New',         icon: 'download',       color: 'var(--blue-dark)' },
  { stage: 'reviewed',    label: 'Reviewing',   icon: 'eye',            color: 'var(--orange)' },
  { stage: 'shortlisted', label: 'Shortlisted', icon: 'star',           color: 'var(--text)' },
  { stage: 'interview',   label: 'Interview',   icon: 'calendar',       color: 'var(--gold-dark)' },
  { stage: 'hired',       label: 'Hired',       icon: 'check',          color: 'var(--green)' },
  { stage: 'rejected',    label: 'Rejected',    icon: 'x',              color: 'var(--red)' },
]
const stageOrder = ['new', 'reviewed', 'shortlisted', 'interview', 'hired']

function pipelineGroup(stage) {
  const list = pipelineJobFilter.value
    ? (props.proposalsByJob[pipelineJobFilter.value] || [])
    : allProposals.value

  return list.filter(p => {
    const pStage  = p.pipeline_stage || 'new'
    const pStatus = val(p.status)

    // Withdrawn never appears anywhere
    if (pStatus === 'withdrawn') return false

    // Hired bucket: accepted status OR pipeline_stage=hired (always exclusive)
    if (stage === 'hired') return pStatus === 'accepted' || pStage === 'hired'

    // Accepted proposals ONLY go in hired — exclude from all other columns
    if (pStatus === 'accepted') return false

    // Rejected bucket: declined status OR pipeline_stage=rejected
    if (stage === 'rejected') return pStatus === 'declined' || pStage === 'rejected'

    // Declined/rejected proposals don't appear in active columns
    if (pStatus === 'declined' || pStage === 'rejected') return false

    // Active proposal — match by pipeline_stage
    return pStage === stage
  })
}

function nextStages(currentStage) {
  const idx = stageOrder.indexOf(currentStage)
  return stageOrder.slice(idx + 1).map(s => ({ stage: s, label: kanbanColumns.find(c => c.stage === s)?.label }))
}

function moveStage(proposal, stage) {
  if (stage === 'hired') {
    openHire(proposal)
    return
  }
  router.post(route('provider.jobs.proposal.stage', { job: proposal.job_id, proposal: proposal.id }), { pipeline_stage: stage }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Applicant moved.'),
    onError:   () => toast.error('Could not update applicant.'),
  })
}

// ── Lookups / formatting ─────────────────────────────────────────────
const categoryLabels = {
  billing: 'Billing', technology: 'IT', it: 'IT', marketing: 'Marketing', legal: 'Legal',
  admin: 'Admin', accounting: 'Accounting', compliance: 'Compliance', credentialing: 'Credentialing',
  consulting: 'Consulting', design: 'Design', hr: 'HR',
}
function categoryLabel(cat) { return categoryLabels[cat] || (cat ? cat.charAt(0).toUpperCase() + cat.slice(1) : '—') }
function locationLabel(loc) { return ({ remote: 'Remote', onsite: 'On-Site', hybrid: 'Hybrid' }[loc] || 'Remote') }
function bpTypeLabel(t) { return t ? t.charAt(0).toUpperCase() + t.slice(1) : 'Business Partner' }

function formatBudget(job) {
  if (!job.budget_amount_cents) return 'TBD'
  const amt = '$' + (job.budget_amount_cents / 100).toLocaleString()
  return job.budget_type === 'hourly' ? amt + '/hr' : amt + '/mo'
}
function formatCents(cents) {
  if (!cents) return '—'
  return '$' + (cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}

const statusLabels = { open: 'Active', draft: 'Draft', paused: 'Paused', closed: 'Closed', filled: 'Closed', cancelled: 'Closed' }
function statusLabel(s) { const v = val(s); return statusLabels[v] || v }
function statusBadgeClass(s) {
  return { open: 'badge-green', draft: 'badge-gray', paused: 'badge-orange', closed: 'badge-red', filled: 'badge-red', cancelled: 'badge-red' }[val(s)] || 'badge-gray'
}
function rowStyle(status) {
  const s = val(status)
  if (['draft', 'paused'].includes(s)) return { opacity: 0.75 }
  if (['filled', 'closed', 'cancelled'].includes(s)) return { opacity: 0.6 }
  return {}
}

function proposalsForJob(jobId) { return props.proposalsByJob[jobId] || [] }
function proposalCount(jobId) { return proposalsForJob(jobId).length }
function newProposalCount(jobId) { return proposalsForJob(jobId).filter(p => (p.pipeline_stage || 'new') === 'new').length }
function jobTitle(jobId) { return props.jobs.find(j => j.id === jobId)?.title ?? '—' }

function proposalStatus(p) { return val(p.status) }
function proposalStatusLabel(p) {
  const s = proposalStatus(p)
  const stage = p.pipeline_stage || 'new'
  if (s === 'accepted') return 'Hired'
  if (s === 'declined') return 'Declined'
  if (s === 'withdrawn') return 'Withdrawn'
  return ({ new: 'New', reviewed: 'Reviewing', shortlisted: 'Shortlisted', interview: 'Interview', hired: 'Hired' }[stage] || 'New')
}
function proposalStatusBadgeClass(p) {
  const s = proposalStatus(p)
  const stage = p.pipeline_stage || 'new'
  if (s === 'accepted') return 'badge-green'
  if (s === 'declined') return 'badge-red'
  if (s === 'withdrawn') return 'badge-gray'
  return ({ new: 'badge-gray', reviewed: 'badge-orange', shortlisted: 'badge-blue', interview: 'badge-gold', hired: 'badge-green' }[stage] || 'badge-gray')
}

const avatarPalette = ['var(--gold-dark)', 'var(--blue-dark)', 'var(--green-dark)', 'var(--orange)']
function initials(name) {
  if (!name) return 'BP'
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}
function avatarStyle(bp) {
  if (bp?.avatar_url) {
    return { backgroundImage: `url(${bp.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' }
  }
  const i = (bp?.id?.charCodeAt(2) ?? 0) % avatarPalette.length
  return { background: avatarPalette[i] }
}

// ── Actions ───────────────────────────────────────────────────────────
function openEdit(job) {
  editingJob.value = job
  showEdit.value = true
}

function openManageApps(job) {
  manageAppsJob.value = job
  showManageApps.value = true
}

function setStatus(job, status, okMsg) {
  router.post(route('provider.jobs.status', job.id), { status }, {
    preserveScroll: true,
    onSuccess: () => toast.success(okMsg),
    onError:   () => toast.error('Could not update posting.'),
  })
}

function confirmPause(job) {
  confirmAction(
    { title: 'Pause Posting', message: 'Pause this posting? Business Partners will not see it until you resume.', confirmLabel: 'Pause', destructive: true },
    () => setStatus(job, 'paused', 'Posting paused.'),
  )
}

function confirmPublish(job) {
  confirmAction(
    { title: 'Publish Posting', message: `Publish "${job.title}"? It will become visible to all Business Partners on Aegis immediately.`, confirmLabel: 'Publish', destructive: false },
    () => setStatus(job, 'open', 'Posting published — live to Business Partners.'),
  )
}

function confirmResume(job) {
  confirmAction(
    { title: 'Resume Posting', message: `Resume "${job.title}"? Business Partners will be able to see and apply to it again.`, confirmLabel: 'Resume', destructive: false },
    () => setStatus(job, 'open', 'Posting resumed.'),
  )
}

// ── Applicant workflow ───────────────────────────────────────────────
function openProfile(proposal) {
  _activeProposalId.value = proposal.id
  showManageApps.value = false
  showProfile.value = true
}

function openStage(proposal, stage) {
  _activeProposalId.value = proposal.id
  stageMode.value = stage
  showStage.value = true
}

function openSchedule(proposal) {
  _activeProposalId.value = proposal.id
  showSchedule.value = true
}

function openReject(proposal) {
  _activeProposalId.value = proposal.id
  showReject.value = true
}

function openHire(proposal) {
  _activeProposalId.value = proposal.id
  showHire.value = true
}

function openContract(contract) {
  activeContract.value = contract
  showContract.value = true
}

function onUseTemplate(t) {
  postJobPrefill.value = t
  showPostJob.value = true
}
</script>

<style scoped>
.jp-my-table { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); }
.jp-my-table-head { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 120px; background: var(--surface-2); border-bottom: 1px solid var(--border); padding: 10px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-3); }
.jp-my-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 120px; padding: 14px 16px; border-bottom: 1px solid var(--border); align-items: center; transition: background var(--transition); cursor: pointer; }
.jp-my-row:last-child { border-bottom: none; }
.jp-my-row:hover { background: var(--surface-2); }
.jp-my-title { font-size: 13px; font-weight: 700; color: var(--text); }
.jp-my-sub { font-size: 11px; color: var(--text-3); margin-top: 2px; display: flex; align-items: center; gap: 4px; }

.jp-app-table-head { display: grid; grid-template-columns: 44px 2fr 1.2fr 1fr 1fr 110px; gap: 12px; padding: 10px 16px; background: var(--surface-2); border-bottom: 1px solid var(--border); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-3); }
.jp-app-row { display: grid; grid-template-columns: 44px 2fr 1.2fr 1fr 1fr 110px; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--border); align-items: center; transition: background var(--transition); cursor: pointer; }
.jp-app-row:hover { background: var(--surface-2); }
.jp-app-avatar { width: 38px; height: 38px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: var(--text-inverted); flex-shrink: 0; }
.jp-app-name { font-size: 13px; font-weight: 700; color: var(--text); }
.jp-app-role { font-size: 11px; color: var(--text-3); margin-top: 1px; }

.jp-grid { display: grid; gap: 14px; }
.jp-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 18px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 10px; transition: all var(--transition); cursor: pointer; }
.jp-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); }
.jp-card-header { display: flex; align-items: flex-start; gap: 12px; }
.jp-card-logo { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.jp-card-title { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 2px; line-height: 1.3; }
.jp-card-practice { font-size: 12px; color: var(--text-2); }
.jp-card-footer { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 4px; padding-top: 10px; border-top: 1px solid var(--border); }
.jp-card-actions { display: flex; gap: 6px; }

.jp-kanban { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; min-height: 320px; }
.jp-kanban-col { background: var(--surface-2); border-radius: var(--radius-lg); padding: 10px; }
.jp-kanban-col-header { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; padding: 4px 6px 8px; display: flex; align-items: center; justify-content: space-between; }
.jp-kanban-count { background: var(--gold-dark); border-radius: var(--radius-full); padding: 1px 7px; font-size: 10px; font-weight: 700; color: #fff; }
.jp-kanban-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 10px; margin-bottom: 8px; transition: all var(--transition); cursor: pointer; }
.jp-kanban-card:hover { box-shadow: var(--shadow); transform: translateY(-1px); }
.jp-kanban-name { font-size: 12px; font-weight: 700; color: var(--text); }
.jp-kanban-role { font-size: 11px; color: var(--text-3); margin-top: 2px; }
.jp-kanban-rate { font-size: 11px; font-weight: 700; color: var(--green); margin-top: 2px; }

@media (max-width: 900px) {
  .jp-kanban { grid-template-columns: repeat(3, 1fr); }
  .jp-my-table-head, .jp-my-row { grid-template-columns: 2fr 1fr 1fr 90px; }
  .jp-my-table-head > *:nth-child(3), .jp-my-table-head > *:nth-child(4),
  .jp-my-row > *:nth-child(3), .jp-my-row > *:nth-child(4) { display: none; }
}
</style>
