<!-- pages/business-partner/Activity.vue -->
<!-- Stub — delegates entirely to pages/shared/Activity.vue -->
<!-- Controller renders 'Shared/Activity'; this file exists so Inertia's resolver has a file to find if needed. -->
<template>
  <SharedPage v-bind="$props" />
</template>

<script setup>
import SharedPage from '@/pages/shared/Activity.vue'

// Props copied exactly from pages/shared/Activity.vue defineProps
defineProps({
  events:      { type: Array,  default: () => [] },
  pagination:  { type: Object, default: null },
  filters:     { type: Object, default: () => ({ module: '', severity: '', unread: '' }) },
  unreadCount: { type: Number, default: 0 },
})
</script>
