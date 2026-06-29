<!-- pages/continuity-steward/Overview.vue -->
<!-- Stub — delegates entirely to pages/shared/Overview.vue -->
<template>
  <SharedPage v-bind="$props" />
</template>

<script setup>
import SharedPage from '@/pages/shared/Overview.vue'

defineProps({
  role:        { type: String, default: '' },
  firstName:   { type: String, default: 'there' },
  heroEyebrow: { type: String, default: 'Welcome to Aegis' },
  heroSub:     { type: String, default: '' },
  notice:      { type: String, default: '' },
  checklist:   { type: Array,  default: () => [] },
  keyTerms:    { type: Array,  default: () => [] },
  whyCards:    { type: Array,  default: () => [] },
  howSteps:    { type: Array,  default: () => [] },
  faqs:        { type: Array,  default: () => [] },
})
</script>
