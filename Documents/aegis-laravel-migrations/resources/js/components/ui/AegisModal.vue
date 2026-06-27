<!--
  AegisModal.vue — universal modal overlay.

  Replaces the .modal-overlay > .modal pattern repeated across every PHP
  portal page. Every modal in Aegis goes through this primitive — no
  per-page modal implementations.

  RULES (enforced):
    • Modal title is plain text only. NO icons inside .modal-title.
    • Body and footer are slots — page composes content freely.
    • Default body horizontal padding inherited from _shared.css.
    • Esc closes (when closeable). Overlay click closes (when closeOnOverlay).

  Sizes map to existing CSS classes: modal-sm, modal-md (default), modal-lg, modal-xl.
-->
<template>
  <Teleport to="body">
    <Transition name="modal-fade" appear>
      <div
        v-if="modelValue"
        class="modal-overlay"
        @click.self="onOverlayClick"
        @keydown.esc="onEsc"
        tabindex="-1"
        ref="overlay"
      >
        <div
          class="modal"
          :class="[`modal-${size}`]"
          role="dialog"
          aria-modal="true"
          :aria-label="title || 'Dialog'"
        >
          <div v-if="title || closeable" class="modal-header">
            <div class="modal-title">{{ title }}</div>
            <button
              v-if="closeable"
              class="modal-close"
              type="button"
              aria-label="Close"
              @click="close"
            >
              <AegisIcon name="x" :size="13" />
            </button>
          </div>

          <div class="modal-body">
            <slot />
          </div>

          <div v-if="$slots.footer" class="modal-footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue:     { type: Boolean, required: true },
  title:          { type: String,  default: '' },
  size:           { type: String,  default: 'md', validator: (v) => ['sm','md','lg','xl'].includes(v) },
  closeable:      { type: Boolean, default: true },
  closeOnOverlay: { type: Boolean, default: true },
  closeOnEsc:     { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'close'])
const overlay = ref(null)

function close() {
  emit('update:modelValue', false)
  emit('close')
}

function onOverlayClick() {
  if (props.closeOnOverlay && props.closeable) close()
}

function onEsc() {
  if (props.closeOnEsc && props.closeable) close()
}

// Focus the overlay when opened so Esc works without the user clicking inside.
watch(() => props.modelValue, async (val) => {
  if (val) {
    await nextTick()
    overlay.value?.focus()
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity var(--transition-fast);
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
