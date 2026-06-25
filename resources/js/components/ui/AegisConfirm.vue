<!--
  AegisConfirm.vue — confirmation dialog built on top of AegisModal.

  Use for any destructive or significant action that needs a second touch.
  Title is plain text (no icons inside .modal-title). Destructive flag swaps
  primary button to .btn-danger.

  Usage:
    <AegisConfirm
      v-model="showConfirm"
      title="Remove Continuity Steward?"
      message="Marcus will lose access immediately. This cannot be undone."
      confirm-label="Remove"
      destructive
      @confirm="onConfirm"
    />
-->
<template>
  <AegisModal
    :model-value="modelValue"
    :title="title"
    size="sm"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <p class="confirm-body">{{ message }}</p>

    <template #footer>
      <button
        type="button"
        class="btn btn-outline"
        @click="onCancel"
      >{{ cancelLabel }}</button>

      <button
        type="button"
        class="btn"
        :class="destructive ? 'btn-danger' : 'btn-primary'"
        :disabled="busy"
        @click="onConfirm"
      >{{ confirmLabel }}</button>
    </template>
  </AegisModal>
</template>

<script setup>
const props = defineProps({
  modelValue:   { type: Boolean, required: true },
  title:        { type: String,  default: 'Confirm' },
  message:      { type: String,  required: true },
  confirmLabel: { type: String,  default: 'Confirm' },
  cancelLabel:  { type: String,  default: 'Cancel' },
  destructive:  { type: Boolean, default: false },
  busy:         { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

function onConfirm() { emit('confirm') }

function onCancel() {
  emit('cancel')
  emit('update:modelValue', false)
}
</script>
