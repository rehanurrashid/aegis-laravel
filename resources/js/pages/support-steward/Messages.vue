<!-- pages/support-steward/Messages.vue -->
<!-- Stub — delegates entirely to pages/shared/Messages.vue -->
<!-- Controller renders 'Shared/Messages'; this file exists so Inertia's resolver has a file to find if needed. -->
<template>
  <SharedPage v-bind="$props" />
</template>

<script setup>
import SharedPage from '@/pages/shared/Messages.vue'

// Props copied exactly from pages/shared/Messages.vue defineProps
defineProps({
  threads:        { type: Array,  default: () => [] },
  activeThread:   { type: Object, default: null },
  activeMessages: { type: Array,  default: () => [] },
  recipients:     { type: Array,  default: () => [] },
  unreadCounts:   { type: Object, default: () => ({}) },
})
</script>
