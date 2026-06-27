<!--
  pages/admin/Complaints.vue — complaints and support tickets oversight.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Admin"
      title="Complaints"
      :subtitle="`${openCount} open · ${urgentCount} urgent`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="alert-triangle" :value="urgentCount" label="Urgent" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
      <AegisStatChip icon="message" :value="openCount" label="Open" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="check-circle" :value="resolvedCount" label="Resolved (30d)" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
    </div>

    <nav class="tab-strip">
      <button v-for="t in ['open', 'urgent', 'resolved', 'all']" :key="t"
        type="button" class="tab-btn" :class="{ 'is-active': tab === t }" @click="tab = t">
        {{ t.charAt(0).toUpperCase() + t.slice(1) }}
      </button>
    </nav>

    <AegisCard v-if="visible.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Subject</th>
            <th>Reporter</th>
            <th>Severity</th>
            <th>Status</th>
            <th>Updated</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in visible" :key="c.id">
            <td class="data-table-primary">
              <a :href="route('admin.complaints.show', { complaint: c.id })">{{ c.subject }}</a>
            </td>
            <td>{{ c.reporter_name }}</td>
            <td><AegisBadge :label="c.severity" :variant="{ urgent: 'red', high: 'orange', medium: 'gold', low: 'neutral' }[c.severity] ?? 'neutral'" /></td>
            <td><AegisBadge :label="c.status" :variant="{ open: 'gold', in_progress: 'blue', resolved: 'green', closed: 'neutral' }[c.status] ?? 'neutral'" /></td>
            <td>{{ activity.timeAgo(c.updated_at) }}</td>
            <td>
              <a :href="route('admin.complaints.show', { complaint: c.id })" class="btn btn-sm btn-outline">Open</a>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="message" :title="`No ${tab} complaints`" />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useActivity } from '@/composables/useActivity'

const props = defineProps({ complaints: { type: Array, default: () => [] } })
const activity = useActivity()
const tab = ref('open')

const urgentCount   = computed(() => props.complaints.filter((c) => c.severity === 'urgent' && c.status !== 'resolved' && c.status !== 'closed').length)
const openCount     = computed(() => props.complaints.filter((c) => c.status === 'open' || c.status === 'in_progress').length)
const resolvedCount = computed(() => props.complaints.filter((c) => c.status === 'resolved').length)

const visible = computed(() => {
  if (tab.value === 'all')      return props.complaints
  if (tab.value === 'open')     return props.complaints.filter((c) => c.status === 'open' || c.status === 'in_progress')
  if (tab.value === 'urgent')   return props.complaints.filter((c) => c.severity === 'urgent')
  if (tab.value === 'resolved') return props.complaints.filter((c) => c.status === 'resolved')
  return []
})
</script>
