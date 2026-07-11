<!--
  ServiceRequestModal.vue — universal "Request a Service" modal.

  Wave 4 update: now handles THREE submission contexts:
    1. Public provider profile — posts to public.profile.service-request (existing)
    2. Explore tab — posts to provider.services.explore.request (Wave 3 new route)
    3. Network page — toast fallback (no route, legacy)

  Context is determined by which props are provided:
    serviceId present   → Explore tab submission (uses new route)
    providerId present  → Public profile submission (existing route)
    neither             → Network fallback toast

  Props:
    providerId    — target user ID (for public profile route)
    providerLabel — display name for public profile route
    serviceId     — service ID (for explore tab route)
    serviceTitle  — service title (prefills Service field, readonly)
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

    <!-- Service (readonly if prefilled) -->
    <div class="form-group">
      <label class="form-label">Service</label>
      <input
        v-if="hasService"
        type="text"
        class="form-input"
        :value="form.service"
        readonly
      />
      <input
        v-else
        type="text"
        class="form-input"
        v-model="form.service"
        placeholder="Describe the service you need…"
      />
    </div>

    <!-- From Provider (readonly) -->
    <div v-if="providerLabel" class="form-group">
      <label class="form-label">Provider</label>
      <input type="text" class="form-input" :value="providerLabel" readonly />
    </div>

    <!-- Date + Time + Timezone -->
    <div class="form-row form-row-2">
      <div class="form-group">
        <label class="form-label">Preferred Date <span class="req">*</span></label>
        <input type="date" class="form-input" v-model="form.date" :min="todayDate" />
        <div v-if="form.errors.date" class="form-error">{{ form.errors.date }}</div>
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

    <!-- Notes -->
    <div class="form-group">
      <label class="form-label">
        Message to Provider
        <span style="color:var(--text-4);font-weight:500"> (optional)</span>
      </label>
      <textarea
        class="form-textarea"
        rows="3"
        placeholder="Briefly describe what you'd like to discuss or any specific questions…"
        v-model="form.message"
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
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { route }   from 'ziggy-js'
import { useModal } from '@/composables/useModal'
import { useToast }  from '@/composables/useToast'

const props = defineProps({
  // Public profile context
  providerId:    { type: String, default: '' },
  providerLabel: { type: String, default: '' },
  // Explore tab context (Wave 4 new)
  serviceId:    { type: String, default: '' },
  serviceTitle: { type: String, default: '' },
})

const { isOpen, closeModal } = useModal()
const toast = useToast()

const hasService = computed(() => !!(props.serviceTitle || props.serviceId))

const form = useForm({
  // For explore tab route
  service_id:         props.serviceId  || '',
  service:            props.serviceTitle || '',
  // For public profile route
  preferred_date:     '',
  preferred_time:     'Flexible',
  preferred_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/New_York',
  format:             'telehealth',
  message:            '',
  // Legacy field names (kept for public profile route compatibility)
  date:               '',
  time:               'Flexible',
  timezone:           Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/New_York',
  notes:              '',
})

const todayDate = new Date().toISOString().split('T')[0]

// Exposed so parent can set service info before opening
function preselect(serviceName, serviceId = '') {
  form.service    = serviceName
  form.service_id = serviceId
}

function onUpdateOpen(v) {
  if (!v) onClose()
}

function onClose() {
  closeModal('serviceRequestModal')
  setTimeout(() => {
    form.reset()
    form.service    = props.serviceTitle || ''
    form.service_id = props.serviceId   || ''
  }, 200)
}

function submit() {
  // Sync date fields (legacy + new)
  form.preferred_date = form.date
  form.preferred_time = form.time
  form.preferred_timezone = form.timezone
  form.notes = form.message

  if (!form.date) {
    toast.error('Please select a preferred date.')
    return
  }

  // ── Context 1: Explore tab (serviceId present) ─────────────────────────
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

  // ── Context 2: Public provider profile (providerId present) ───────────
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

  // ── Context 3: Network page fallback ──────────────────────────────────
  toast.success('Service request sent! Provider will be notified.')
  onClose()
}

defineExpose({ preselect })
</script>
