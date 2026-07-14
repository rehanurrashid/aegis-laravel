<!--
  AegisToggle.vue — checkbox-style toggle switch.

  Uses .toggle / .toggle-input / .toggle-label from _shared.css. Two-way
  bound via v-model. Supports a label and an optional description line.

  NOTE: _shared.css sizes `label.toggle` as a fixed 42×24px switch only —
  the switch and its text label are rendered as flex siblings here (not
  nested inside the same <label>) so the text doesn't get clipped/squashed
  into the switch's fixed dimensions.

  Usage:
    <AegisToggle v-model="form.notify_email" label="Email notifications" />
-->
<template>
  <div class="aegis-toggle-row" :class="{ 'toggle--disabled': disabled }">
    <label class="toggle">
      <input
        type="checkbox"
        class="toggle-input"
        :checked="modelValue"
        :disabled="disabled"
        @change="$emit('update:modelValue', $event.target.checked)"
      />
      <span class="toggle-slider" aria-hidden="true"></span>
    </label>
    <span v-if="label || description" class="toggle-label">
      <span class="toggle-label-title">{{ label }}</span>
      <span v-if="description" class="toggle-label-desc">{{ description }}</span>
    </span>
  </div>
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

<style scoped>
.aegis-toggle-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
}
.aegis-toggle-row.toggle--disabled { opacity: 0.55; pointer-events: none; }
/* When disabled but ON — render gold so it reads as "locked ON" not "off" */
.aegis-toggle-row.toggle--disabled .toggle-input:checked + .toggle-slider { background: var(--gold-dark); opacity: 1; }
.toggle-label-title { display: block; }
.toggle-label-desc { display: block; margin-top: 2px; }
</style>
