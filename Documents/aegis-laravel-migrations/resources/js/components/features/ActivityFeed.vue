<!--
  ActivityFeed.vue — recent activity feed widget.

  Replaces the inline render in _shared/templates/activity.php. Used on
  every Dashboard's "Recent Activity" card plus the full Activity Log page.

  Severity classes (info / warning / error / critical) map to CSS modifiers
  in _shared.css. Empty state mirrors the standard AegisEmptyState pattern.

  Props:
    events    — array of { id, title, description?, severity, module, created_at, read_at?, href? }
    compact   — narrow variant for dashboard widget
    showEmpty — render empty state when no events (default true)
-->
<template>
  <div class="activity-feed" :class="{ 'activity-feed--compact': compact }">
    <component
      v-for="event in events"
      :key="event.id"
      :is="event.href ? 'a' : 'div'"
      :href="event.href || undefined"
      class="activity-item"
      :class="[activity.severityClass(event.severity), { 'is-unread': !event.read_at }]"
    >
      <div class="activity-item-icon">
        <AegisIcon :name="iconFor(event)" :size="16" />
      </div>
      <div class="activity-item-body">
        <div class="activity-item-title">{{ event.title }}</div>
        <div v-if="event.description && !compact" class="activity-item-desc">
          {{ event.description }}
        </div>
        <div class="activity-item-time">{{ activity.timeAgo(event.created_at) }}</div>
      </div>
      <span v-if="!event.read_at" class="activity-item-unread-dot" aria-label="Unread"></span>
    </component>

    <AegisEmptyState
      v-if="showEmpty && !events.length"
      icon="activity"
      title="No recent activity"
      description="New events from your stewards, vault, and incidents will appear here."
    />
  </div>
</template>

<script setup>
import { useActivity } from '@/composables/useActivity'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'

const props = defineProps({
  events:    { type: Array,   default: () => [] },
  compact:   { type: Boolean, default: false },
  showEmpty: { type: Boolean, default: true },
})

const activity = useActivity()

function iconFor(event) {
  if (event.icon) return event.icon
  if (event.severity === 'critical') return 'alert-triangle'
  if (event.module) return activity.iconForEventType(event.module)
  return 'dot'
}
</script>
