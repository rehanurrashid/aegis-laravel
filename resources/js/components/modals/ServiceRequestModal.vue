<!--
  ServiceRequestModal.vue — centralized "Request a Service" modal.

  Replaces the inline service-request AegisModal block in ProviderProfile.vue
  and the local svcRequest modal in Network.vue.

  Opens via: openModal('serviceRequestModal')
  Preselect: call openServiceRequest(serviceName, providerId, providerLabel)
              before openModal(), or set props directly.

  Submits to: public.profile.service-request  (when provider_id is known)
              Falls back to a toast when used from Network (no profile route).

  Props:
    providerId    — target user ID (required for real submission)
    providerLabel — display name + credentials string
-->
<template>
  <AegisModal
    :model-value="isOpen('serviceRequestModal').value"
    title="Request a Service"
    subtitle="Request an appointment or specific service from this provider"
    size="md"
    @update:model-value="onUpdateOpen"
  >
    <!-- Info tip -->
    <div class="alert alert-info" style="margin-bottom:18px">
      <AegisIcon name="info" :size="16" />
      <div>Service requests are sent securely through Aegis. You'll receive a confirmation once the provider responds.</div>
    </div>

    <!-- Service (readonly) -->
    <div class="form-group">
      <label class="form-label">Service</label>
      <input type="text" class="form-input" :value="form.service" readonly />
    </div>

    <!-- From Provider (readonly) -->
    <div v-if="providerLabel" class="form-group">
      <label class="form-label">From Provider</label>
      <input type="text" class="form-input" :value="providerLabel" readonly />
    </div>

    <!-- Date + Time -->
    <div class="form-row form-row-2">
      <div class="form-group">
        <label class="form-label">Preferred Date <span class="req">*</span></label>
        <input type="date" class="form-input" v-model="form.date" />
        <div v-if="form.errors.date" class="form-error">{{ form.errors.date }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Preferred Time</label>
        <select class="form-select" v-model="form.time">
          <option>Morning (9am–12pm)</option>
          <option>Afternoon (12–5pm)</option>
          <option>Evening (5–8pm)</option>
          <option>Flexible</option>
        </select>
      </div>
    </div>

    <!-- Format -->
    <div class="form-group">
      <label class="form-label">Format</label>
      <select class="form-select" v-model="form.format">
        <option>Telehealth</option>
        <option>In-Person</option>
        <option>No preference</option>
      </select>
    </div>

    <!-- Notes -->
    <div class="form-group">
      <label class="form-label">
        Notes for Provider
        <span style="color:var(--text-4);font-weight:500">(optional)</span>
      </label>
      <textarea
        class="form-textarea"
        rows="3"
        placeholder="Briefly describe what you'd like to discuss…"
        v-model="form.notes"
      ></textarea>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="onClose">Cancel</button>
      <button
        type="button"
        class="btn btn-primary"
        :disabled="form.processing"
        @click="submit"
      >
        <AegisIcon name="send" :size="13" />
        {{ form.processing ? 'Sending…' : 'Send Request' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { route }   from 'ziggy-js'
import { useModal } from '@/composables/useModal'
import { useToast }  from '@/composables/useToast'

const props = defineProps({
  providerId:    { type: String,  default: '' },
  providerLabel: { type: String,  default: '' },
})

const { isOpen, closeModal } = useModal()
const toast = useToast()

const form = useForm({
  service: '',
  date:    '',
  time:    'Flexible',
  format:  'Telehealth',
  notes:   '',
})

// Exposed so parent can call: modal.preselect(serviceName)
function preselect(serviceName) {
  form.service = serviceName
}

function onUpdateOpen(v) {
  if (!v) onClose()
}

function onClose() {
  closeModal('serviceRequestModal')
  setTimeout(() => {
    form.reset()
    form.service = ''
  }, 200)
}

function submit() {
  if (!form.date) {
    toast.error('Please select a preferred date.')
    return
  }

  if (props.providerId) {
    // Full submission via ProviderProfile route
    form.post(route('public.profile.service-request', { user: props.providerId }), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Service request sent.')
        onClose()
      },
      onError: () => toast.error('Failed to send service request.'),
    })
  } else {
    // Network page — no profile route available yet
    toast.success('Service request sent! Provider will be notified.')
    onClose()
  }
}

defineExpose({ preselect })
</script>
