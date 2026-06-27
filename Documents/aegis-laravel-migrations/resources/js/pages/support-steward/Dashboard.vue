<!--
  pages/support-steward/Dashboard.vue — SS portal home.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Welcome back"
      :title="`Hello, ${auth.user?.first_name ?? 'Steward'}.`"
      subtitle="Non-clinical continuity support — logistics, communications, and operations."
    >
      <template #actions>
        <a :href="route('ss.tasks')" class="btn btn-primary">
          <AegisIcon name="tasks" :size="14" />
          <span>My tasks</span>
        </a>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="users" :value="stats.providers" label="Practitioners served" />
      <AegisStatChip icon="alert-triangle" :value="stats.active_incidents" label="Active incidents" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
      <AegisStatChip icon="tasks" :value="stats.open_tasks" label="Open tasks" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="users-network" :value="stats.cs_partners" label="CS partners" bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
    </div>

    <div class="dashboard-grid">
      <AegisCard title="Practitioners">
        <template #actions>
          <a :href="route('ss.providers')" class="btn btn-ghost btn-sm">View all <AegisIcon name="arrow-right" :size="13" /></a>
        </template>
        <ul v-if="recentProviders.length" class="cs-provider-list">
          <li v-for="p in recentProviders" :key="p.id" class="cs-provider-row">
            <ProfileStrip :person="p" kind="provider" />
          </li>
        </ul>
        <AegisEmptyState v-else icon="users" title="No practitioners yet" />
      </AegisCard>

      <AegisCard title="Recent activity">
        <template #actions>
          <a :href="route('ss.activity')" class="btn btn-ghost btn-sm">View all <AegisIcon name="arrow-right" :size="13" /></a>
        </template>
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
import { useAuthStore } from '@/stores/auth'

defineProps({
  stats:           { type: Object, default: () => ({ providers: 0, active_incidents: 0, open_tasks: 0, cs_partners: 0 }) },
  recentProviders: { type: Array,  default: () => [] },
  recentEvents:    { type: Array,  default: () => [] },
})
const auth = useAuthStore()
</script>
