<!--
  pages/support-steward/MyTasks.vue — SS task list (mirrors CS MyTasks).
-->
<template>
  <AppLayout>
    <AegisHeroBanner eyebrow="Activation" title="My Tasks" :subtitle="`${openCount} open · ${doneCount} completed`" />

    <div class="stat-chips-row">
      <AegisStatChip icon="alert-triangle" :value="criticalCount" label="Critical" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
      <AegisStatChip icon="clock" :value="dueSoonCount" label="Due soon" bg-color="var(--icon-bg-orange)" icon-color="var(--orange-dark)" />
      <AegisStatChip icon="tasks" :value="openCount" label="Open" />
      <AegisStatChip icon="check-circle" :value="doneCount" label="Completed" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
    </div>

    <AegisCard title="Tasks">
      <ul v-if="tasks.length" class="task-list">
        <li v-for="t in tasks" :key="t.id" class="task-row" :class="{ 'is-done': t.completed_at }">
          <button type="button" class="task-checkbox" @click="toggle(t)">
            <AegisIcon :name="t.completed_at ? 'check-circle' : 'circle'" :size="16" />
          </button>
          <div class="task-body">
            <div class="task-title">{{ t.title }}</div>
            <div class="task-meta">
              <AegisBadge :label="t.priority" :variant="priorityVariant(t.priority)" />
              <span v-if="t.provider_name">for {{ t.provider_name }}</span>
              <span v-if="t.due_at" :class="{ 'task-overdue': isOverdue(t) }">due {{ activity.timeAgo(t.due_at) }}</span>
            </div>
          </div>
        </li>
      </ul>
      <AegisEmptyState v-else icon="tasks" title="No tasks right now" />
    </AegisCard>
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

const props = defineProps({ tasks: { type: Array, default: () => [] } })
const activity = useActivity()
const openCount = computed(() => props.tasks.filter((t) => !t.completed_at).length)
const doneCount = computed(() => props.tasks.filter((t) => t.completed_at).length)
const criticalCount = computed(() => props.tasks.filter((t) => t.priority === 'critical' && !t.completed_at).length)
const dueSoonCount  = computed(() => props.tasks.filter((t) => t.due_at && !t.completed_at && new Date(t.due_at) - Date.now() < 86400000 * 3).length)
function priorityVariant(p) { return { critical: 'red', high: 'orange', medium: 'gold', low: 'neutral' }[p] ?? 'neutral' }
function isOverdue(t) { return t.due_at && new Date(t.due_at) < Date.now() && !t.completed_at }
function toggle(t) { router.post(route('ss.tasks.toggle', { task: t.id }), {}, { preserveScroll: true }) }
</script>
