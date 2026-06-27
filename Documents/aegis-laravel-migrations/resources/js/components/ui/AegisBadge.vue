<!--
  AegisBadge.vue — status / role pill.

  Uses .badge plus an optional variant modifier. Variants map 1:1 to the
  CSS classes defined in _shared.css (badge--gold, badge--green, etc).

  Pass an optional icon (rendered before the label) — kept small (12px).
-->
<template>
  <span class="badge" :class="variantClass">
    <AegisIcon v-if="icon" :name="icon" :size="12" />
    <span class="badge-label">{{ label }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label:   { type: String,  required: true },
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) => [
      'neutral','gold','green','red','blue','purple','teal','orange','soft',
    ].includes(v),
  },
  icon: { type: String, default: '' },
})

const variantClass = computed(() => `badge--${props.variant}`)
</script>
