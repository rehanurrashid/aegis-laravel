<!--
  pages/provider/JobPostings.vue — Support & Services postings (Practice tier).
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="My Practice"
      title="Support &amp; Services"
      subtitle="Post a job to find business partners — bookkeepers, billers, marketers, designers."
    >
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openModal('postJobModal')">
          <AegisIcon name="plus" :size="14" />
          <span>Post a job</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="briefcase" :value="postings.length" label="Open postings" />
      <AegisStatChip icon="file-pen"  :value="totalProposals"  label="Total proposals" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="agreement" :value="activeContracts" label="Active contracts" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
    </div>

    <div v-if="postings.length" class="postings-list">
      <article v-for="p in postings" :key="p.id" class="posting-card">
        <header class="posting-card-head">
          <a :href="route('provider.job-postings.show', { job: p.id })" class="posting-card-title">
            {{ p.title }}
          </a>
          <AegisBadge :label="p.status" :variant="statusVariant(p.status)" />
        </header>
        <div class="posting-card-meta">
          <span><AegisIcon name="dollar" :size="12" /> {{ rateLine(p) }}</span>
          <span><AegisIcon name="file-pen" :size="12" /> {{ p.proposal_count }} proposals</span>
          <span><AegisIcon name="calendar" :size="12" /> Posted {{ activity.timeAgo(p.created_at) }}</span>
        </div>
        <p class="posting-card-desc">{{ p.description }}</p>
        <footer class="posting-card-foot">
          <a :href="route('provider.job-postings.show', { job: p.id })" class="btn btn-outline btn-sm">
            View proposals ({{ p.proposal_count }})
          </a>
        </footer>
      </article>
    </div>

    <AegisEmptyState
      v-else
      icon="briefcase"
      title="No postings yet"
      description="Post your first job to start receiving proposals from vetted business partners."
    >
      <template #action>
        <button type="button" class="btn btn-primary" @click="openModal('postJobModal')">Post a job</button>
      </template>
    </AegisEmptyState>

    <!-- Post job modal (compact) -->
    <AegisModal
      :model-value="isOpen('postJobModal').value"
      title="Post a new job"
      size="lg"
      @update:model-value="(v) => !v && close()"
    >
      <form @submit.prevent="post">
        <div class="form-group">
          <label class="form-label">Title <span class="req">*</span></label>
          <input v-model="form.title" required class="form-input" placeholder="e.g. Bookkeeping for solo therapy practice" />
        </div>
        <div class="form-group">
          <label class="form-label">Description <span class="req">*</span></label>
          <textarea v-model="form.description" required class="form-input" rows="6"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Engagement</label>
            <select v-model="form.engagement" class="form-input">
              <option value="fixed">Fixed price</option>
              <option value="hourly">Hourly</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">{{ form.engagement === 'fixed' ? 'Budget ($)' : 'Rate range ($/hr)' }}</label>
            <input v-model.number="form.budget_input" type="number" min="0" class="form-input" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Tags</label>
          <input v-model="form.tags_input" type="text" class="form-input" placeholder="bookkeeping, quickbooks, monthly" />
        </div>
      </form>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="close">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="form.processing" @click="post">
          {{ form.processing ? 'Posting…' : 'Post job' }}
        </button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'
import { usePricingStore } from '@/stores/pricing'

defineProps({
  postings:        { type: Array, default: () => [] },
  totalProposals:  { type: Number, default: 0 },
  activeContracts: { type: Number, default: 0 },
})

const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const activity = useActivity()
const pricing = usePricingStore()

const form = useForm({
  title: '', description: '', engagement: 'fixed',
  budget_input: null, budget_cents: null, tags_input: '',
})

watch(() => form.budget_input, (v) => { form.budget_cents = v != null ? Math.round(Number(v) * 100) : null })

function rateLine(p) {
  if (p.engagement === 'fixed') return p.budget_cents ? `${pricing.formatCents(p.budget_cents)} fixed` : 'Fixed'
  return p.rate_min_cents && p.rate_max_cents
    ? `${pricing.formatCents(p.rate_min_cents)}–${pricing.formatCents(p.rate_max_cents)}/hr`
    : 'Hourly'
}
function statusVariant(s) { return { open: 'green', closed: 'neutral', draft: 'gold' }[s] ?? 'neutral' }

function close() { closeModal('postJobModal'); setTimeout(() => form.reset(), 200) }
function post() {
  form.post(route('provider.job-postings.store'), {
    preserveScroll: true,
    onSuccess: () => { toast.success('Job posted.'); close() },
  })
}
</script>
