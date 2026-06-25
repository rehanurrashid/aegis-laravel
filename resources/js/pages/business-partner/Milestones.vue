<!--
  pages/business-partner/Milestones.vue — work milestones across contracts.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Work"
      title="Milestones"
      :subtitle="`${openCount} open · ${overdueCount} overdue`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="flag-2" :value="openCount" label="Open" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="alert-triangle" :value="overdueCount" label="Overdue" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
      <AegisStatChip icon="check-circle" :value="completedCount" label="Completed" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="dollar" :value="pricing.formatCents(openValue)" label="Open value" />
    </div>

    <AegisCard v-if="milestones.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Milestone</th>
            <th>Contract</th>
            <th>Due</th>
            <th>Amount</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in milestones" :key="m.id" :class="{ 'is-overdue': isOverdue(m) }">
            <td class="data-table-primary">{{ m.title }}</td>
            <td>
              <a :href="route('bp.contracts.show', { contract: m.contract_id })">{{ m.contract_title }}</a>
            </td>
            <td>{{ m.due_at ? activity.timeAgo(m.due_at) : '—' }}</td>
            <td>{{ pricing.formatCents(m.amount_cents) }}</td>
            <td><AegisBadge :label="m.status" :variant="variant(m.status)" /></td>
            <td>
              <button
                v-if="m.status === 'open'"
                type="button"
                class="btn btn-sm btn-primary"
                @click="submit(m)"
              >
                Submit
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="flag-2" title="No milestones yet" description="Milestones appear as you sign contracts." />
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useActivity } from '@/composables/useActivity'
import { useToast } from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({ milestones: { type: Array, default: () => [] } })
const activity = useActivity()
const toast = useToast()
const pricing = usePricingStore()

const openCount      = computed(() => props.milestones.filter((m) => m.status === 'open').length)
const completedCount = computed(() => props.milestones.filter((m) => m.status === 'completed').length)
const overdueCount   = computed(() => props.milestones.filter((m) => isOverdue(m)).length)
const openValue      = computed(() => props.milestones.filter((m) => m.status === 'open').reduce((s, m) => s + (m.amount_cents || 0), 0))

function isOverdue(m) { return m.status === 'open' && m.due_at && new Date(m.due_at) < Date.now() }
function variant(s) { return { open: 'gold', submitted: 'blue', completed: 'green', cancelled: 'neutral' }[s] ?? 'neutral' }

function submit(m) {
  router.post(route('bp.milestones.submit', { milestone: m.id }), {}, {
    onSuccess: () => toast.success('Milestone submitted for review.'),
  })
}
</script>
