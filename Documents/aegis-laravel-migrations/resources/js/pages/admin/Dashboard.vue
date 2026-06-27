<!--
  pages/admin/Dashboard.vue — admin home with platform-wide stats.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Admin"
      title="Platform Overview"
      subtitle="Real-time view of the Aegis network."
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="users" :value="stats.total_users" label="Total users" />
      <AegisStatChip icon="user-md" :value="stats.providers" label="Practitioners" bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
      <AegisStatChip icon="users-network" :value="stats.stewards" label="Stewards (CS+SS)" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="briefcase" :value="stats.business_partners" label="Business partners" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
    </div>

    <div class="stat-chips-row">
      <AegisStatChip icon="alert-triangle" :value="stats.active_incidents" label="Active incidents" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
      <AegisStatChip icon="message" :value="stats.open_complaints" label="Open complaints" bg-color="var(--icon-bg-orange)" icon-color="var(--orange-dark)" />
      <AegisStatChip icon="dollar" :value="pricing.formatCents(stats.mrr_cents)" label="MRR" />
      <AegisStatChip icon="trending-up" :value="stats.signups_30d" label="Signups (30d)" />
    </div>

    <div class="dashboard-grid">
      <AegisCard title="Recent signups">
        <ul v-if="recentSignups.length" class="cs-provider-list">
          <li v-for="u in recentSignups" :key="u.id" class="cs-provider-row">
            <ProfileStrip :person="u" :kind="u.portal" />
          </li>
        </ul>
        <AegisEmptyState v-else icon="users" title="No recent signups" />
      </AegisCard>

      <AegisCard title="System activity">
        <ActivityFeed :events="recentEvents" compact />
      </AegisCard>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import ProfileStrip from '@/components/features/ProfileStrip.vue'
import ActivityFeed from '@/components/features/ActivityFeed.vue'
import { usePricingStore } from '@/stores/pricing'

defineProps({
  stats: { type: Object, default: () => ({ total_users: 0, providers: 0, stewards: 0, business_partners: 0, active_incidents: 0, open_complaints: 0, mrr_cents: 0, signups_30d: 0 }) },
  recentSignups: { type: Array, default: () => [] },
  recentEvents:  { type: Array, default: () => [] },
})
const pricing = usePricingStore()
</script>
