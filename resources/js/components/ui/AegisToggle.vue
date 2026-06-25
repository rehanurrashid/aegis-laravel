<!--
  AegisToggle.vue — checkbox-style toggle switch.

  Uses .toggle / .toggle-input / .toggle-label from _shared.css. Two-way
  bound via v-model. Supports a label and an optional description line.

  Usage:
    <AegisToggle v-model="form.notify_email" label="Email notifications" />
-->
<template>
  <label class="toggle" :class="{ 'toggle--disabled': disabled }">
    <input
      type="checkbox"
      class="toggle-input"
      :checked="modelValue"
      :disabled="disabled"
      @change="$emit('update:modelValue', $event.target.checked)"
    />
    <span class="toggle-slider" aria-hidden="true"></span>
    <span v-if="label || description" class="toggle-label">
      <span class="toggle-label-title">{{ label }}</span>
      <span v-if="description" class="toggle-label-desc">{{ description }}</span>
    </span>
  </label>
</template>

<script setup>
defineProps({
  modelValue:  { type: Boolean, required: true },
  label:       { type: String,  default: '' },
  description: { type: String,  default: '' },
  disabled:    { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>
