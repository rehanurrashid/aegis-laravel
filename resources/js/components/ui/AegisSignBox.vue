<!--
  AegisSignBox.vue — global one-click digital signature box.

  Usage:
    <AegisSignBox v-model="signed" :signer-name="providerName" />
    <AegisSignBox v-model="signed" :signer-name="providerName" label="Sign to confirm" />

  Props:
    modelValue   Boolean  — whether the user has signed (v-model)
    signerName   String   — display name shown in signed state ("Signed by Dr. Smith")
    label        String   — override the unsigned CTA label
    sublabel     String   — override the sub-label text below the CTA

  Emits:
    update:modelValue   — toggled on click
-->
<template>
  <div
    class="aegis-sign-box"
    :class="{ 'is-signed': modelValue }"
    role="button"
    tabindex="0"
    :aria-pressed="modelValue"
    @click="toggle"
    @keydown.enter.prevent="toggle"
    @keydown.space.prevent="toggle"
  >
    <!-- Icon -->
    <div class="aegis-sign-box-icon">
      <AegisIcon v-if="modelValue" name="check-circle" :size="22" />
      <AegisIcon v-else name="file-pen" :size="22" />
    </div>

    <!-- Labels -->
    <div class="aegis-sign-box-body">
      <div class="aegis-sign-box-title">
        {{ modelValue ? signedLabel : unsignedLabel }}
      </div>
      <div v-if="modelValue && signerName" class="aegis-sign-box-signer">
        Signed as <strong>{{ signerName }}</strong>
      </div>
      <div v-else class="aegis-sign-box-sub">{{ computedSublabel }}</div>
    </div>

    <!-- Right indicator -->
    <div class="aegis-sign-box-status">
      <span v-if="modelValue" class="aegis-sign-box-check">
        <AegisIcon name="check" :size="12" />
      </span>
      <span v-else class="aegis-sign-box-caret">
        <AegisIcon name="chevron-right" :size="14" />
      </span>
    </div>
  </div>
</template>

<script setup>
import AegisIcon from '@/components/ui/AegisIcon.vue'

const props = defineProps({
  modelValue:  { type: Boolean, default: false },
  signerName:  { type: String,  default: '' },
  label:       { type: String,  default: '' },
  sublabel:    { type: String,  default: '' },
})

const emit = defineEmits(['update:modelValue'])

function toggle() {
  emit('update:modelValue', !props.modelValue)
}

const unsignedLabel    = props.label    || 'Click to apply your digital signature'
const computedSublabel = props.sublabel || 'By signing, you confirm all details above are accurate'
const signedLabel      = 'Signature applied — click to remove'
</script>

<style scoped>
.aegis-sign-box {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border: 1.5px dashed var(--border-dark);
  border-radius: var(--radius-lg);
  background: var(--surface-2);
  cursor: pointer;
  transition: border-color var(--transition), background var(--transition), box-shadow var(--transition);
  outline: none;
  user-select: none;
}

.aegis-sign-box:hover {
  border-color: var(--gold);
  background: rgba(196, 129, 62, 0.04);
}

.aegis-sign-box:focus-visible {
  box-shadow: var(--focus-ring);
  border-color: var(--gold-dark);
}

/* Signed state */
.aegis-sign-box.is-signed {
  border-style: solid;
  border-color: var(--green-dark);
  background: var(--green-light, #f0fdf4);
}

.aegis-sign-box.is-signed:hover {
  background: #e6f9f0;
  border-color: var(--green-dark);
}

/* Icon box */
.aegis-sign-box-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--radius);
  background: var(--gold-dark);
  color: var(--text-inverted);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background var(--transition);
}

.aegis-sign-box.is-signed .aegis-sign-box-icon {
  background: var(--green-dark);
}

/* Body */
.aegis-sign-box-body {
  flex: 1;
  min-width: 0;
}

.aegis-sign-box-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  line-height: 1.3;
}

.aegis-sign-box.is-signed .aegis-sign-box-title {
  color: var(--green-dark);
}

.aegis-sign-box-sub {
  font-size: 12px;
  color: var(--text-4);
  margin-top: 2px;
}

.aegis-sign-box-signer {
  font-size: 12px;
  color: var(--green-dark);
  margin-top: 2px;
}

/* Right status */
.aegis-sign-box-status {
  flex-shrink: 0;
}

.aegis-sign-box-check {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: var(--radius-full);
  background: var(--green-dark);
  color: var(--text-inverted);
}

.aegis-sign-box-caret {
  color: var(--text-4);
}
</style>
