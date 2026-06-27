<!-- pages/admin/HelpCenter.vue -->
<!-- Stub — delegates entirely to pages/shared/HelpCenter.vue -->
<!-- Controller renders 'admin/HelpCenter' with articles prop (role-filtered). -->
<template>
  <SharedHelpCenter v-bind="$props" />
</template>

<script setup>
import SharedHelpCenter from '@/pages/shared/HelpCenter.vue'

// Props copied exactly from pages/shared/HelpCenter.vue defineProps
defineProps({
  articles: { type: Array, default: () => [] },
})
</script>
