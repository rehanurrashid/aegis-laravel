<!--
  pages/support-steward/Providers.vue — SS view of practitioners served.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Critical Moment Plans"
      title="My Practitioners"
      :subtitle="`${providers.length} practitioner${providers.length === 1 ? '' : 's'} under your support.`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="users" :value="providers.length" label="Active" />
      <AegisStatChip icon="alert-triangle" :value="incidentCount" label="In incident" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
    </div>

    <AegisCard v-if="providers.length">
      <table class="data-table">
        <thead><tr><th>Practitioner</th><th>CS</th><th>Status</th><th>Last contact</th><th></th></tr></thead>
        <tbody>
          <tr v-for="p in providers" :key="p.id">
            <td class="data-table-primary">
              <a :href="profileHref(p.slug, 'provider')">{{ p.display_name }}</a>
            </td>
            <td>{{ p.cs_name ?? '—' }}</td>
            <td>
              <AegisBadge :label="p.incident_active ? 'Incident' : 'Standby'" :variant="p.incident_active ? 'red' : 'green'" />
            </td>
            <td>{{ p.last_contact_at ? activity.timeAgo(p.last_contact_at) : '—' }}</td>
            <td><a :href="route('ss.providers.show', { provider: p.id })" class="btn btn-sm btn-outline">Open</a></td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="users" title="No practitioners yet" description="Practitioners who designate you appear here." />
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useActivity } from '@/composables/useActivity'
import { useProfileRoute } from '@/composables/useProfileRoute'

const props = defineProps({ providers: { type: Array, default: () => [] } })
const activity = useActivity()
const { profileHref } = useProfileRoute()
const incidentCount = computed(() => props.providers.filter((p) => p.incident_active).length)
</script>
