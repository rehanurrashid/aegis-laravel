<!--
  pages/business-partner/Proposals.vue — proposals sent by this BP.

  Wave 1 fixes:
  - window.confirm() replaced with confirmAction(opts, cb)
  - bp.proposals.show (non-existent route) replaced with openDetail(p)
  - btn-sm / btn-xs violations replaced
  - Redundant AegisCard/AegisBadge/AegisEmptyState local imports removed (globally registered)
  - Tab classes: tab-btn/is-active → tabs-segmented/tab-pill pattern
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Work"
      title="My Proposals"
      :subtitle="`${proposals.length} proposal${proposals.length === 1 ? '' : 's'} sent.`"
    >
      <template #actions>
        <a :href="route('bp.jobs.index')" class="btn btn-primary">
          <AegisIcon name="search-lg" :size="14" />
          <span>Find more jobs</span>
        </a>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="hourglass"   :value="pendingCount"  label="Pending"  bg-color="var(--icon-bg-gold)"  icon-color="var(--gold-dark)" />
      <AegisStatChip icon="check-badge" :value="acceptedCount" label="Accepted" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="x-circle"    :value="declinedCount" label="Declined" bg-color="var(--icon-bg-red)"   icon-color="var(--red-dark)" />
      <AegisStatChip icon="file-pen"    :value="proposals.length" label="Total" />
    </div>

    <div class="tabs-segmented">
      <button
        v-for="t in tabs"
        :key="t.value"
        type="button"
        class="tab-pill"
        :class="{ 'is-active': tab === t.value }"
        @click="tab = t.value"
      >
        {{ t.label }}
      </button>
    </div>

    <AegisCard v-if="visible.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Job</th>
            <th>Client</th>
            <th>Bid</th>
            <th>Status</th>
            <th>Sent</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in visible" :key="p.id">
            <td class="data-table-primary">
              <button
                type="button"
                class="link-btn"
                @click="openDetail(p)"
              >{{ p.job_title }}</button>
            </td>
            <td>{{ p.client_name }}</td>
            <td>{{ pricing.formatCents(p.bid_cents) }}</td>
            <td>
              <AegisBadge :label="statusLabel(p.status)" :variant="statusVariant(p.status)" />
            </td>
            <td>{{ activity.timeAgo(p.created_at) }}</td>
            <td class="data-table-actions">
              <button
                type="button"
                class="btn btn-outline"
                @click="openDetail(p)"
              >View</button>
              <button
                v-if="p.status === 'pending'"
                type="button"
                class="btn btn-ghost btn-danger-ghost"
                @click="withdraw(p)"
              >
                Withdraw
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState
      v-else
      icon="file-pen"
      :title="tab === 'all' ? 'No proposals yet' : `No ${tab} proposals`"
      description="Browse open jobs and send your first proposal."
    >
      <template #action>
        <a :href="route('bp.jobs.index')" class="btn btn-primary">Find jobs</a>
      </template>
    </AegisEmptyState>

    <!-- Proposal detail modal -->
    <ProposalDetailModal :proposal="activeProposal" @withdraw="withdraw" />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router }        from '@inertiajs/vue3'
import AppLayout         from '@/layouts/AppLayout.vue'
import ProposalDetailModal from '@/components/modals/ProposalDetailModal.vue'
import { useConfirm }   from '@/composables/useConfirm'
import { useActivity }  from '@/composables/useActivity'
import { useToast }     from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({ proposals: { type: Array, default: () => [] } })

const { confirmAction } = useConfirm()
const activity = useActivity()
const toast    = useToast()
const pricing  = usePricingStore()

const tab = ref('all')
const activeProposal = ref(null)

const tabs = [
  { value: 'all',       label: 'All' },
  { value: 'pending',   label: 'Pending' },
  { value: 'accepted',  label: 'Accepted' },
  { value: 'declined',  label: 'Declined' },
  { value: 'withdrawn', label: 'Withdrawn' },
]

const visible = computed(() =>
  tab.value === 'all'
    ? props.proposals
    : props.proposals.filter((p) => p.status === tab.value),
)

const pendingCount  = computed(() => props.proposals.filter((p) => p.status === 'pending').length)
const acceptedCount = computed(() => props.proposals.filter((p) => p.status === 'accepted').length)
const declinedCount = computed(() => props.proposals.filter((p) => p.status === 'declined').length)

function statusLabel(s) {
  return { pending: 'Pending', under_review: 'Under Review', accepted: 'Accepted', declined: 'Declined', withdrawn: 'Withdrawn' }[s] ?? s
}

function statusVariant(s) {
  return { pending: 'gold', under_review: 'blue', accepted: 'green', declined: 'red', withdrawn: 'neutral' }[s] ?? 'neutral'
}

function openDetail(p) {
  activeProposal.value = p
}

function withdraw(p) {
  confirmAction(
    {
      title:        'Withdraw proposal?',
      message:      `Your proposal for "${p.job_title}" will be withdrawn. This cannot be undone.`,
      confirmLabel: 'Withdraw',
      destructive:  true,
    },
    () => router.delete(route('bp.proposals.withdraw', { proposal: p.id }), {
      preserveScroll: true,
      onSuccess: () => toast.success('Proposal withdrawn.'),
    }),
  )
}
</script>
