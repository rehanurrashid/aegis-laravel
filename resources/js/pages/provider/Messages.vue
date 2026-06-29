<!-- pages/provider/Messages.vue -->
<!-- Stub — delegates entirely to pages/shared/Messages.vue -->
<template>
  <SharedPage v-bind="$props" />
</template>

<script setup>
import SharedPage from '@/pages/shared/Messages.vue'

defineProps({
  threads:             { type: Array,  default: () => [] },
  activeThread:        { type: Object, default: null },
  activeMessages:      { type: Array,  default: () => [] },
  recipients:          { type: Array,  default: () => [] },
  unreadCounts:        { type: Object, default: () => ({}) },
  buckets:             { type: Array,  default: () => [] },
  bucketCounts:        { type: Object, default: () => ({}) },
  currentUserId:       { type: String, default: '' },
  currentUserInitials: { type: String, default: 'U' },
})
</script>
