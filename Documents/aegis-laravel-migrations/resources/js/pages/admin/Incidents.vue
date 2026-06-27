<!--
  pages/admin/Incidents.vue — platform-wide incident oversight.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Admin"
      title="Incident Oversight"
      :subtitle="`${activeCount} active critical incident${activeCount === 1 ? '' : 's'} platform-wide.`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="alert-triangle" :value="activeCount" label="Active" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
      <AegisStatChip icon="clock" :value="medianResolutionLabel" label="Median resolution" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="check-circle" :value="resolvedCount" label="Resolved (30d)" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
    </div>

    <AegisCard v-if="incidents.length" title="Incidents">
      <table class="data-table">
        <thead>
          <tr>
            <th>Practitioner</th>
            <th>CS</th>
            <th>Activated</th>
            <th>Status</th>
            <th>Duration</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="i in incidents" :key="i.id" :class="{ 'is-overdue': !i.resolved_at }">
            <td class="data-table-primary">{{ i.provider_name }}</td>
            <td>{{ i.cs_name ?? '—' }}</td>
            <td>{{ activity.timeAgo(i.activated_at) }}</td>
            <td><AegisBadge :label="i.resolved_at ? 'Resolved' : 'Active'" :variant="i.resolved_at ? 'green' : 'red'" /></td>
            <td>{{ duration(i) }}</td>
            <td><a :href="route('admin.incidents.show', { incident: i.id })" class="btn btn-sm btn-outline">Audit</a></td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="shield" title="No incidents on record" />
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useActivity } from '@/composables/useActivity'

const props = defineProps({
  incidents:        { type: Array,  default: () => [] },
  medianResolutionLabel: { type: String, default: '—' },
})
const activity = useActivity()

const activeCount   = computed(() => props.incidents.filter((i) => !i.resolved_at).length)
const resolvedCount = computed(() => props.incidents.filter((i) =>  i.resolved_at).length)

function duration(i) {
  const start = new Date(i.activated_at)
  const end   = i.resolved_at ? new Date(i.resolved_at) : new Date()
  const ms = end - start
  const hrs = Math.floor(ms / 3600000)
  if (hrs < 24) return `${hrs}h`
  return `${Math.floor(hrs / 24)}d ${hrs % 24}h`
}
</script>
