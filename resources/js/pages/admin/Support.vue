<!-- pages/admin/Support.vue -->
<!-- Stub — delegates entirely to pages/shared/Support.vue -->
<template>
  <SharedPage v-bind="$props" />
</template>

<script setup>
import SharedPage from '@/pages/shared/Support.vue'

defineProps({
  tickets:         { type: Array,  default: () => [] },
  feedbackHistory: { type: Array,  default: () => [] },
  helpByCategory:  { type: Array,  default: () => [] },
  ticketReplies:   { type: Object, default: () => ({}) },
  openCount:       { type: Number, default: 0 },
  resolvedCount:   { type: Number, default: 0 },
  currentUserId:   { type: String, default: '' },
})
</script>
