<!--
  AegisToast.vue — single toast pill, used by the toast stack in layouts.

  Replaces the showToast() UI from _shared.js. Toasts are spawned via the
  UI store (useToast composable). This component is dumb: it renders one
  toast with an icon + message + level styling.
-->
<template>
  <div class="toast" :class="`toast--${level}`" role="status">
    <AegisIcon :name="iconName" :size="16" />
    <span class="toast-message">{{ message }}</span>
    <button class="toast-close" type="button" aria-label="Dismiss" @click="$emit('dismiss')">
      <AegisIcon name="x" :size="12" />
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  message: { type: String, required: true },
  level:   { type: String, default: 'success', validator: (v) => ['success','error','info','warning'].includes(v) },
})
defineEmits(['dismiss'])

const iconName = computed(() => ({
  success: 'check-circle',
  error:   'alert-circle',
  info:    'info',
  warning: 'alert-triangle',
}[props.level]))
</script>
