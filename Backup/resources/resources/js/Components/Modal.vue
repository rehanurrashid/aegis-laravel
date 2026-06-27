<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="modal-overlay open"
      :id="id"
      @click.self="close"
    >
      <div class="modal" :class="sizeClass" role="dialog" aria-modal="true">
        <div class="modal-header" :style="headerStyle">
          <div style="flex:1;min-width:0">
            <div class="modal-title" :style="titleStyle">{{ title }}</div>
            <div v-if="subtitle" class="modal-subtitle">{{ subtitle }}</div>
          </div>
          <button class="modal-close" type="button" @click="close" aria-label="Close">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body" :style="bodyStyle">
          <slot />
        </div>
        <div v-if="$slots.footer" class="modal-footer" :style="footerStyle">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, watch, onBeforeUnmount } from 'vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  id: { type: String, default: undefined },
  size: { type: String, default: '' }, // '', 'sm', 'lg'
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  headerStyle: { type: [String, Object], default: undefined },
  titleStyle: { type: [String, Object], default: undefined },
  bodyStyle: { type: [String, Object], default: undefined },
  footerStyle: { type: [String, Object], default: undefined },
});

const emit = defineEmits(['update:modelValue', 'close']);

const sizeClass = computed(() => (props.size ? `modal-${props.size}` : ''));

function close() {
  emit('update:modelValue', false);
  emit('close');
}

function onKey(e) {
  if (e.key === 'Escape' && props.modelValue) close();
}

watch(
  () => props.modelValue,
  (open) => {
    if (typeof document === 'undefined') return;
    if (open) {
      document.addEventListener('keydown', onKey);
      document.body.style.overflow = 'hidden';
    } else {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = '';
    }
  }
);

onBeforeUnmount(() => {
  if (typeof document === 'undefined') return;
  document.removeEventListener('keydown', onKey);
  document.body.style.overflow = '';
});
</script>
