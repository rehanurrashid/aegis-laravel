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
        <a v-if="stats.pending_engagement_requests" :href="route('bp.engagement-requests.index')" class="btn-hero-ghost is-on-light">
          <AegisIcon name="briefcase" :size="14" />
          {{ stats.pending_engagement_requests }} Pending Request{{ stats.pending_engagement_requests !== 1 ? 's' : '' }}
        </a>
        <a :href="route('bp.jobs.index')" class="btn btn-primary">
          <AegisIcon name="search" :size="14" />
          <span>Find jobs</span>
        </a>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="briefcase"  :value="stats.pending_engagement_requests" label="Pending Requests" />
      <AegisStatChip icon="file-text"  :value="stats.active_contracts"   label="Active contracts" />
      <AegisStatChip icon="message-square" :value="stats.pending_proposals" label="Open proposals" />
      <AegisStatChip icon="dollar"     :value="pricing.formatCents(stats.ytd_cents)" label="YTD Earned" />
    </div>

    <div class="dashboard-grid">

      <!-- Pending engagement requests card -->
      <AegisCard v-if="pendingEngagementRequests.length" title="Engagement Requests">
        <template #actions>
          <a :href="route('bp.engagement-requests.index')" class="btn btn-outline btn-sm">View All</a>
        </template>
        <ul class="bp-contract-list">
          <li v-for="r in pendingEngagementRequests" :key="r.id" class="bp-contract-row">
            <div>
              <div class="bp-contract-title">{{ r.label }}</div>
              <div class="bp-contract-meta">From {{ r.from }} · {{ r.created_at }}</div>
            </div>
            <a :href="route('bp.engagement-requests.index')" class="btn btn-outline btn-sm">Review</a>
          </li>
        </ul>
      </AegisCard>

      <AegisCard title="Active contracts">
        <ul v-if="activeContracts.length" class="bp-contract-list">
          <li v-for="c in activeContracts" :key="c.id" class="bp-contract-row">
            <div>
              <div class="bp-contract-title">{{ c.title }}</div>
              <div class="bp-contract-meta">{{ c.status }}</div>
            </div>
            <a :href="route('bp.contracts.index')" class="btn btn-ghost btn-sm">Open</a>
          </li>
        </ul>
        <AegisEmptyState v-else icon="file-text" title="No active contracts" />
      </AegisCard>

      <AegisCard title="Recent activity">
        <ActivityFeed :events="recentActivity" compact />
      </AegisCard>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip from '@/components/ui/AegisStatChip.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import ActivityFeed from '@/components/features/ActivityFeed.vue'
import { useAuthStore } from '@/stores/auth'
import { usePricingStore } from '@/stores/pricing'

defineProps({
  stats: {
    type: Object,
    default: () => ({ pending_engagement_requests: 0, active_contracts: 0, pending_proposals: 0, ytd_cents: 0, open_invoices: 0 }),
  },
  activeContracts:            { type: Array, default: () => [] },
  pendingEngagementRequests:  { type: Array, default: () => [] },
  recentActivity:             { type: Array, default: () => [] },
})
const auth    = useAuthStore()
const pricing = usePricingStore()
</script>
