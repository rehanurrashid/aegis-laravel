<!--
  modals/AttestPlanModal.vue — one-shot vault attestation.
  Usage: <AttestPlanModal v-model="showAttestModal" />
-->
<template>
  <AegisModal :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" size="md" title="Attest Vault Contents">
    <div class="alert alert-info" style="margin-bottom:14px">
      <AegisIcon name="info" :size="16" />
      <div>Attestation confirms to your Continuity Stewards that the Vault contains accurate, up-to-date information. This is required before your plan can be signed.</div>
    </div>
    <div class="form-group" style="margin-bottom:0">
      <label class="form-label">Optional note</label>
      <textarea v-model="note" class="form-input" rows="3"
        placeholder="e.g. Updated credential list and roster as of today…" style="resize:vertical" />
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="$emit('update:modelValue', false)">Cancel</button>
      <button type="button" class="btn btn-primary"  :disabled="submitting" @click="submit">
        <span v-if="submitting" class="spinner spinner-sm" />
        <AegisIcon v-else name="check-circle" :size="14" />
        {{ submitting ? 'Attesting…' : 'Attest Vault' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ modelValue: { type: Boolean, required: true } })
const emit  = defineEmits(['update:modelValue'])

const note       = ref('')
const submitting = ref(false)

function submit() {
  submitting.value = true
  router.post(
    route('provider.plan.attest'),
    { note: note.value },
    {
      preserveScroll: false,
      preserveState:  false,
      onSuccess: () => {
        submitting.value = false
        note.value = ''
        emit('update:modelValue', false)
      },
      onError:  () => { submitting.value = false },
      onFinish: () => { submitting.value = false },
    }
  )
}
</script>

<style scoped>
</style>
