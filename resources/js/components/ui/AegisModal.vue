<!--
  AegisModal.vue — universal modal overlay.

  TWO usage patterns:
  A) Store-driven (preferred): pass modal-id, open via ui.openModal('id')
       <AegisModal modal-id="myModal" title="My Modal">...</AegisModal>

  B) v-model (local state):
       <AegisModal v-model="showModal" title="My Modal">...</AegisModal>

  RULES:
    • Modal title is plain text only — NO icons inside .modal-title.
    • Body and footer are slots — page composes content freely.
    • Esc and overlay-click close by default.
    • Sizes: sm | md (default) | lg | xl
-->
<template>
  <Teleport to="body">
    <Transition name="modal-fade" appear>
      <div
        v-if="isOpen"
        class="modal-overlay open"
        @click.self="onOverlayClick"
        @keydown.esc="onEsc"
        tabindex="-1"
        ref="overlayRef"
        style="z-index:10000"
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
import { ref, computed, watch, nextTick } from 'vue'
import { useUiStore } from '@/stores/ui'
import AegisIcon from '@/components/ui/AegisIcon.vue'

const props = defineProps({
  modalId:        { type: String,  default: '' },     // store-driven pattern
  modelValue:     { type: Boolean, default: undefined }, // v-model pattern
  title:          { type: String,  default: '' },
  size:           { type: String,  default: 'md', validator: (v) => ['sm','md','lg','xl'].includes(v) },
  closeable:      { type: Boolean, default: true },
  closeOnOverlay: { type: Boolean, default: true },
  closeOnEsc:     { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'close'])
const overlayRef = ref(null)
const ui = useUiStore()

// Determine open state from either store or v-model
const isOpen = computed(() => {
  if (props.modalId) return ui.activeModal === props.modalId
  return props.modelValue === true
})

function close() {
  if (props.modalId) {
    ui.closeModal(props.modalId)
  } else {
    emit('update:modelValue', false)
  }
  emit('close')
}

function onOverlayClick() {
  if (props.closeOnOverlay && props.closeable) close()
}

function onEsc() {
  if (props.closeOnEsc && props.closeable) close()
}

// Focus overlay when opened so Esc works
watch(isOpen, async (val) => {
  if (val) {
    await nextTick()
    overlayRef.value?.focus()
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active { transition: opacity 0.18s ease; }
.modal-fade-enter-from,
.modal-fade-leave-to    { opacity: 0; }
</style>
