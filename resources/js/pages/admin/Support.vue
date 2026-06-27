<!-- pages/admin/Support.vue -->
<!-- Stub — delegates entirely to pages/shared/Support.vue -->
<!-- Controller renders 'Shared/Support'; this file exists so Inertia's resolver has a file to find if needed. -->
<template>
  <SharedPage v-bind="$props" />
</template>

<script setup>
import SharedPage from '@/pages/shared/Support.vue'

// Props copied exactly from pages/shared/Support.vue defineProps
defineProps({
  tickets:      { type: Array, default: () => [] },
  helpArticles: { type: Array, default: () => [] },
  initialTab:   { type: String, default: 'tickets' },
})
</script>
