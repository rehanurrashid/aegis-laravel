<!--
  modals/AttestPlanModal.vue
  One-shot vault attestation — confirms vault_attested_at timestamp.
-->
<template>
  <AegisModal modal-id="attestPlanModal" size="md" title="Attest Vault Contents" subtitle="Confirm the vault accurately reflects your practice.">
    <template #body>
      <div class="alert alert-info">
        <AegisIcon name="info" :size="16" />
        <div>Attestation confirms to your Continuity Stewards that the Vault contains accurate, up-to-date information. This is required before your plan can be signed.</div>
      </div>
      <div class="form-group" style="margin-top:14px;margin-bottom:0">
        <label class="form-label">Optional note</label>
        <textarea
          v-model="form.note"
          class="form-input"
          rows="3"
          placeholder="e.g. Updated credential list and roster as of today…"
          style="resize:vertical"
        />
      </div>
    </template>
    <template #footer>
      <button type="button" class="btn btn-outline" @click="$emit('close')">Cancel</button>
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

const emit = defineEmits(['close'])

const form = useForm({ note: '' })
const submitting = ref(false)

function submit() {
  submitting.value = true
  form.post(route('provider.plan.attest'), {
    onSuccess: () => { submitting.value = false; emit('close') },
    onError:   () => { submitting.value = false },
  })
}
</script>
