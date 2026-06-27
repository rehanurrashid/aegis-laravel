<!--
  pages/business-partner/Dashboard.vue — BP portal home.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Welcome back"
      :title="`Hello, ${auth.user?.first_name ?? 'Partner'}.`"
      :subtitle="auth.isAgency ? 'Your agency at a glance.' : 'Your freelance work at a glance.'"
    >
      <template #actions>
        <a :href="route('bp.find-jobs')" class="btn btn-primary">
          <AegisIcon name="search-lg" :size="14" />
          <span>Find jobs</span>
        </a>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="search-lg" :value="stats.open_proposals" label="Open proposals" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="agreement" :value="stats.active_contracts" label="Active contracts" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="flag-2" :value="stats.open_milestones" label="Open milestones" bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
      <AegisStatChip icon="dollar" :value="pricing.formatCents(stats.outstanding_cents)" label="Outstanding" />
    </div>

    <div class="dashboard-grid">
      <AegisCard title="Active contracts">
        <ul v-if="activeContracts.length" class="bp-contract-list">
          <li v-for="c in activeContracts" :key="c.id" class="bp-contract-row">
            <div>
              <div class="bp-contract-title">{{ c.title }}</div>
              <div class="bp-contract-meta">with {{ c.client_name }} · {{ pricing.formatCents(c.amount_cents) }}</div>
            </div>
            <a :href="route('bp.contracts.show', { contract: c.id })" class="btn btn-ghost btn-sm">Open</a>
          </li>
        </ul>
        <AegisEmptyState v-else icon="agreement" title="No active contracts" />
      </AegisCard>

      <AegisCard title="Recent activity">
        <ActivityFeed :events="recentEvents" compact />
      </AegisCard>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import ActivityFeed from '@/components/features/ActivityFeed.vue'
import { useAuthStore } from '@/stores/auth'
import { usePricingStore } from '@/stores/pricing'

defineProps({
  stats: { type: Object, default: () => ({ open_proposals: 0, active_contracts: 0, open_milestones: 0, outstanding_cents: 0 }) },
  activeContracts: { type: Array, default: () => [] },
  recentEvents:    { type: Array, default: () => [] },
})
const auth = useAuthStore()
const pricing = usePricingStore()
</script>
