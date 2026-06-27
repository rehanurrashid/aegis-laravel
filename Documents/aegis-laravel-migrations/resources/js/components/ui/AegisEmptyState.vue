<!--
  AegisEmptyState.vue — empty state block.

  Uses .empty-state / .empty-state-icon / .empty-state-title /
  .empty-state-desc from _shared.css. Includes an optional CTA slot.

  Tone: MA'AT voice — measured, never marketing copy. Title is a short
  declarative phrase, description gives the next step.
-->
<template>
  <div class="empty-state">
    <div v-if="icon" class="empty-state-icon">
      <AegisIcon :name="icon" :size="32" />
    </div>
    <h3 v-if="title" class="empty-state-title">{{ title }}</h3>
    <p v-if="description" class="empty-state-desc">{{ description }}</p>
    <div v-if="$slots.action" class="empty-state-action">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon:        { type: String, default: 'inbox' },
  title:       { type: String, default: '' },
  description: { type: String, default: '' },
})
</script>
