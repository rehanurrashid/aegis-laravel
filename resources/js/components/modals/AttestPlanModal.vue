<!--
  modals/AttestPlanModal.vue — one-shot vault attestation.
  Usage: <AttestPlanModal v-model="showAttestModal" />
-->
<template>
  <AegisModal v-model="modelValue" size="md" title="Attest Vault Contents" @close="$emit('update:modelValue', false)">
    <div class="alert alert-info" style="margin-bottom:14px">
      <AegisIcon name="info" :size="16" />
      <div>Attestation confirms to your Continuity Stewards that the Vault contains accurate, up-to-date information. This is required before your plan can be signed.</div>
    </div>
    <div class="form-group" style="margin-bottom:0">
      <label class="form-label">Optional note</label>
      <textarea v-model="form.note" class="form-input" rows="3"
        placeholder="e.g. Updated credential list and roster as of today…" style="resize:vertical" />
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="$emit('update:modelValue', false)">Cancel</button>
      <button type="button" class="btn btn-primary btn-spin" :disabled="submitting" @click="submit">
        <AegisIcon v-if="submitting" name="refresh-cw" :size="14" class="spin" />
        <AegisIcon v-else name="check-circle" :size="14" />
        {{ submitting ? 'Attesting…' : 'Attest Vault' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({ modelValue: { type: Boolean, required: true } })
const emit = defineEmits(['update:modelValue'])

const form = useForm({ note: '' })
const submitting = ref(false)

function submit() {
  submitting.value = true
  form.post(route('provider.plan.attest'), {
    onSuccess: () => { submitting.value = false; emit('update:modelValue', false) },
    onError:   () => { submitting.value = false },
  })
}
</script>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin .7s linear infinite; }
</style>
