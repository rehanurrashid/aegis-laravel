<!--
  pages/business-partner/Proposals.vue — proposals sent by this BP.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Work"
      title="My Proposals"
      :subtitle="`${proposals.length} proposal${proposals.length === 1 ? '' : 's'} sent.`"
    >
      <template #actions>
        <a :href="route('bp.find-jobs')" class="btn btn-primary">
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

    <nav class="tab-strip">
      <button
        v-for="t in ['all', 'pending', 'accepted', 'declined']"
        :key="t"
        type="button"
        class="tab-btn"
        :class="{ 'is-active': tab === t }"
        @click="tab = t"
      >
        {{ t === 'all' ? 'All' : t.charAt(0).toUpperCase() + t.slice(1) }}
      </button>
    </nav>

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
              <a :href="route('bp.proposals.show', { proposal: p.id })">{{ p.job_title }}</a>
            </td>
            <td>{{ p.client_name }}</td>
            <td>{{ pricing.formatCents(p.bid_cents) }}</td>
            <td>
              <AegisBadge :label="p.status" :variant="statusVariant(p.status)" />
            </td>
            <td>{{ activity.timeAgo(p.created_at) }}</td>
            <td>
              <a :href="route('bp.proposals.show', { proposal: p.id })" class="btn btn-sm btn-outline">View</a>
              <button
                v-if="p.status === 'pending'"
                type="button"
                class="btn btn-sm btn-ghost btn-danger-ghost"
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
        <a :href="route('bp.find-jobs')" class="btn btn-primary">Find jobs</a>
      </template>
    </AegisEmptyState>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useActivity } from '@/composables/useActivity'
import { useToast } from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({ proposals: { type: Array, default: () => [] } })
const activity = useActivity()
const toast = useToast()
const pricing = usePricingStore()

const tab = ref('all')

const visible = computed(() =>
  tab.value === 'all' ? props.proposals : props.proposals.filter((p) => p.status === tab.value),
)
const pendingCount  = computed(() => props.proposals.filter((p) => p.status === 'pending').length)
const acceptedCount = computed(() => props.proposals.filter((p) => p.status === 'accepted').length)
const declinedCount = computed(() => props.proposals.filter((p) => p.status === 'declined').length)

function statusVariant(s) { return { pending: 'gold', accepted: 'green', declined: 'red', withdrawn: 'neutral' }[s] ?? 'neutral' }
function withdraw(p) {
  if (!window.confirm('Withdraw this proposal?')) return
  router.delete(route('bp.proposals.withdraw', { proposal: p.id }),
    { onSuccess: () => toast.success('Proposal withdrawn.') })
}
</script>
