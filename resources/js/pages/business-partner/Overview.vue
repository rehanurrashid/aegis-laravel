<!-- pages/business-partner/Overview.vue -->
<!-- Stub — delegates entirely to pages/shared/Overview.vue -->
<!-- Controller renders 'Shared/Overview'; this file exists so Inertia's resolver has a file to find if needed. -->
<template>
  <SharedPage v-bind="$props" />
</template>

<script setup>
import SharedPage from '@/pages/shared/Overview.vue'

// Props copied exactly from pages/shared/Overview.vue defineProps
defineProps({
  role:     { type: String, default: '' },
  keyTerms: { type: Array,  default: () => [] },
  whyCards: { type: Array,  default: () => [] },
  howSteps: { type: Array,  default: () => [] },
  faqs:     { type: Array,  default: () => [] },
})
</script>
