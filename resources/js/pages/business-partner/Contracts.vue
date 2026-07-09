<!--
  pages/business-partner/Contracts.vue — active + completed contracts.
  Props from BP/ContractsController::index (uses ContractService::getForBp shaped rows).
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Work"
      title="Contracts"
      :subtitle="`${activeCount} active · ${completedCount} completed`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="agreement" :value="activeCount" label="Active" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="check-circle" :value="completedCount" label="Completed" />
      <AegisStatChip icon="dollar" :value="pricing.formatCents(totalEarned)" label="Total value (completed)" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
    </div>

    <nav class="tab-strip">
      <button type="button" class="tab-btn" :class="{ 'is-active': tab === 'active' }" @click="tab = 'active'">
        Active ({{ activeCount }})
      </button>
      <button type="button" class="tab-btn" :class="{ 'is-active': tab === 'completed' }" @click="tab = 'completed'">
        Completed ({{ completedCount }})
      </button>
    </nav>

    <AegisCard v-if="visible.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Contract</th>
            <th>Client</th>
            <th>Type</th>
            <th>Value</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in visible" :key="c.id">
            <td class="data-table-primary">{{ c.title }}</td>
            <td>{{ c.client_name }}</td>
            <td>{{ c.payment_type === 'milestone' ? 'Milestone-based' : 'One-time' }}</td>
            <td>{{ pricing.formatCents(c.amount_cents) }}</td>
            <td><AegisBadge :label="c.status" :variant="variant(c.status)" /></td>
            <td>
              <!--
                Contract detail view is not yet built for the BP portal.
                Milestone actions live on the Milestones page.
              -->
              <a :href="route('bp.milestones.index')" class="btn btn-sm btn-outline">
                <AegisIcon name="flag-2" :size="12" /> Milestones
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="agreement" :title="tab === 'active' ? 'No active contracts' : 'No completed contracts'" />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip   from '@/components/ui/AegisStatChip.vue'
import AegisCard       from '@/components/ui/AegisCard.vue'
import AegisBadge      from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisIcon       from '@/components/ui/AegisIcon.vue'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({ contracts: { type: Array, default: () => [] } })
const pricing = usePricingStore()
const tab = ref('active')

const activeCount    = computed(() => props.contracts.filter((c) => c.status === 'active').length)
const completedCount = computed(() => props.contracts.filter((c) => c.status === 'completed').length)
const totalEarned    = computed(() => props.contracts.filter((c) => c.status === 'completed').reduce((s, c) => s + (c.amount_cents || 0), 0))

const visible = computed(() => props.contracts.filter((c) => c.status === tab.value))

function variant(s) { return { active: 'green', completed: 'blue', cancelled: 'neutral' }[s] ?? 'neutral' }
</script>
