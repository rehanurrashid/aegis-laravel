<!--
  JobDetailModal.vue — BP "Find Jobs" detail viewer.

  Replaces _shared/modals/job_detail_modal.php. Opens from a job listing
  row in the BP Find Jobs page. Shows full posting details and offers
  "Submit Proposal" (opens ProposalModal) and "Save" actions.
-->
<template>
  <AegisModal
    :model-value="isOpen('jobDetailModal').value"
    :title="job?.title ?? 'Job posting'"
    size="lg"
    @update:model-value="onUpdateOpen"
  >
    <div v-if="job" class="job-detail">
      <!-- Header strip -->
      <div class="job-detail-header">
        <div class="job-detail-poster">
          <div class="job-detail-poster-avatar">{{ posterInitials }}</div>
          <div>
            <div class="job-detail-poster-name">{{ job.poster_name }}</div>
            <div class="job-detail-poster-role">{{ job.poster_role }}</div>
          </div>
        </div>
        <div class="job-detail-meta">
          <AegisBadge
            :label="job.engagement === 'fixed' ? 'Fixed price' : 'Hourly'"
            variant="gold"
            :icon="job.engagement === 'fixed' ? 'flag-2' : 'clock'"
          />
          <span class="job-detail-rate">{{ rateLine }}</span>
        </div>
      </div>

      <!-- Tags -->
      <div v-if="job.tags?.length" class="job-detail-tags">
        <span v-for="t in job.tags" :key="t" class="job-detail-tag">{{ t }}</span>
      </div>

      <!-- Body sections -->
      <section class="job-detail-section">
        <h3>About the role</h3>
        <p class="job-detail-prose">{{ job.description }}</p>
      </section>

      <section v-if="job.scope" class="job-detail-section">
        <h3>Scope</h3>
        <p class="job-detail-prose">{{ job.scope }}</p>
      </section>

      <section v-if="job.requirements?.length" class="job-detail-section">
        <h3>Requirements</h3>
        <ul class="job-detail-list">
          <li v-for="req in job.requirements" :key="req">
            <AegisIcon name="check" :size="13" />
            <span>{{ req }}</span>
          </li>
        </ul>
      </section>

      <!-- Footer meta -->
      <div class="job-detail-footer-meta">
        <span><AegisIcon name="calendar" :size="12" /> Posted {{ activity.timeAgo(job.created_at) }}</span>
        <span v-if="job.proposal_count != null">
          <AegisIcon name="file-pen" :size="12" /> {{ job.proposal_count }} proposal{{ job.proposal_count === 1 ? '' : 's' }}
        </span>
        <span v-if="job.deadline">
          <AegisIcon name="hourglass" :size="12" /> Deadline {{ activity.timeAgo(job.deadline) }}
        </span>
      </div>
    </div>

    <template #footer>
      <button
        type="button"
        class="btn btn-outline"
        @click="toggleSaved"
      >
        <AegisIcon :name="job?.saved ? 'bookmark' : 'bookmark'" :size="14" :filled="job?.saved" />
        <span>{{ job?.saved ? 'Saved' : 'Save' }}</span>
      </button>
      <button
        type="button"
        class="btn btn-primary"
        :disabled="job?.has_my_proposal"
        @click="openProposal"
      >
        <AegisIcon name="send" :size="14" />
        <span>{{ job?.has_my_proposal ? 'Proposal submitted' : 'Submit proposal' }}</span>
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'
import AegisBadge from '@/components/ui/AegisBadge.vue'

const props = defineProps({
  job: { type: Object, default: null },
})

const { isOpen, openModal, closeModal } = useModal()
const toast    = useToast()
const activity = useActivity()

const posterInitials = computed(() => {
  const n = props.job?.poster_name ?? ''
  return n.split(/\s+/).map((s) => s[0]).slice(0, 2).join('').toUpperCase() || '·'
})

const rateLine = computed(() => {
  const j = props.job
  if (!j) return ''
  if (j.engagement === 'fixed') {
    return j.budget_cents != null ? `$${(j.budget_cents / 100).toLocaleString()} fixed` : 'Fixed price'
  }
  if (j.rate_min_cents != null && j.rate_max_cents != null) {
    return `$${j.rate_min_cents / 100}–$${j.rate_max_cents / 100}/hr`
  }
  return 'Hourly'
})

function onUpdateOpen(v) { if (!v) closeModal('jobDetailModal') }

function openProposal() {
  closeModal('jobDetailModal')
  // Defer so the close animation finishes before the next overlay opens.
  setTimeout(() => openModal('proposalModal'), 200)
}

function toggleSaved() {
  if (!props.job) return
  router.post(route('bp.jobs.toggle-save', { job: props.job.id }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success(props.job.saved ? 'Removed from saved' : 'Saved'),
  })
}
</script>
