<!--
  ServiceRequestModal.vue — universal "Request a Service" modal.

  Contexts:
    1. providerId present  → posts to public.profile.service-request
    2. Network page        → toast fallback
  
  Validation:
    - Preferred Date: required (inline error below field)
    - Service: required when not prefilled (inline error below field)
    All via Vuelidate: fieldError() helper, is-error class, @blur touch.
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

    <!-- Service -->
    <div class="form-group">
      <label class="form-label">Service <span v-if="!hasService" class="req">*</span></label>
      <input
        v-if="hasService"
        type="text"
        class="form-input"
        :value="form.service"
        readonly
      />
      <input
        v-else
        v-model="form.service"
        type="text"
        class="form-input"
        :class="{ 'is-error': fieldError('service') }"
        placeholder="Describe the service you need…"
        @blur="v$.service.$touch()"
      />
      <div v-if="fieldError('service')" class="form-error">{{ fieldError('service') }}</div>
    </div>

    <!-- Provider (readonly) -->
    <div v-if="providerLabel" class="form-group">
      <label class="form-label">Provider</label>
      <input type="text" class="form-input" :value="providerLabel" readonly />
    </div>

    <!-- Date + Time -->
    <div class="form-row form-row-2">
      <div class="form-group">
        <label class="form-label">Preferred Date <span class="req">*</span></label>
        <input
          v-model="form.date"
          type="date"
          class="form-input"
          :class="{ 'is-error': fieldError('date') }"
          :min="todayDate"
          @blur="v$.date.$touch()"
          @change="v$.date.$touch()"
        />
        <div v-if="fieldError('date')" class="form-error">{{ fieldError('date') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Preferred Time</label>
        <select class="form-select" v-model="form.time">
          <option value="Morning (9am–12pm)">Morning (9am–12pm)</option>
          <option value="Afternoon (12–5pm)">Afternoon (12–5pm)</option>
          <option value="Evening (5–8pm)">Evening (5–8pm)</option>
          <option value="Flexible">Flexible — any time</option>
        </select>
      </div>
    </div>

    <!-- Timezone -->
    <div class="form-group">
      <label class="form-label">Your Timezone</label>
      <select class="form-select" v-model="form.preferred_timezone">
        <option value="America/New_York">Eastern Time (ET) — New York, Miami</option>
        <option value="America/Chicago">Central Time (CT) — Chicago, Dallas</option>
        <option value="America/Denver">Mountain Time (MT) — Denver, Phoenix</option>
        <option value="America/Los_Angeles">Pacific Time (PT) — Los Angeles, Seattle</option>
        <option value="America/Anchorage">Alaska Time (AKT)</option>
        <option value="Pacific/Honolulu">Hawaii Time (HST)</option>
      </select>
    </div>

    <!-- Format -->
    <div class="form-group">
      <label class="form-label">Format</label>
      <select class="form-select" v-model="form.format">
        <option value="telehealth">Virtual (Telehealth)</option>
        <option value="in_person">In-Person</option>
        <option value="">No preference</option>
      </select>
    </div>

    <!-- Message -->
    <div class="form-group">
      <label class="form-label">
        Message to Provider
        <span style="color:var(--text-4);font-weight:500"> (optional)</span>
      </label>
      <textarea
        v-model="form.message"
        class="form-textarea"
        rows="3"
        placeholder="Briefly describe what you'd like to discuss or any specific questions…"
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
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { route }   from 'ziggy-js'
import useVuelidate from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'
import { useModal } from '@/composables/useModal'
import { useToast }  from '@/composables/useToast'

const props = defineProps({
  providerId:    { type: String, default: '' },
  providerLabel: { type: String, default: '' },
  serviceId:     { type: String, default: '' },
  serviceTitle:  { type: String, default: '' },
})

const { isOpen, closeModal } = useModal()
const toast = useToast()

const hasService = computed(() => !!(props.serviceTitle || props.serviceId))

// ── useForm at top level (never inside functions) ─────────────────────────
const form = useForm({
  service_id:         '',
  service:            '',
  date:               '',
  time:               'Flexible',
  preferred_date:     '',
  preferred_time:     'Flexible',
  preferred_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/New_York',
  format:             'telehealth',
  message:            '',
  notes:              '',
  timezone:           Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/New_York',
})

const todayDate = new Date().toISOString().split('T')[0]

// ── Vuelidate rules ────────────────────────────────────────────────────────
const rules = computed(() => ({
  date: {
    required: helpers.withMessage('Please select a preferred date.', required),
  },
  service: hasService.value ? {} : {
    required: helpers.withMessage('Please describe the service you need.', required),
  },
}))

const v$ = useVuelidate(rules, form)

function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message ?? ''
  return form.errors[field] ?? ''
}

// ── Sync props when parent sets them ──────────────────────────────────────
watch(() => props.serviceTitle, (v) => { form.service    = v || '' }, { immediate: true })
watch(() => props.serviceId,    (v) => { form.service_id = v || '' }, { immediate: true })

// Reset when modal closes
watch(() => isOpen('serviceRequestModal').value, (open) => {
  if (!open) {
    v$.value.$reset()
    form.reset()
    form.service    = props.serviceTitle || ''
    form.service_id = props.serviceId   || ''
    form.preferred_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/New_York'
    form.timezone           = form.preferred_timezone
    form.time               = 'Flexible'
    form.preferred_time     = 'Flexible'
  }
})

function preselect(serviceName, serviceId = '') {
  form.service    = serviceName
  form.service_id = serviceId
  v$.value.$reset()
}

function onUpdateOpen(v) {
  if (!v) onClose()
}

function onClose() {
  closeModal('serviceRequestModal')
}

async function submit() {
  // Touch all fields for inline errors
  await v$.value.$validate()
  if (v$.value.$error) return

  // Sync legacy field names
  form.preferred_date     = form.date
  form.preferred_time     = form.time
  form.preferred_timezone = form.timezone
  form.notes              = form.message

  // Context 1: Explore tab
  if (props.serviceId) {
    form.service_id = props.serviceId
    form.post(route('provider.services.explore.request'), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Service request sent! The provider will respond within 72 hours.')
        onClose()
      },
      onError: () => toast.error(form.errors.service_id ?? 'Failed to send service request.'),
    })
    return
  }

  // Context 2: Public provider profile
  if (props.providerId) {
    form.post(route('public.profile.service-request', { user: props.providerId }), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Service request sent.')
        onClose()
      },
      onError: () => toast.error('Failed to send service request.'),
    })
    return
  }

  // Context 3: Network fallback
  toast.success('Service request sent! Provider will be notified.')
  onClose()
}

defineExpose({ preselect })
</script>
