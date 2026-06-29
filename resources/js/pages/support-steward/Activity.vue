<!-- pages/support-steward/Activity.vue -->
<!-- Stub — delegates entirely to pages/shared/Activity.vue -->
<template>
  <SharedPage v-bind="$props" />
</template>

<script setup>
import SharedPage from '@/pages/shared/Activity.vue'

defineProps({
  events:         { type: Array,  default: () => [] },
  grouped:        { type: Object, default: () => ({ today: [], week: [], month: [] }) },
  pagination:     { type: Object, default: null },
  filters:        { type: Object, default: () => ({ module: '', severity: '', unread: '', event_type: '' }) },
  unreadCount:    { type: Number, default: 0 },
  criticalCount:  { type: Number, default: 0 },
  categoryCounts: { type: Array,  default: () => [] },
})
</script>
