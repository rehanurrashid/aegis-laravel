<!--
  pages/support-steward/CriticalIncidentLog.vue — chronological incident history.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Activation"
      title="Critical Incident Log"
      subtitle="Active and resolved critical incidents you're supporting."
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="alert-triangle" :value="activeCount" label="Active" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
      <AegisStatChip icon="check-circle" :value="resolvedCount" label="Resolved" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
    </div>

    <AegisCard v-if="incidents.length" title="Incidents">
      <ol class="incident-log">
        <li
          v-for="i in incidents"
          :key="i.id"
          class="incident-log-row"
          :class="{ 'is-active': !i.resolved_at }"
        >
          <div class="incident-log-marker">
            <AegisIcon :name="i.resolved_at ? 'check-circle' : 'alert-triangle'" :size="16" />
          </div>
          <div class="incident-log-body">
            <div class="incident-log-head">
              <div class="incident-log-title">{{ i.title }}</div>
              <AegisBadge
                :label="i.resolved_at ? 'Resolved' : 'Active'"
                :variant="i.resolved_at ? 'green' : 'red'"
              />
            </div>
            <div class="incident-log-meta">
              For <strong>{{ i.provider_name }}</strong>
              · Activated {{ activity.timeAgo(i.activated_at) }}
              <span v-if="i.resolved_at"> · Resolved {{ activity.timeAgo(i.resolved_at) }}</span>
            </div>
            <a :href="route('ss.incident-log.show', { incident: i.id })" class="btn btn-ghost btn-sm">
              View timeline <AegisIcon name="arrow-right" :size="13" />
            </a>
          </div>
        </li>
      </ol>
    </AegisCard>

    <AegisEmptyState v-else icon="shield" title="No incidents recorded" description="When practitioners activate continuity support, the incidents will appear here." />
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useActivity } from '@/composables/useActivity'

const props = defineProps({ incidents: { type: Array, default: () => [] } })
const activity = useActivity()
const activeCount = computed(() => props.incidents.filter((i) => !i.resolved_at).length)
const resolvedCount = computed(() => props.incidents.filter((i) => i.resolved_at).length)
</script>
