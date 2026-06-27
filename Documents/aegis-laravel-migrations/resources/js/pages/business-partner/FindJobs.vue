<!--
  pages/business-partner/FindJobs.vue — browse and apply to job postings.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Work"
      title="Find Jobs"
      :subtitle="`${jobs.length} job${jobs.length === 1 ? '' : 's'} matching your services.`"
    />

    <!-- Filters -->
    <div class="bp-filters">
      <input v-model="filters.q" type="text" class="form-input bp-filters-q" placeholder="Search jobs…" @keyup.enter="apply" />
      <select v-model="filters.engagement" class="form-input" @change="apply">
        <option value="">Any engagement</option>
        <option value="fixed">Fixed</option>
        <option value="hourly">Hourly</option>
      </select>
      <select v-model="filters.sort" class="form-input" @change="apply">
        <option value="recent">Recent</option>
        <option value="budget_high">Budget high → low</option>
        <option value="budget_low">Budget low → high</option>
      </select>
    </div>

    <div v-if="jobs.length" class="job-results-list">
      <article
        v-for="j in jobs"
        :key="j.id"
        class="job-result-row"
        @click="openJob(j)"
      >
        <header class="job-result-head">
          <div>
            <div class="job-result-title">{{ j.title }}</div>
            <div class="job-result-poster">{{ j.poster_name }} · {{ j.poster_role }}</div>
          </div>
          <AegisBadge
            :label="j.engagement === 'fixed' ? 'Fixed' : 'Hourly'"
            variant="gold"
          />
        </header>
        <p class="job-result-snippet">{{ j.snippet }}</p>
        <footer class="job-result-foot">
          <span><AegisIcon name="dollar" :size="12" /> {{ rateLine(j) }}</span>
          <span><AegisIcon name="calendar" :size="12" /> {{ activity.timeAgo(j.created_at) }}</span>
          <span><AegisIcon name="file-pen" :size="12" /> {{ j.proposal_count }} proposals</span>
        </footer>
      </article>
    </div>

    <AegisEmptyState v-else icon="search-lg" title="No jobs match your filters" description="Try a broader search." />

    <JobDetailModal :job="selectedJob" />
    <ProposalModal :job="selectedJob" />
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import JobDetailModal from '@/components/modals/JobDetailModal.vue'
import ProposalModal from '@/components/modals/ProposalModal.vue'
import { useModal } from '@/composables/useModal'
import { useActivity } from '@/composables/useActivity'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  jobs:    { type: Array,  default: () => [] },
  filters: { type: Object, default: () => ({ q: '', engagement: '', sort: 'recent' }) },
})

const { openModal } = useModal()
const activity = useActivity()
const pricing = usePricingStore()

const filters = reactive({ ...props.filters })
const selectedJob = ref(null)

function rateLine(j) {
  if (j.engagement === 'fixed') return j.budget_cents ? pricing.formatCents(j.budget_cents) + ' fixed' : 'Fixed'
  return j.rate_min_cents && j.rate_max_cents
    ? `${pricing.formatCents(j.rate_min_cents)}–${pricing.formatCents(j.rate_max_cents)}/hr`
    : 'Hourly'
}

function apply() {
  router.get(route('bp.find-jobs'), filters, {
    preserveScroll: true, preserveState: true, replace: true, only: ['jobs', 'filters'],
  })
}

function openJob(j) {
  selectedJob.value = j
  openModal('jobDetailModal')
}
</script>
